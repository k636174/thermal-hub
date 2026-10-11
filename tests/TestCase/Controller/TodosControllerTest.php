<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TodosControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
    }

    public function testIndexShowsTodoFormAndSystemPlannerSizes(): void
    {
        $this->get('/todos');

        $this->assertResponseOk();
        $this->assertResponseContains('Todoを追加');
        $this->assertResponseContains('詳細');
        $this->assertResponseContains('ステータス');
        $this->assertResponseContains('優先度');
        $this->assertResponseContains('システム手帳 ナロ（170mm）');
        $this->assertResponseContains('M5／マイクロ5（105mm）');
    }

    public function testIndexRequiresLogin(): void
    {
        $this->session([]);
        $this->get('/todos');

        $this->assertRedirectContains('/users/login');
    }
}
