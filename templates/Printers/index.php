<?php $this->assign('title', 'プリンター'); ?>
<div class="printers index content">
    <?= $this->Html->link('プリンターを追加', ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3>プリンター</h3>
    <table><thead><tr><th>名称</th><th>接続先</th><th>文字コード</th><th>操作</th></tr></thead><tbody>
    <?php foreach ($printers as $printer) :
        ?><tr>
        <td><?= h($printer->name) ?></td><td><?= h($printer->host) ?>:<?= $this->Number->format($printer->port) ?></td><td><?= h($printer->encoding) ?></td>
        <td><?= $this->Html->link('編集', ['action' => 'edit', $printer->id]) ?> <?= $this->Form->postLink('削除', ['action' => 'delete', $printer->id], ['confirm' => '削除しますか？']) ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
</div>
