<?php
/** @var \App\View\AppView $this */
/** @var iterable<\App\Model\Entity\Todo> $todos */
/** @var array<int, string> $printers @var array<string, string> $paperGuides */
/** @var int|null $selectedPrinterId @var string $selectedPaperGuide */
/** @var array<string, string> $statuses @var array<string, string> $priorities */
$this->assign('title', 'Todo');
$this->Html->css('todo', ['block' => true]);
$todoList = is_array($todos) ? $todos : iterator_to_array($todos);
$printerRegistrationLink = $this->Html->link('プリンターを登録', ['controller' => 'Printers', 'action' => 'add']);
?>
<div class="todo-page">
    <header class="todo-header">
        <div><p class="todo-eyebrow">SYSTEM PLANNER</p><h1>Todo</h1></div>
        <p>やることを管理し、システム手帳用のリストとして印字できます。</p>
    </header>

    <section class="todo-add-panel">
        <h2>Todoを追加</h2>
        <?= $this->Form->create(null, ['url' => ['action' => 'add'], 'class' => 'todo-add-form']) ?>
        <?= $this->Form->control('title', [
            'label' => '件名', 'maxlength' => 500, 'required' => true, 'placeholder' => 'やることを入力',
        ]) ?>
        <?= $this->Form->control('description', [
            'type' => 'textarea', 'label' => '詳細', 'rows' => 2, 'placeholder' => '補足情報を入力',
        ]) ?>
        <?= $this->Form->control('status', [
            'type' => 'select', 'label' => 'ステータス', 'options' => $statuses, 'value' => 'pending',
        ]) ?>
        <?= $this->Form->control('priority', [
            'type' => 'select', 'label' => '優先度', 'options' => $priorities, 'value' => 'medium',
        ]) ?>
        <?= $this->Form->control('due_date', ['type' => 'date', 'label' => '期限日', 'empty' => true]) ?>
        <?= $this->Form->button('追加') ?>
        <?= $this->Form->end() ?>
    </section>

    <?php if ($todoList) : ?>
        <section class="todo-list" aria-label="Todo一覧">
            <p class="todo-order-note">未完了のTodoを、期限の近さと優先度を合わせた緊急度順で表示しています。</p>
            <?php foreach ($todoList as $todo) : ?>
                <article class="todo-item<?= $todo->is_completed ? ' completed' : '' ?>">
                    <label class="todo-select">
                        <input
                            type="checkbox" name="todo_ids[]" value="<?= h((string)$todo->id) ?>"
                            form="todo-print-form"
                        >
                        <span class="todo-check" aria-label="印字対象に選択"></span>
                    </label>
                    <div class="todo-content">
                        <strong><?= h($todo->title) ?></strong>
                        <?php if ($todo->description !== '') : ?>
                            <span class="todo-description"><?= nl2br(h($todo->description)) ?></span>
                        <?php endif; ?>
                        <span class="todo-meta">
                            <small class="todo-status status-<?= h($todo->status) ?>">
                                <?= h($statuses[$todo->status] ?? $todo->status) ?>
                            </small>
                            <small class="todo-priority priority-<?= h($todo->priority) ?>">
                                優先度: <?= h($priorities[$todo->priority] ?? $todo->priority) ?>
                            </small>
                        </span>
                        <?php if ($todo->due_date) : ?><small>期限 <?= h($todo->due_date->format('Y/m/d')) ?></small><?php endif; ?>
                    </div>
                    <div class="todo-actions">
                        <?= $this->Html->link('編集', ['action' => 'edit', $todo->id], [
                            'class' => 'button button-outline',
                        ]) ?>
                        <?= $this->Form->postLink(
                            $todo->is_completed ? '未完了に戻す' : '完了',
                            ['action' => 'toggle', $todo->id],
                            ['class' => 'button button-outline'],
                        ) ?>
                        <?= $this->Form->postLink('削除', ['action' => 'delete', $todo->id], [
                            'class' => 'button button-outline todo-delete', 'confirm' => 'このTodoを削除しますか？',
                        ]) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="todo-printer-panel">
            <h2>システム手帳へ印字</h2>
            <?php if ($printers) : ?>
                <?= $this->Form->create(null, [
                    'url' => ['action' => 'printNow'], 'class' => 'todo-print-form', 'id' => 'todo-print-form',
                ]) ?>
                <div class="todo-print-controls">
                    <?= $this->Form->control('printer_id', [
                        'type' => 'select', 'label' => '印字先プリンター', 'options' => $printers,
                        'value' => $selectedPrinterId, 'required' => true,
                    ]) ?>
                    <?= $this->Form->control('paper_guide', [
                        'type' => 'select', 'label' => '用紙サイズ', 'options' => $paperGuides,
                        'value' => $selectedPaperGuide, 'required' => true,
                    ]) ?>
                    <?= $this->Form->button('選択したTodoを印字', [
                        'confirm' => '選択したTodoを印字しますか？',
                    ]) ?>
                </div>
                <?= $this->Form->end() ?>
                <p>印字上限はナロ12件、M5／マイクロ5は7件です。印字するTodoの左端にチェックを入れてください。</p>
            <?php else : ?>
                <p>印字するには<?= $printerRegistrationLink ?>してください。</p>
            <?php endif; ?>
        </section>
    <?php else : ?>
        <div class="todo-empty"><p>まだTodoはありません。</p></div>
    <?php endif; ?>
</div>
