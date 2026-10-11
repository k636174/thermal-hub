<?php
/** @var \App\View\AppView $this @var \App\Model\Entity\Todo $todo */
/** @var array<string, string> $statuses @var array<string, string> $priorities */
$this->assign('title', 'Todo編集');
$this->Html->css('todo', ['block' => true]);
?>
<div class="todo-page">
    <header class="todo-header">
        <div><p class="todo-eyebrow">SYSTEM PLANNER</p><h1>Todo編集</h1></div>
    </header>
    <section class="todo-add-panel">
        <?= $this->Form->create($todo) ?>
        <?= $this->Form->control('title', ['label' => '件名', 'maxlength' => 500, 'required' => true]) ?>
        <?= $this->Form->control('description', ['type' => 'textarea', 'label' => '詳細', 'rows' => 5]) ?>
        <?= $this->Form->control('status', [
            'type' => 'select', 'label' => 'ステータス', 'options' => $statuses, 'required' => true,
        ]) ?>
        <?= $this->Form->control('priority', [
            'type' => 'select', 'label' => '優先度', 'options' => $priorities, 'required' => true,
        ]) ?>
        <?= $this->Form->control('due_date', [
            'type' => 'datetime-local',
            'label' => '期限日時（任意）',
            'empty' => true,
            'value' => $todo->due_date?->format('Y-m-d\TH:i'),
        ]) ?>
        <?= $this->Form->control('clear_due_date', [
            'type' => 'checkbox', 'label' => '現在の期限を解除する', 'value' => '1',
        ]) ?>
        <div class="todo-edit-actions">
            <?= $this->Form->button('更新') ?>
            <?= $this->Html->link('キャンセル', ['action' => 'index'], ['class' => 'button button-outline']) ?>
        </div>
        <?= $this->Form->end() ?>
    </section>
</div>
