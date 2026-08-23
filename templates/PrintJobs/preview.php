<?php $this->assign('title', '印字データプレビュー'); ?>
<div class="printJobs preview content">
    <h3><?= h($printJob->title) ?></h3>
    <p>印字データプレビュー</p>
    <pre class="print-preview-paper"><?php
    $parts = preg_split('/(\[\[QR:.+?\]\]|!!.+?!!)/su', (string)$printJob->body, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts ?: [] as $part) {
        if (preg_match('/^!!(.+?)!!$/su', $part, $match) === 1) {
            echo '<span class="print-preview-reverse">' . h($match[1]) . '</span>';
        } elseif (preg_match('/^\[\[QR:(.+?)\]\]$/su', $part, $match) === 1) {
            echo '<span class="print-preview-qr" title="' . h($match[1]) . '">QR: ' . h($match[1]) . '</span>';
        } else {
            echo h($part);
        }
    }
    ?></pre>
</div>
