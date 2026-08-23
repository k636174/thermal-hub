<?php $this->assign('title', '印字データプレビュー'); ?>
<div class="printJobs preview content">
    <h3><?= h($printJob->title) ?></h3>
    <p>印字データプレビュー</p>
    <pre class="print-preview-paper"><?= h($printJob->body) ?></pre>
</div>
