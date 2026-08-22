<?php $this->assign('title', '宛先ラベル'); ?>
<div class="addressLabels index content">
<?= $this->Html->link('宛先ラベルを追加', ['action' => 'add'], ['class' => 'button float-right']) ?>
<h3>宛先ラベル</h3>
<?php if (!$printers): ?>
<p>画像印字が有効な<?= $this->Html->link('プリンターを登録', ['controller' => 'Printers', 'action' => 'add']) ?>してください。</p>
<?php endif; ?>
<table>
<thead><tr><th>タイトル</th><th>宛名</th><th>郵便番号</th><th>更新日時</th><th>印字</th><th>操作</th></tr></thead>
<tbody>
<?php foreach ($labels as $label): ?>
<tr>
<td><?= h($label->title) ?></td><td><?= h($label->recipient_name) ?> <?= $label->honorific !== 'なし' ? h($label->honorific) : '' ?></td>
<td><?= $label->postal_code ? h(substr($label->postal_code, 0, 3) . '-' . substr($label->postal_code, 3)) : '' ?></td>
<td><?= h($label->modified) ?></td><td>
<?php if ($printers): ?>
<?= $this->Form->create(null, ['url' => ['action' => 'printNow', $label->id]]) ?>
<?= $this->Form->select('printer_id', $printers, ['required' => true]) ?>
<?= $this->Form->button('90度回転して印字', ['confirm' => "「{$label->recipient_name}」を印字しますか？"]) ?>
<?= $this->Form->end() ?>
<?php endif; ?>
</td><td>
<?= $this->Html->link('編集', ['action' => 'edit', $label->id]) ?>
<?= $this->Form->postLink('削除', ['action' => 'delete', $label->id], ['confirm' => '削除しますか？']) ?>
</td></tr>
<?php endforeach; ?>
</tbody></table></div>
