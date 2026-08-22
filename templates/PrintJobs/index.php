<?php $this->assign('title', '印字データ'); ?>
<div class="printJobs index content">
<?= $this->Html->link('印字データを追加', ['action' => 'add'], ['class' => 'button float-right']) ?><h3>印字データ</h3>
<?php if (!$printers) :
    ?><p>印字するには先に<?= $this->Html->link('プリンターを登録', ['controller' => 'Printers', 'action' => 'add']) ?>してください。</p><?php
endif; ?>
<table>
<thead><tr><th>タイトル</th><th>更新日時</th><th>印字</th><th>操作</th></tr></thead>
<tbody>
<?php foreach ($jobs as $job) :
    ?><tr><td><?= h($job->title) ?></td><td><?= h($job->modified) ?></td><td>
    <?php if ($printers) :
        ?>
        <?= $this->Form->create(null, ['url' => ['action' => 'printNow', $job->id]]) ?>
        <?= $this->Form->select('printer_id', $printers, ['required' => true]) ?>
        <?= $this->Form->control('paper_length', [
            'type' => 'select',
            'label' => '用紙の長さ',
            'options' => [
                'none' => '指定なし',
                'narrow' => 'システム手帳 ナロー（17cm）',
                'm5' => 'M5／マイクロ5（10.5cm）',
            ],
        ]) ?>
        <?= $this->Form->button('印字', ['confirm' => 'この内容を印字しますか？']) ?>
        <?= $this->Form->end() ?>
        <?php
    endif; ?>
</td><td>
    <?= $this->Html->link('編集', ['action' => 'edit', $job->id]) ?>
    <?= $this->Form->postLink(
        '削除',
        ['action' => 'delete', $job->id],
        ['confirm' => '削除しますか？'],
    ) ?>
</td></tr><?php
endforeach; ?>
</tbody></table></div>
