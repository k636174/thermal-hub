<?php $this->assign('title', '画像印字'); ?>
<div class="imagePrintJobs index content">
<h3>画像印字</h3>
<?= $this->Form->create(null, ['url' => ['action' => 'add'], 'type' => 'file']) ?>
<fieldset><legend>画像をアップロード</legend>
<?= $this->Form->control('title', ['label' => 'タイトル（省略時はファイル名）']) ?>
<?= $this->Form->control('image', [
    'type' => 'file',
    'label' => '画像（JPEG・PNG・WebP、10MB以下）',
    'accept' => 'image/jpeg,image/png,image/webp',
    'required' => true,
]) ?>
</fieldset>
<?= $this->Form->button('アップロード') ?>
<?= $this->Form->end() ?>

<?php if (!$printers) : ?>
<p>印字するには画像印字を有効にした<?= $this->Html->link(
    'プリンターを登録',
    ['controller' => 'Printers', 'action' => 'add'],
) ?>してください。</p>
<?php endif; ?>

<table><thead><tr><th>画像</th><th>タイトル</th><th>ファイル</th><th>印字</th><th>操作</th></tr></thead><tbody>
<?php foreach ($images as $image) : ?>
<tr>
<td><a href="<?= $this->Url->build(['action' => 'preview', $image->id]) ?>" target="_blank" rel="noopener">
<img src="<?= $this->Url->build(['action' => 'preview', $image->id]) ?>" alt="<?= h($image->title) ?>" style="max-width:120px;max-height:100px">
</a></td>
<td><?= h($image->title) ?></td>
<td><?= h($image->original_name) ?><br><?= h(number_format((int)$image->file_size / 1024, 1)) ?> KB</td>
<td><?php if ($printers) : ?>
<?= $this->Form->create(null, ['url' => ['action' => 'printNow', $image->id]]) ?>
<?= $this->Form->select('printer_id', $printers, [
    'required' => true,
    'aria-label' => 'プリンター',
    'value' => isset($printers[(int)$image->last_printer_id]) ? (int)$image->last_printer_id : null,
]) ?>
<?= $this->Form->button('印字', ['confirm' => 'この画像を印字しますか？']) ?>
<?= $this->Form->end() ?>
<?php endif; ?></td>
<td><?= $this->Form->postLink('削除', ['action' => 'delete', $image->id], ['confirm' => '削除しますか？']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
