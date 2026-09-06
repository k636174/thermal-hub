<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

class WeeklySchedulesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
    }

    public function testIndexShowsRequestedWeekFromMonday(): void
    {
        $this->get('/weekly-schedules?date=2026-09-09');

        $this->assertResponseOk();
        $this->assertResponseContains('2026年9月7日');
        $this->assertResponseContains('2026-09-07');
        $this->assertResponseContains('2026-09-13');
        $body = (string)$this->_response->getBody();
        $this->assertSame(7, substr_count($body, 'data-date='));
    }

    public function testIndexFallsBackToCurrentWeekForInvalidInput(): void
    {
        $this->get('/weekly-schedules?date=invalid');

        $this->assertResponseOk();
        $monday = new DateTimeImmutable('monday this week');
        $this->assertResponseContains($monday->format('Y年n月j日'));
    }

    public function testIndexRequiresLogin(): void
    {
        $this->session([]);
        $this->get('/weekly-schedules');

        $this->assertRedirectContains('/users/login');
    }
}
