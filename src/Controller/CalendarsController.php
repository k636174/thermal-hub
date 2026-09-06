<?php
declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;

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

        $this->set([
            'year' => $year,
            'month' => $month,
            'days' => $days,
            'previousMonth' => $monthStart->modify('-1 month'),
            'nextMonth' => $monthStart->modify('+1 month'),
        ]);
    }
}
