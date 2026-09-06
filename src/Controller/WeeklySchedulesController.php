<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\WeeklyScheduleEntriesTable;
use App\Service\WeeklySchedulePrintService;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use DateTimeImmutable;
use Throwable;
use UnexpectedValueException;

/** Weekly schedule display and printing. */
class WeeklySchedulesController extends AppController
{
    /** Display the week containing the requested date. */
    public function index(): void
    {
        $today = new DateTimeImmutable('today');
        $selectedDate = $this->parseDate((string)$this->getRequest()->getQuery('date')) ?? $today;
        $weekStart = $selectedDate->modify('-' . ((int)$selectedDate->format('N') - 1) . ' days');
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $days = [];
        for ($index = 0; $index < 7; $index++) {
            $date = $weekStart->modify('+' . $index . ' days');
            $days[] = [
                'date' => $date,
                'weekday' => $weekdays[$index],
                'isToday' => $date->format('Y-m-d') === $today->format('Y-m-d'),
                'inverted' => $index >= 5,
            ];
        }

        $uid = $this->currentUserId();
        $savedEntries = [];
        $weekEnd = $weekStart->modify('+6 days');
        foreach (
            $this->weeklyScheduleEntries()->find()
            ->where([
                'user_id' => $uid,
                'schedule_date >=' => $weekStart->format('Y-m-d'),
                'schedule_date <=' => $weekEnd->format('Y-m-d'),
            ]) as $entry
        ) {
            $scheduleDate = $entry->get('schedule_date');
            if ($scheduleDate instanceof Date) {
                $savedEntries[$scheduleDate->format('Y-m-d')] = $entry;
            }
        }
        foreach ($days as &$day) {
            $key = $day['date']->format('Y-m-d');
            if (isset($savedEntries[$key])) {
                $day['note'] = (string)$savedEntries[$key]->get('note');
                $day['inverted'] = (bool)$savedEntries[$key]->get('is_inverted');
            } else {
                $day['note'] = '';
            }
        }
        unset($day);
        $printers = $this->fetchTable('Printers')->find('list')
            ->where(['user_id' => $uid])->toArray();
        $lastPrint = $this->fetchTable('PrintLogs')->find()
            ->select(['printer_id'])
            ->where(['user_id' => $uid, 'document_type' => 'weekly_schedule'])
            ->orderBy(['printed_at' => 'DESC', 'id' => 'DESC'])
            ->first();
        $lastPrinterId = (int)($lastPrint?->get('printer_id') ?? 0);

        $this->set([
            'weekStart' => $weekStart,
            'days' => $days,
            'previousWeek' => $weekStart->modify('-7 days'),
            'nextWeek' => $weekStart->modify('+7 days'),
            'printers' => $printers,
            'selectedPrinterId' => array_key_exists($lastPrinterId, $printers) ? $lastPrinterId : null,
        ]);
    }

    /** Save the submitted week without printing it. */
    public function saveSchedule(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        [$weekStart, $notes, $invertedDays] = $this->submittedSchedule();
        $this->weeklyScheduleEntries()->saveWeek(
            $this->currentUserId(),
            $weekStart,
            $notes,
            $invertedDays,
        );
        $this->Flash->success('週間スケジュールを保存しました。');

        return $this->redirect(['action' => 'index', '?' => ['date' => $weekStart->format('Y-m-d')]]);
    }

    /** Print the submitted weekly schedule on an owned raster printer. */
    public function printNow(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        [$weekStart, $notes, $invertedDays] = $this->submittedSchedule();

        $uid = $this->currentUserId();
        $printerId = (int)$this->getRequest()->getData('printer_id');
        $printer = $this->fetchTable('Printers')->find()
            ->where(['id' => $printerId, 'user_id' => $uid])->first();
        if (!$printer) {
            throw new NotFoundException();
        }
        $this->weeklyScheduleEntries()->saveWeek($uid, $weekStart, $notes, $invertedDays);

        $status = 'success';
        $message = $weekStart->format('Y年n月j日') . '開始の週間スケジュールを送信しました。';
        try {
            (new WeeklySchedulePrintService())->print($weekStart, $notes, $invertedDays, $printer->toArray());
            $this->Flash->success($message);
        } catch (Throwable $exception) {
            $status = 'failed';
            $message = mb_substr($exception->getMessage(), 0, 500);
            $this->Flash->error($message);
        }
        $log = $this->fetchTable('PrintLogs')->newEntity([
            'user_id' => $uid,
            'printer_id' => $printer->get('id'),
            'print_job_id' => null,
            'address_label_id' => null,
            'document_type' => 'weekly_schedule',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $this->fetchTable('PrintLogs')->saveOrFail($log);

        return $this->redirect(['action' => 'index', '?' => ['date' => $weekStart->format('Y-m-d')]]);
    }

    /** Parse an exact ISO date without PHP's rollover behavior. */
    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }

    /** @return array{0: \DateTimeImmutable, 1: array<int, string>, 2: array<int, int>} */
    private function submittedSchedule(): array
    {
        $weekStart = $this->parseDate((string)$this->getRequest()->getData('week_start'));
        if (!$weekStart || $weekStart->format('N') !== '1') {
            throw new BadRequestException('週の指定が不正です。');
        }
        $rawNotes = $this->getRequest()->getData('notes');
        $notes = [];
        for ($index = 0; $index < 7; $index++) {
            $note = is_array($rawNotes) ? (string)($rawNotes[$index] ?? '') : '';
            if (mb_strlen($note) > 120 || count(preg_split('/\R/u', $note) ?: []) > 6) {
                throw new BadRequestException('予定は1日あたり120文字・6行以内で入力してください。');
            }
            $notes[$index] = $note;
        }
        $rawInverted = $this->getRequest()->getData('inverted');
        $invertedDays = [];
        if (is_array($rawInverted)) {
            foreach (array_keys($rawInverted) as $index) {
                $index = filter_var($index, FILTER_VALIDATE_INT);
                if ($index !== false && $index >= 0 && $index <= 6) {
                    $invertedDays[] = $index;
                }
            }
        }

        return [$weekStart, $notes, $invertedDays];
    }

    /** Return the typed schedule-entry repository. */
    private function weeklyScheduleEntries(): WeeklyScheduleEntriesTable
    {
        $table = $this->fetchTable('WeeklyScheduleEntries');
        if (!$table instanceof WeeklyScheduleEntriesTable) {
            throw new UnexpectedValueException('週間スケジュールの保存先を取得できません。');
        }

        return $table;
    }
}
