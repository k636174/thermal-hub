<?php
/** @var \App\View\AppView $this @var \DateTimeImmutable $weekStart */
/** @var array<int, array{date: \DateTimeImmutable, weekday: string, isToday: bool, inverted: bool, note: string}> $days */
/** @var array<int, string> $printers @var int|null $selectedPrinterId */
$this->assign('title', '週間スケジュール');
$this->Html->css('weekly_schedule', ['block' => true]);
$printerRegistrationLink = $this->Html->link('プリンターを登録', ['controller' => 'Printers', 'action' => 'add']);
?>
<div class="calendar-page">
    <header class="calendar-toolbar">
        <div>
            <p class="calendar-eyebrow">WEEKLY PLANNER</p>
            <h1>週間スケジュール</h1>
            <p class="calendar-lead">予定を書き込み、休日にしたい日を反転して、そのままナロー用紙へ。</p>
        </div>
    </header>

    <div class="calendar-controls">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'calendar-date-form']) ?>
        <?= $this->Form->control('date', [
            'type' => 'date', 'label' => '表示する週の日付', 'value' => $weekStart->format('Y-m-d'), 'required' => true,
        ]) ?>
        <?= $this->Form->button('この週を表示') ?>
        <?= $this->Form->end() ?>
        <nav class="calendar-shortcuts" aria-label="週の移動">
            <?= $this->Html->link(
                '‹ 前週',
                [
                    'controller' => 'WeeklySchedules',
                    'action' => 'index',
                    '?' => ['date' => $previousWeek->format('Y-m-d')],
                ],
                ['class' => 'button button-outline'],
            ) ?>
            <?= $this->Html->link('今週', [
                'controller' => 'WeeklySchedules',
                'action' => 'index',
            ], ['class' => 'button button-outline']) ?>
            <?= $this->Html->link(
                '次週 ›',
                [
                    'controller' => 'WeeklySchedules',
                    'action' => 'index',
                    '?' => ['date' => $nextWeek->format('Y-m-d')],
                ],
                ['class' => 'button button-outline'],
            ) ?>
        </nav>
    </div>

    <?= $this->Form->create(null, ['url' => ['action' => 'saveSchedule'], 'class' => 'weekly-print-form']) ?>
    <?= $this->Form->hidden('week_start', ['value' => $weekStart->format('Y-m-d')]) ?>
    <section class="calendar-sheet" aria-label="<?= h($weekStart->format('Y年n月j日') . 'からの週間スケジュール') ?>">
        <div class="schedule-days">
            <?php foreach ($days as $index => $day) : ?>
                <article
                    class="schedule-day<?= $day['isToday'] ? ' today' : '' ?>"
                    data-date="<?= h($day['date']->format('Y-m-d')) ?>"
                >
                    <div class="schedule-date">
                        <span><?= h($day['date']->format('n/j')) ?></span>
                        <strong><?= h($day['weekday']) ?></strong>
                        <?php if ($day['isToday']) :
                            ?><small>TODAY</small><?php
                        endif; ?>
                    </div>
                    <div class="schedule-note">
                        <label for="note-<?= $index ?>">予定</label>
                        <textarea
                            id="note-<?= $index ?>" name="notes[<?= $index ?>]" maxlength="120"
                            rows="6" placeholder="予定を入力（120文字・6行まで、!!文字!!で反転）"
                        ><?= h($day['note']) ?></textarea>
                    </div>
                    <label class="invert-toggle">
                        <input
                            type="checkbox" name="inverted[<?= $index ?>]" value="1"
                            <?= $day['inverted'] ? 'checked' : '' ?>
                        >
                        <span>日付を反転</span>
                    </label>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="calendar-printer-panel">
        <?= $this->Form->button('予定を保存', [
            'type' => 'submit', 'class' => 'button button-outline', 'formnovalidate' => true,
        ]) ?>
        <?php if ($printers) : ?>
            <?= $this->Form->control('printer_id', [
                'type' => 'select', 'label' => '印字先プリンター', 'options' => $printers,
                'value' => $selectedPrinterId, 'required' => true,
            ]) ?>
            <?= $this->Form->button('週間スケジュールを印字', [
                'confirm' => $weekStart->format('Y年n月j日') . 'からの週間スケジュールを印字しますか？',
                'formaction' => $this->Url->build(['action' => 'printNow']),
            ]) ?>
            <p>ESC/POSテキストで直接送信します。予定の <code>!!文字!!</code> は、記号を除いて反転印字します。</p>
        <?php else : ?>
            <p>印字するには<?= $printerRegistrationLink ?>してください。</p>
        <?php endif; ?>
    </div>
    <?= $this->Form->end() ?>
</div>
