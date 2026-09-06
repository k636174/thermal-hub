<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarPrintService;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use DateTimeImmutable;
use Throwable;

/** 月間カレンダーの表示と印刷を提供する。 */
class CalendarsController extends AppController
{
    /** 月間カレンダーを表示する。 */
    public function index(): void
    {
        $today = new DateTimeImmutable('today');
        $year = filter_var(
            $this->getRequest()->getQuery('year'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1900, 'max_range' => 2100]],
        );
        $month = filter_var(
            $this->getRequest()->getQuery('month'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 12]],
        );

        if ($year === false || $month === false) {
            $year = (int)$today->format('Y');
            $month = (int)$today->format('n');
        }

        $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $calendarStart = $monthStart->modify('-' . $monthStart->format('w') . ' days');
        $days = [];
        for ($index = 0; $index < 42; $index++) {
            $date = $calendarStart->modify('+' . $index . ' days');
            $days[] = [
                'date' => $date,
                'inMonth' => $date->format('Y-m') === $monthStart->format('Y-m'),
                'isToday' => $date->format('Y-m-d') === $today->format('Y-m-d'),
            ];
        }

        $uid = $this->currentUserId();
        $printers = $this->fetchTable('Printers')->find('list')
            ->where(['user_id' => $uid, 'raster_enabled' => true])->toArray();
        $lastPrint = $this->fetchTable('PrintLogs')->find()
            ->select(['printer_id'])
            ->where(['user_id' => $uid, 'document_type' => 'calendar'])
            ->orderBy(['printed_at' => 'DESC', 'id' => 'DESC'])
            ->first();
        $lastPrinterId = (int)($lastPrint?->get('printer_id') ?? 0);
        $selectedPrinterId = array_key_exists($lastPrinterId, $printers) ? $lastPrinterId : null;

        $this->set([
            'year' => $year,
            'month' => $month,
            'days' => $days,
            'previousMonth' => $monthStart->modify('-1 month'),
            'nextMonth' => $monthStart->modify('+1 month'),
            'printers' => $printers,
            'selectedPrinterId' => $selectedPrinterId,
        ]);
    }

    /** Send the selected month to an owned raster-capable thermal printer. */
    public function printNow(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $year = filter_var($this->getRequest()->getData('year'), FILTER_VALIDATE_INT);
        $month = filter_var($this->getRequest()->getData('month'), FILTER_VALIDATE_INT);
        if ($year === false || $year < 1900 || $year > 2100 || $month === false || $month < 1 || $month > 12) {
            throw new BadRequestException('年月の指定が不正です。');
        }

        $uid = $this->currentUserId();
        $printerId = (int)$this->getRequest()->getData('printer_id');
        $printer = $this->fetchTable('Printers')->find()
            ->where(['id' => $printerId, 'user_id' => $uid, 'raster_enabled' => true])->first();
        if (!$printer) {
            throw new NotFoundException();
        }

        $status = 'success';
        $message = sprintf('%d年%d月のカレンダーを送信しました。', $year, $month);
        try {
            (new CalendarPrintService())->print($year, $month, $printer->toArray());
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
            'document_type' => 'calendar',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $this->fetchTable('PrintLogs')->saveOrFail($log);

        return $this->redirect(['action' => 'index', '?' => ['year' => $year, 'month' => $month]]);
    }
}
