<?= $this->Form->create($printer) ?>
<fieldset><legend><?= $printer->isNew() ? 'プリンター追加' : 'プリンター編集' ?></legend>
<?= $this->Form->control('name', ['label' => '名称']) ?>
<?= $this->Form->control('host', ['label' => 'ホスト名 / IPアドレス']) ?>
<?= $this->Form->control('port', ['label' => 'TCPポート', 'value' => $printer->port ?? 9100]) ?>
<?= $this->Form->control('encoding', ['label' => '文字コード', 'options' => ['CP932' => 'CP932', 'SHIFT_JIS' => 'Shift_JIS', 'UTF-8' => 'UTF-8']]) ?>
<?= $this->Form->control('timeout', ['label' => 'タイムアウト（秒）', 'value' => $printer->timeout ?? 5]) ?>
<h4>画像・宛先ラベル印字</h4>
<?= $this->Form->control('raster_enabled', ['label' => '画像印字を有効にする', 'type' => 'checkbox', 'default' => true]) ?>
<?= $this->Form->control('dpi', ['label' => '解像度', 'options' => [203 => '203dpi'], 'default' => 203]) ?>
<?= $this->Form->control('printable_width_dots', ['label' => '印字可能幅（dot）', 'value' => $printer->printable_width_dots ?? 576]) ?>
<?= $this->Form->control('label_width_mm', ['label' => '用紙幅（mm）', 'value' => $printer->label_width_mm ?? 72.0]) ?>
<?= $this->Form->control('label_length_mm', ['label' => 'ラベル長（mm）', 'value' => $printer->label_length_mm ?? 100.0]) ?>
</fieldset><?= $this->Form->button('保存') ?> <?= $this->Html->link('戻る', ['action' => 'index']) ?><?= $this->Form->end() ?>
