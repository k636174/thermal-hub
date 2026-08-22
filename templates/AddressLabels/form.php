<?= $this->Form->create($addressLabel) ?>
<fieldset><legend><?= $addressLabel->isNew() ? '宛先ラベル追加' : '宛先ラベル編集' ?></legend>
<p>画面では横書きで作成し、印字時に時計回りへ90度回転します。</p>
<?= $this->Form->control('title', ['label' => '管理用タイトル']) ?>
<?= $this->Form->control('postal_code', ['label' => '郵便番号', 'placeholder' => '123-4567', 'maxlength' => 8]) ?>
<?= $this->Form->control('address_line1', ['label' => '住所1']) ?>
<?= $this->Form->control('address_line2', ['label' => '住所2（建物名・部屋番号）']) ?>
<?= $this->Form->control('recipient_name', ['label' => '宛名']) ?>
<?= $this->Form->control('honorific', ['label' => '敬称', 'options' => ['様' => '様', '御中' => '御中', '行' => '行', 'なし' => 'なし'], 'default' => '様']) ?>
</fieldset>
<?= $this->Form->button('保存') ?>
<?= $this->Form->button('プレビュー', [
    'formaction' => $this->Url->build(['action' => 'preview']),
    'formtarget' => '_blank',
]) ?>
<?= $this->Html->link('戻る', ['action' => 'index']) ?>
<?= $this->Form->end() ?>
