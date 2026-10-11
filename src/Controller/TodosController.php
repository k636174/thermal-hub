<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\TodoOrderingService;
use App\Service\TodoPrintService;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Throwable;

/** User-owned Todo management and planner-sized printing. */
class TodosController extends AppController
{
    private const STATUSES = [
        'pending' => '未着手',
        'in_progress' => '進行中',
        'completed' => '完了',
    ];

    private const PRIORITIES = [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
    ];

    /** Display the current user's Todo list and print settings. */
    public function index(): void
    {
        $uid = $this->currentUserId();
        $todos = (new TodoOrderingService())->sort(
            $this->fetchTable('Todos')->find()->where(['user_id' => $uid])->all()->toList(),
        );
        $printers = $this->fetchTable('Printers')->find('list')
            ->where(['user_id' => $uid])->toArray();
        $preference = $this->fetchTable('TodoPrintPreferences')->find()
            ->where(['user_id' => $uid])->first();
        $lastPrinterId = (int)($preference?->get('printer_id') ?? 0);
        $paperGuide = (string)($preference?->get('paper_guide') ?? 'narrow');
        if (!array_key_exists($paperGuide, TodoPrintService::PAPER_GUIDES)) {
            $paperGuide = 'narrow';
        }

        $this->set([
            'todos' => $todos,
            'printers' => $printers,
            'paperGuides' => TodoPrintService::PAPER_GUIDES,
            'selectedPrinterId' => array_key_exists($lastPrinterId, $printers) ? $lastPrinterId : null,
            'selectedPaperGuide' => $paperGuide,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
        ]);
    }

    /** Create a Todo owned by the current user. */
    public function add(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $todo = $this->fetchTable('Todos')->newEntity($this->normalizedTodoData(), [
            'fields' => ['title', 'description', 'status', 'priority', 'due_date'],
        ]);
        $todo->set('user_id', $this->currentUserId());
        $todo->set('is_completed', $todo->get('status') === 'completed');
        if ($this->fetchTable('Todos')->save($todo)) {
            $this->Flash->success('Todoを追加しました。');
        } else {
            $this->Flash->error('Todoを追加できません。入力内容を確認してください。');
        }

        return $this->redirect(['action' => 'index']);
    }

    /** Edit the content, workflow status, and priority of an owned Todo. */
    public function edit(int $id): ?Response
    {
        $todo = $this->ownedTodo($id);
        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $todo = $this->fetchTable('Todos')->patchEntity($todo, $this->normalizedTodoData(true), [
                'fields' => ['title', 'description', 'status', 'priority', 'due_date'],
            ]);
            $todo->set('is_completed', $todo->get('status') === 'completed');
            if ($this->fetchTable('Todos')->save($todo)) {
                $this->Flash->success('Todoを更新しました。');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Todoを更新できません。入力内容を確認してください。');
        }
        $this->set([
            'todo' => $todo,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
        ]);

        return null;
    }

    /** Toggle one owned Todo between completed and incomplete. */
    public function toggle(int $id): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $todo = $this->ownedTodo($id);
        $todo->set('is_completed', !(bool)$todo->get('is_completed'));
        $todo->set('status', $todo->get('is_completed') ? 'completed' : 'pending');
        $this->fetchTable('Todos')->saveOrFail($todo);
        $this->Flash->success($todo->get('is_completed') ? 'Todoを完了にしました。' : 'Todoを未完了に戻しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Delete one owned Todo. */
    public function delete(int $id): Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $this->fetchTable('Todos')->deleteOrFail($this->ownedTodo($id));
        $this->Flash->success('Todoを削除しました。');

        return $this->redirect(['action' => 'index']);
    }

    /** Print the selected owned Todos on the requested planner size. */
    public function printNow(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $uid = $this->currentUserId();
        $printerId = (int)$this->getRequest()->getData('printer_id');
        $paperGuide = (string)$this->getRequest()->getData('paper_guide');
        if (!array_key_exists($paperGuide, TodoPrintService::PAPER_GUIDES)) {
            throw new BadRequestException('用紙サイズの指定が不正です。');
        }
        $printer = $this->fetchTable('Printers')->find()
            ->where(['id' => $printerId, 'user_id' => $uid])->first();
        if (!$printer) {
            throw new NotFoundException();
        }
        $rawIds = $this->getRequest()->getData('todo_ids');
        $ids = is_array($rawIds) ? array_values(array_unique(array_filter(array_map('intval', $rawIds)))) : [];
        $todos = $ids ? $this->fetchTable('Todos')->find()
            ->where(['user_id' => $uid, 'id IN' => $ids])
            ->orderByAsc('is_completed')->orderByAsc('due_date')->all()->toList() : [];
        if (count($todos) !== count($ids)) {
            throw new NotFoundException();
        }
        $service = new TodoPrintService();
        if (!$todos || count($todos) > $service->limit($paperGuide)) {
            throw new BadRequestException('印字対象は' . $service->limit($paperGuide) . '件以内で選択してください。');
        }

        $this->savePreference($uid, $printerId, $paperGuide);
        $status = 'success';
        $message = count($todos) . '件のTodoを送信しました。';
        try {
            $service->print(
                array_map(static fn($todo): array => $todo->toArray(), $todos),
                $paperGuide,
                $printer->toArray(),
            );
            $this->Flash->success($message);
        } catch (Throwable $exception) {
            $status = 'failed';
            $message = mb_substr($exception->getMessage(), 0, 500);
            $this->Flash->error($message);
        }
        $log = $this->fetchTable('PrintLogs')->newEntity([
            'user_id' => $uid,
            'printer_id' => $printerId,
            'print_job_id' => null,
            'address_label_id' => null,
            'document_type' => 'todo',
            'status' => $status,
            'message' => $message,
            'printed_at' => DateTime::now(),
        ]);
        $this->fetchTable('PrintLogs')->saveOrFail($log);

        return $this->redirect(['action' => 'index']);
    }

    /** Find an owned Todo or reject the request. */
    private function ownedTodo(int $id): EntityInterface
    {
        $todo = $this->fetchTable('Todos')->find()
            ->where(['id' => $id, 'user_id' => $this->currentUserId()])->first();
        if (!$todo) {
            throw new NotFoundException();
        }

        return $todo;
    }

    /** Persist the user's most recently requested printer and paper size. */
    private function savePreference(int $userId, int $printerId, string $paperGuide): void
    {
        $table = $this->fetchTable('TodoPrintPreferences');
        $preference = $table->find()->where(['user_id' => $userId])->first() ?? $table->newEmptyEntity();
        $preference->set('user_id', $userId);
        $preference->set('printer_id', $printerId);
        $preference->set('paper_guide', $paperGuide);
        $table->saveOrFail($preference);
    }

    /** Convert the list screen's date-only value to the persisted datetime format. */
    private function normalizedTodoData(bool $preserveEmptyDueDate = false): array
    {
        $data = $this->getRequest()->getData();
        if (!is_array($data)) {
            return [];
        }
        $dueDate = $data['due_date'] ?? null;
        if ($preserveEmptyDueDate && !empty($data['clear_due_date'])) {
            $data['due_date'] = null;

            return $data;
        }
        if ($preserveEmptyDueDate && ($dueDate === null || $dueDate === '')) {
            unset($data['due_date']);

            return $data;
        }
        if (is_string($dueDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) === 1) {
            $data['due_date'] = $dueDate . ' 00:00:00';
        }

        return $data;
    }
}
