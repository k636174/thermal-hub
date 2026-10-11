<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\EntityInterface;
use DateTimeImmutable;
use DateTimeInterface;

class TodoOrderingService
{
    /**
     * Sort incomplete Todos by combined deadline urgency and priority, then completed Todos.
     *
     * @param list<\Cake\Datasource\EntityInterface> $todos
     * @return list<\Cake\Datasource\EntityInterface>
     */
    public function sort(array $todos, ?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        usort($todos, function (EntityInterface $left, EntityInterface $right) use ($today): int {
            $leftCompleted = $this->isCompleted($left);
            $rightCompleted = $this->isCompleted($right);
            if ($leftCompleted !== $rightCompleted) {
                return $leftCompleted <=> $rightCompleted;
            }
            if (!$leftCompleted) {
                $scoreOrder = $this->score($right, $today) <=> $this->score($left, $today);
                if ($scoreOrder !== 0) {
                    return $scoreOrder;
                }
            }
            $dateOrder = $this->dueTimestamp($left) <=> $this->dueTimestamp($right);

            return $dateOrder !== 0
                ? $dateOrder
                : (int)$left->get('id') <=> (int)$right->get('id');
        });

        return $todos;
    }

    /** Calculate a score where approaching deadlines outweigh priority alone. */
    private function score(EntityInterface $todo, DateTimeImmutable $today): int
    {
        $priorityScore = match ((string)$todo->get('priority')) {
            'high' => 30,
            'medium' => 15,
            default => 0,
        };
        $dueDate = $this->dueDate($todo);
        if (!$dueDate) {
            return $priorityScore;
        }
        $days = (int)$today->diff($dueDate->setTime(0, 0))->format('%r%a');
        $deadlineScore = match (true) {
            $days < 0 => 100,
            $days === 0 => 80,
            $days <= 2 => 60,
            $days <= 7 => 40,
            $days <= 30 => 20,
            default => 0,
        };

        return $deadlineScore + $priorityScore;
    }

    /** Treat either persisted completion representation as completed. */
    private function isCompleted(EntityInterface $todo): int
    {
        return $todo->get('status') === 'completed' || (bool)$todo->get('is_completed') ? 1 : 0;
    }

    /** Return a due date as an immutable value. */
    private function dueDate(EntityInterface $todo): ?DateTimeImmutable
    {
        $value = $todo->get('due_date');
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string)$value);
    }

    /** Put missing due dates after real dates when urgency scores tie. */
    private function dueTimestamp(EntityInterface $todo): int
    {
        return $this->dueDate($todo)?->getTimestamp() ?? PHP_INT_MAX;
    }
}
