<?= $this->Form->create($printJob) ?><fieldset><legend><?= $printJob->isNew() ? '印字データ追加' : '印字データ編集' ?></legend>
<?= $this->Form->control('title', ['label' => 'タイトル']) ?>
<div class="print-markup-tools" role="group" aria-label="印字装飾">
    <button type="button" data-print-markup="reverse">選択文字を反転</button>
    <button type="button" data-print-markup="qr">QRコードを挿入</button>
</div>
<p class="print-markup-help">反転は <code>!!文字!!</code>、QRコードは <code>[[QR:内容]]</code> の形式で本文に保存されます。</p>
<?= $this->Form->control('body', [
    'label' => '本文',
    'type' => 'textarea',
    'rows' => 12,
    'id' => 'print-job-body',
]) ?>
</fieldset>
<?= $this->Form->button('保存') ?>
<?= $this->Form->button('プレビュー', [
    'formaction' => $this->Url->build(['action' => 'preview']),
    'formtarget' => '_blank',
]) ?>
<?= $this->Html->link('戻る', ['action' => 'index']) ?>
<?= $this->Form->end() ?>
<script>
document.querySelectorAll('[data-print-markup]').forEach((button) => {
    button.addEventListener('click', () => {
        const body = document.getElementById('print-job-body');
        if (!body) return;
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const selected = body.value.slice(start, end);
        const isQr = button.dataset.printMarkup === 'qr';
        const content = selected || (isQr ? 'https://example.com' : '反転文字');
        const replacement = isQr ? `[[QR:${content}]]` : `!!${content}!!`;
        body.setRangeText(replacement, start, end, 'select');
        body.focus();
    });
});
</script>
