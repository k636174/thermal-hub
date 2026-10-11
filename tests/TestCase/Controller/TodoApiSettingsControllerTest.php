<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TodoApiSettingsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testIndexShowsTokenManagementForLoggedInUser(): void
    {
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
        $this->get('/todo-api-settings');

        $this->assertResponseOk();
        $this->assertResponseContains('Todo API設定');
        $this->assertResponseContains('発行して一度だけ表示');
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/todo-api-settings');

        $this->assertRedirectContains('/users/login');
    }
}
