<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Service\TodoApiTokenService;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TodosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private int $apiUserId;

    public function setUp(): void
    {
        parent::setUp();
        $users = FactoryLocator::get('Table')->get('Users');
        $user = $users->find()->where(['email' => 'api@example.com'])->first();
        if (!$user) {
            $user = $users->newEntity([
                'email' => 'api@example.com', 'name' => 'API User', 'password' => 'test-password',
            ]);
            $users->saveOrFail($user);
        }
        $this->apiUserId = (int)$user->get('id');
        $tokens = FactoryLocator::get('Table')->get('TodoApiTokens');
        $token = $tokens->find()->where(['user_id' => $this->apiUserId])->first()
            ?? $tokens->newEmptyEntity();
        $service = new TodoApiTokenService();
        $token->set('user_id', $this->apiUserId);
        $token->set('token_hash', $service->hash('test-fixed-token-that-is-long-enough'));
        $token->set('token_hint', $service->hint('test-fixed-token-that-is-long-enough'));
        $tokens->saveOrFail($token);
    }

    public function testIndexRejectsMissingBearerToken(): void
    {
        $this->get('/api/todos');

        $this->assertResponseCode(401);
        $this->assertContentType('application/json');
    }

    public function testCreateAndListTodoAsJson(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer test-fixed-token-that-is-long-enough',
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->post('/api/todos', json_encode([
            'title' => '牛乳を買う',
            'description' => '低脂肪乳を2本',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2026-09-30T19:00:00Z',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseCode(201);
        $payload = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('牛乳を買う', $payload['title']);
        $this->assertSame('high', $payload['priority']);
        $this->assertSame('2026-09-30T19:00:00Z', $payload['due_date']);

        $this->get('/api/todos');
        $this->assertResponseOk();
        $list = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('牛乳を買う', $list[0]['title']);
    }

    public function testViewDoesNotExposeAnotherUsersTodo(): void
    {
        $todos = FactoryLocator::get('Table')->get('Todos');
        $users = FactoryLocator::get('Table')->get('Users');
        $otherUser = $users->find()->where(['email' => 'other-api@example.com'])->first();
        if (!$otherUser) {
            $otherUser = $users->newEntity([
                'email' => 'other-api@example.com', 'name' => 'Other API User', 'password' => 'test-password',
            ]);
            $users->saveOrFail($otherUser);
        }
        $todo = $todos->newEntity(['title' => '非公開']);
        $todo->set('user_id', $otherUser->get('id'));
        $todo->set('description', '');
        $todo->set('status', 'pending');
        $todo->set('priority', 'medium');
        $todos->saveOrFail($todo);
        $this->configRequest([
            'headers' => ['Authorization' => 'Bearer test-fixed-token-that-is-long-enough'],
        ]);

        $this->get('/api/todos/' . $todo->get('id'));

        $this->assertResponseCode(404);
    }
}
