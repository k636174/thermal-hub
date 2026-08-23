<?php $this->assign('title', '印字データ'); ?>
<div class="printJobs index content">
<?= $this->Html->link('印字データを追加', ['action' => 'add'], ['class' => 'button float-right']) ?><h3>印字データ</h3>
<?php if (!$printers) :
    ?><p>印字するには先に<?= $this->Html->link('プリンターを登録', ['controller' => 'Printers', 'action' => 'add']) ?>してください。</p><?php
endif; ?>
<table>
<thead><tr><th>タイトル</th><th>更新日時</th><th>印字</th><th>操作</th></tr></thead>
<tbody>
<?php foreach ($jobs as $job) :
    $guide = $printGuides[(int)$job->id];
    ?><tr><td><?= h($job->title) ?></td><td><?= h($job->modified) ?></td><td>
    <?php if ($printers) :
        $selectedPrinterId = (int)($job->last_printer_id ?? 0);
        $selectedPaperGuide = (string)($job->paper_guide ?? $job->last_paper_guide ?? 'none');
        ?>
        <?= $this->Form->create(null, ['url' => ['action' => 'printNow', $job->id]]) ?>
        <select name="printer_id" required aria-label="プリンター">
            <?php foreach ($printers as $printerId => $printerName) : ?>
                <option value="<?= h((string)$printerId) ?>"<?= (int)$printerId === $selectedPrinterId ? ' selected' : '' ?>><?= h($printerName) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="paper-guide-<?= h((string)$job->id) ?>">用紙サイズ（印字範囲の目安）</label>
        <select name="paper_guide" id="paper-guide-<?= h((string)$job->id) ?>" class="paper-guide-select">
            <?php foreach ([
                'none' => '指定なし',
                'narrow' => 'システム手帳 ナロー（17.0cm）',
                'm5' => 'M5／マイクロ5（10.5cm）',
            ] as $guideValue => $guideLabel) : ?>
                <option value="<?= h($guideValue) ?>"<?= $guideValue === $selectedPaperGuide ? ' selected' : '' ?>><?= h($guideLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="paper-guide-result"
            data-input-lines="<?= h((string)$guide['inputLines']) ?>"
            data-characters="<?= h((string)$guide['characters']) ?>"
            data-max-columns="<?= h((string)$guide['maxColumns']) ?>"
            data-wrapped-lines="<?= h((string)$guide['wrappedLines']) ?>"
            data-qr-codes="<?= h((string)$guide['qrCodes']) ?>"
            data-narrow-limit="<?= h((string)$paperGuideLimits['narrow']) ?>"
            data-m5-limit="<?= h((string)$paperGuideLimits['m5']) ?>"></p>
        <?= $this->Form->button('印字', ['confirm' => 'この内容を印字しますか？']) ?>
        <?= $this->Form->end() ?>
        <?php
    endif; ?>
</td><td>
    <?= $this->Html->link('編集', ['action' => 'edit', $job->id]) ?>
    <?= $this->Form->postLink(
        '削除',
        ['action' => 'delete', $job->id],
        ['confirm' => '削除しますか？'],
    ) ?>
</td></tr><?php
endforeach; ?>
</tbody></table></div>
<script>
document.querySelectorAll('.paper-guide-select').forEach((select) => {
    const result = select.closest('form')?.querySelector('.paper-guide-result');
    if (!result) {
        return;
    }

    const updateGuide = () => {
        const inputLines = Number(result.dataset.inputLines);
        const characters = Number(result.dataset.characters);
        const maxColumns = Number(result.dataset.maxColumns);
        const wrappedLines = Number(result.dataset.wrappedLines);
        const qrCodes = Number(result.dataset.qrCodes);
        const limits = {
            narrow: Number(result.dataset.narrowLimit),
            m5: Number(result.dataset.m5Limit),
        };
        const details = `入力${inputLines}行・${characters}文字・最大${maxColumns}桁・折り返し込み約${wrappedLines}行`;

        result.classList.remove('within-range', 'outside-range');
        if (!(select.value in limits)) {
            result.textContent = `${details}。用紙を選択すると印字可能範囲の目安を表示します。`;
            return;
        }

        const limit = limits[select.value];
        const withinRange = wrappedLines <= limit;
        result.classList.add(withinRange ? 'within-range' : 'outside-range');
        const comparison = withinRange
            ? `参考上限${limit}行の範囲内です。`
            : `参考上限${limit}行を約${wrappedLines - limit}行超えています。`;
        const qrNotice = qrCodes > 0
            ? ` QRコード${qrCodes}個の高さはこの判定に含まれないため、実際の範囲は保証されません。`
            : ' 実際の印字範囲を保証するものではありません。';
        result.textContent = `${details}。${comparison}${qrNotice}`;
    };

    select.addEventListener('change', updateGuide);
    updateGuide();
});
</script>
