<?= $this->Form->create($printJob) ?><fieldset><legend><?= $printJob->isNew() ? '印字データ追加' : '印字データ編集' ?></legend>
<?= $this->Form->control('title', ['label' => 'タイトル']) ?>
<?= $this->Form->control('paper_guide', [
    'type' => 'select',
    'label' => '用紙サイズ（印字範囲の目安）',
    'id' => 'print-job-paper-guide',
    'options' => [
        'none' => '指定なし',
        'narrow' => 'システム手帳 ナロー（80×170mm）',
        'm5' => 'M5／マイクロ5（62×105mm）',
    ],
    'default' => 'none',
]) ?>
<p id="print-job-paper-guide-result" class="paper-guide-result"
    data-narrow-limit="<?= h((string)$paperGuideLimits['narrow']) ?>"
    data-m5-limit="<?= h((string)$paperGuideLimits['m5']) ?>"></p>
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

const paperGuide = document.getElementById('print-job-paper-guide');
const paperGuideResult = document.getElementById('print-job-paper-guide-result');
const printBody = document.getElementById('print-job-body');
const paperSizes = {
    narrow: {width: 80, length: 170, name: 'ナロー'},
    m5: {width: 62, length: 105, name: 'M5／マイクロ5'},
};
const columnWidth = (text) => Array.from(text).reduce((width, character) => {
    const code = character.codePointAt(0) ?? 0;
    const isWide = code >= 0x1100 && (
        code <= 0x115f || code === 0x2329 || code === 0x232a ||
        (code >= 0x2e80 && code <= 0xa4cf) || (code >= 0xac00 && code <= 0xd7a3) ||
        (code >= 0xf900 && code <= 0xfaff) || (code >= 0xfe10 && code <= 0xfe6f) ||
        (code >= 0xff01 && code <= 0xff60) || (code >= 0xffe0 && code <= 0xffe6)
    );
    return width + (isWide ? 2 : 1);
}, 0);
const analyzeBody = () => {
    const lines = (printBody?.value ?? '').replace(/\r\n?/g, '\n').split('\n');
    let characters = 0;
    let maxColumns = 0;
    let wrappedLines = 0;
    let qrCodes = 0;
    lines.forEach((line) => {
        const withoutQr = line.replace(/\[\[QR:(.+?)\]\]/gu, () => { qrCodes++; return ''; });
        const visible = withoutQr.replace(/!!(.+?)!!/gu, '$1');
        const columns = columnWidth(visible);
        characters += Array.from(visible).length;
        maxColumns = Math.max(maxColumns, columns);
        wrappedLines += visible === '' ? (line === '' ? 1 : 0) : Math.max(1, Math.ceil(columns / 48));
    });
    return {inputLines: lines.length, characters, maxColumns, wrappedLines, qrCodes};
};
const updatePaperGuide = () => {
    if (!paperGuide || !paperGuideResult || !printBody) return;
    const size = paperSizes[paperGuide.value];
    const analysis = analyzeBody();
    const details = `入力${analysis.inputLines}行・${analysis.characters}文字・最大${analysis.maxColumns}桁・折り返し込み約${analysis.wrappedLines}行`;
    paperGuideResult.classList.remove('within-range', 'outside-range');
    printBody.classList.toggle('paper-sized-textarea', Boolean(size));
    if (!size) {
        printBody.style.removeProperty('--paper-width');
        printBody.style.removeProperty('--paper-length');
        paperGuideResult.textContent = `${details}。用紙を選択すると印字可能範囲の目安を表示します。`;
        return;
    }
    printBody.style.setProperty('--paper-width', `${size.width}mm`);
    printBody.style.setProperty('--paper-length', `${size.length}mm`);
    const limit = Number(paperGuideResult.dataset[`${paperGuide.value}Limit`]);
    const withinRange = analysis.wrappedLines <= limit;
    paperGuideResult.classList.add(withinRange ? 'within-range' : 'outside-range');
    const comparison = withinRange ? `参考上限${limit}行の範囲内です。` : `参考上限を約${analysis.wrappedLines - limit}行超えています。`;
    const qrNotice = analysis.qrCodes > 0 ? ` QRコード${analysis.qrCodes}個の高さは判定に含まれません。` : '';
    paperGuideResult.textContent = `${size.name}（横${size.width}mm × 長さ${size.length}mm）：${details}。${comparison}${qrNotice} 実際の印字範囲を保証するものではありません。`;
};
paperGuide?.addEventListener('change', updatePaperGuide);
printBody?.addEventListener('input', updatePaperGuide);
updatePaperGuide();
</script>
