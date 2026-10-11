<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use App\Service\TodoApiTokenService;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonException;

/** Fixed-token JSON API for user-owned Todos. */
class TodosController extends AppController
{
    private int $apiUserId = 0;

    /** Authenticate API requests without using the browser session. */
    public function beforeFilter(EventInterface $event): void
    {
        $authorization = $this->getRequest()->getHeaderLine('Authorization');
        $providedToken = preg_match('/^Bearer\s+(.+)$/i', $authorization, $match) === 1
            ? trim($match[1])
            : '';
        $token = $providedToken === '' ? null : $this->fetchTable('TodoApiTokens')->find()
            ->where(['token_hash' => (new TodoApiTokenService())->hash($providedToken)])
            ->first();
        if (!$token) {
            $event->stopPropagation();
            $this->setResponse($this->json(['error' => 'unauthorized'], 401));

            return;
        }
        $this->apiUserId = (int)$token->get('user_id');
    }

    /** Return all Todos belonging to the configured API user. */
    public function index(): Response
    {
        $todos = $this->fetchTable('Todos')->find()
            ->where(['user_id' => $this->apiUserId])
            ->orderByAsc('created')
            ->orderByAsc('id')
            ->all();

        return $this->json(array_map(
            fn(EntityInterface $todo): array => $this->serializeTodo($todo),
            $todos->toList(),
        ));
    }

    /** Return one owned Todo. */
    public function view(int $id): Response
    {
        $todo = $this->ownedTodo($id);

        return $todo ? $this->json($this->serializeTodo($todo)) : $this->notFound();
    }

    /** Create a Todo for the configured API user. */
    public function add(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $data = $this->requestData();
        $data += ['description' => '', 'status' => 'pending', 'priority' => 'medium'];
        $todo = $this->fetchTable('Todos')->newEntity($data, [
            'fields' => ['title', 'description', 'status', 'priority', 'due_date'],
        ]);
        $todo->set('user_id', $this->apiUserId);
        $todo->set('is_completed', $todo->get('status') === 'completed');
        if (!$this->fetchTable('Todos')->save($todo)) {
            return $this->validationError($todo);
        }

        return $this->json($this->serializeTodo($todo), 201);
    }

    /** Update one owned Todo. */
    public function edit(int $id): Response
    {
        $this->getRequest()->allowMethod(['patch', 'put']);
        $todo = $this->ownedTodo($id);
        if (!$todo) {
            return $this->notFound();
        }
        $todo = $this->fetchTable('Todos')->patchEntity($todo, $this->requestData(), [
            'fields' => ['title', 'description', 'status', 'priority', 'due_date'],
        ]);
        if ($todo->isDirty('status')) {
            $todo->set('is_completed', $todo->get('status') === 'completed');
        }
        if (!$this->fetchTable('Todos')->save($todo)) {
            return $this->validationError($todo);
        }

        return $this->json($this->serializeTodo($todo));
    }

    /** Delete one owned Todo. */
    public function delete(int $id): Response
    {
        $this->getRequest()->allowMethod(['delete']);
        $todo = $this->ownedTodo($id);
        if (!$todo) {
            return $this->notFound();
        }
        $this->fetchTable('Todos')->deleteOrFail($todo);

        return $this->getResponse()->withStatus(204)->withStringBody('');
    }

    /** Find a Todo without exposing records owned by other users. */
    private function ownedTodo(int $id): ?EntityInterface
    {
        return $this->fetchTable('Todos')->find()
            ->where(['id' => $id, 'user_id' => $this->apiUserId])
            ->first();
    }

    /** Return only array-shaped request input. */
    private function requestData(): array
    {
        $data = $this->getRequest()->getData();

        return is_array($data) ? $data : [];
    }

    /** Convert an entity to the public API representation. */
    private function serializeTodo(EntityInterface $todo): array
    {
        return [
            'id' => (int)$todo->get('id'),
            'title' => (string)$todo->get('title'),
            'description' => (string)$todo->get('description'),
            'status' => (string)$todo->get('status'),
            'priority' => (string)$todo->get('priority'),
            'created_at' => $this->isoDate($todo->get('created')),
            'due_date' => $this->isoDate($todo->get('due_date')),
        ];
    }

    /** Format persisted dates as UTC ISO 8601 values. */
    private function isoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string)$value);

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /** Build a JSON response without exposing secrets in logs or templates. */
    private function json(array $payload, int $status = 200): Response
    {
        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            $body = '{"error":"json_encoding_failed"}';
            $status = 500;
        }

        return $this->getResponse()->withType('application/json')->withStatus($status)->withStringBody($body);
    }

    /** Return entity validation details. */
    private function validationError(EntityInterface $todo): Response
    {
        return $this->json(['error' => 'validation_failed', 'details' => $todo->getErrors()], 422);
    }

    /** Return the API's JSON not-found response. */
    private function notFound(): Response
    {
        return $this->json(['error' => 'not_found'], 404);
    }
}
