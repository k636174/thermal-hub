<?= $this->Form->create($printJob) ?><fieldset><legend><?= $printJob->isNew() ? '印字データ追加' : '印字データ編集' ?></legend>
<?= $this->Form->control('title', ['label' => 'タイトル']) ?><?= $this->Form->control('body', ['label' => '本文', 'type' => 'textarea', 'rows' => 12]) ?>
</fieldset>
<?= $this->Form->button('保存') ?>
<?= $this->Form->button('プレビュー', [
    'formaction' => $this->Url->build(['action' => 'preview']),
    'formtarget' => '_blank',
]) ?>
<?= $this->Html->link('戻る', ['action' => 'index']) ?>
<?= $this->Form->end() ?>
