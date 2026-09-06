<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class CalendarsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->session(['Auth' => ['User' => ['id' => 1]]]);
    }

    public function testIndexShowsRequestedMonthInSixWeeks(): void
    {
        $this->get('/calendars?year=2026&month=9');
        $this->assertResponseOk();
        $this->assertResponseContains('2026年9月');
        $this->assertResponseContains('2026-08-30');
        $this->assertResponseContains('2026-10-10');
        $this->assertResponseContains('href="/calendars?year=2026&amp;month=8"');
        $this->assertResponseContains('href="/calendars">今月</a>');
        $this->assertResponseContains('href="/calendars?year=2026&amp;month=10"');
        $body = (string)$this->_response->getBody();
        $this->assertSame(42, substr_count($body, 'class="calendar-day '));
    }

    public function testMonthLinksCrossYearBoundary(): void
    {
        $this->get('/calendars?year=2026&month=1');
        $this->assertResponseOk();
        $this->assertResponseContains('href="/calendars?year=2025&amp;month=12"');
        $this->assertResponseContains('href="/calendars?year=2026&amp;month=2"');
    }

    public function testIndexFallsBackToCurrentMonthForInvalidInput(): void
    {
        $this->get('/calendars?year=9999&month=13');
        $this->assertResponseOk();
        $this->assertResponseContains(date('Y') . '年' . (int)date('n') . '月');
    }

    public function testIndexRequiresLogin(): void
    {
        $this->session([]);
        $this->get('/calendars');
        $this->assertRedirectContains('/users/login');
    }
}
