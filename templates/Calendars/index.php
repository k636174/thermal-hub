<?php
/**
 * @var \App\View\AppView $this
 * @var int $year
 * @var int $month
 * @var array<array{date: \DateTimeImmutable, inMonth: bool, isToday: bool}> $days
 * @var \DateTimeImmutable $previousMonth
 * @var \DateTimeImmutable $nextMonth
 * @var array<int, string> $printers
 */
$this->assign('title', 'カレンダー');
$this->Html->css('calendar', ['block' => true]);
$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
$printerRegistrationLink = $this->Html->link(
    'プリンターを登録',
    ['controller' => 'Printers', 'action' => 'add'],
);
?>
<div class="calendar-page">
    <div class="calendar-toolbar no-print">
        <div>
            <p class="calendar-eyebrow">MONTHLY PLANNER</p>
            <h1>カレンダー</h1>
        </div>
    </div>

    <div class="calendar-controls no-print">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'calendar-date-form']) ?>
        <?= $this->Form->control('year', [
            'type' => 'number',
            'label' => '年',
            'value' => $year,
            'min' => 1900,
            'max' => 2100,
            'required' => true,
        ]) ?>
        <?= $this->Form->control('month', [
            'type' => 'select',
            'label' => '月',
            'value' => $month,
            'options' => array_combine(range(1, 12), array_map(fn(int $value): string => $value . '月', range(1, 12))),
        ]) ?>
        <?= $this->Form->button('表示する') ?>
        <?= $this->Form->end() ?>
        <div class="calendar-shortcuts">
            <?= $this->Html->link('‹ 前月', [
                'year' => $previousMonth->format('Y'),
                'month' => $previousMonth->format('n'),
            ], ['class' => 'button button-outline']) ?>
            <?= $this->Html->link('今月', ['action' => 'index'], ['class' => 'button button-outline']) ?>
            <?= $this->Html->link('翌月 ›', [
                'year' => $nextMonth->format('Y'),
                'month' => $nextMonth->format('n'),
            ], ['class' => 'button button-outline']) ?>
        </div>
    </div>

    <div class="calendar-printer-panel">
        <?php if ($printers) : ?>
            <?= $this->Form->create(null, [
                'url' => ['action' => 'printNow'],
                'class' => 'calendar-print-form',
            ]) ?>
            <?= $this->Form->hidden('year', ['value' => $year]) ?>
            <?= $this->Form->hidden('month', ['value' => $month]) ?>
            <?= $this->Form->control('printer_id', [
                'type' => 'select',
                'label' => '印字先プリンター',
                'options' => $printers,
                'required' => true,
            ]) ?>
            <?= $this->Form->button('サーマルプリンターで印字', [
                'confirm' => $year . '年' . $month . '月のカレンダーを印字しますか？',
            ]) ?>
            <?= $this->Form->end() ?>
            <p>プリンターの印字可能幅に合わせ、ナローサイズ（長さ170mm）の横長画像として送信します。</p>
        <?php else : ?>
            <p>印字するには画像印字が有効な<?= $printerRegistrationLink ?>してください。</p>
        <?php endif; ?>
    </div>

    <section class="calendar-sheet" aria-label="<?= h($year . '年' . $month . '月のカレンダー') ?>">
        <header class="calendar-heading">
            <p><?= h((string)$year) ?></p>
            <h2><?= h((string)$month) ?><span>月</span></h2>
        </header>
        <div class="calendar-grid calendar-weekdays" aria-hidden="true">
            <?php foreach ($weekdays as $index => $weekday) : ?>
                <div class="calendar-weekday weekday-<?= $index ?>"><?= h($weekday) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="calendar-grid calendar-days">
            <?php foreach ($days as $index => $day) :
                $classes = ['calendar-day', 'weekday-' . ($index % 7)];
                if (!$day['inMonth']) {
                    $classes[] = 'outside-month';
                }
                if ($day['isToday']) {
                    $classes[] = 'today';
                }
                ?>
                <div class="<?= h(implode(' ', $classes)) ?>" data-date="<?= h($day['date']->format('Y-m-d')) ?>">
                    <span class="calendar-day-number"><?= h($day['date']->format('j')) ?></span>
                    <div class="calendar-note-lines" aria-hidden="true"><i></i><i></i><i></i></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
