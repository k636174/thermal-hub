<?= $this->Form->create($printer) ?>
<fieldset><legend><?= $printer->isNew() ? 'プリンター追加' : 'プリンター編集' ?></legend>
<?= $this->Form->control('name', ['label' => '名称']) ?>
<?= $this->Form->control('host', ['label' => 'ホスト名 / IPアドレス']) ?>
<?= $this->Form->control('port', ['label' => 'TCPポート', 'value' => $printer->port ?? 9100]) ?>
<?= $this->Form->control('encoding', ['label' => '文字コード', 'options' => ['CP932' => 'CP932', 'SHIFT_JIS' => 'Shift_JIS', 'UTF-8' => 'UTF-8']]) ?>
<?= $this->Form->control('timeout', ['label' => 'タイムアウト（秒）', 'value' => $printer->timeout ?? 5]) ?>
</fieldset><?= $this->Form->button('保存') ?> <?= $this->Html->link('戻る', ['action' => 'index']) ?><?= $this->Form->end() ?>
