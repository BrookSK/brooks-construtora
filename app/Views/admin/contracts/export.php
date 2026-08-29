<?php
// =====================================================================
// Exportação do Contrato — DOCX e PDF client-side, com identidade visual
// =====================================================================
$contract = $contract ?? [];
$logoUrl  = $logoUrl  ?? '';
$markdown = (string)($contract['contract_markdown'] ?? '');

$projectCode = $contract['project_code'] ?? 'Contrato';
$version = (int)($contract['version'] ?? 1);
$fileSafe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $projectCode . '_v' . $version) ?: 'Contrato';

function e_($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES); }

/**
 * Converte o Markdown do contrato em HTML diagramado.
 * As linhas de caixa (┌ │ └) viram títulos de cláusula centralizados;
 * numeração 1.1 / a) recebe recuo; **negrito** é respeitado.
 */
function render_contract_html(string $md): string
{
    $lines = preg_split('/\r\n|\r|\n/', $md);
    $html = '';
    $boxBuffer = [];
    $inBox = false;

    $flushBox = function () use (&$boxBuffer, &$html) {
        if (!$boxBuffer) return;
        $title = trim(implode(' ', $boxBuffer));
        $title = trim($title, " |");
        if ($title !== '') {
            $html .= '<div class="clause-title">' . e_($title) . '</div>';
        }
        $boxBuffer = [];
    };

    foreach ($lines as $raw) {
        $line = rtrim($raw);
        $t = trim($line);

        // Bordas das caixas de título de cláusula
        if (mb_strpos($t, '┌') !== false) { $inBox = true; $boxBuffer = []; continue; }
        if (mb_strpos($t, '└') !== false) { $inBox = false; $flushBox(); continue; }
        if ($inBox) {
            $content = trim(str_replace('│', '', $t));
            if ($content !== '') $boxBuffer[] = $content;
            continue;
        }

        // Comentários do modelo e marcadores de logo não vão para o corpo
        // (a logo aparece uma única vez no topo da página, à esquerda).
        if (str_starts_with($t, '<!--') || str_starts_with($t, '[LOGO]')) {
            continue;
        }

        if ($t === '') { $html .= '<div class="spacer"></div>'; continue; }

        $safe = e_($t);
        // negrito **...**
        $safe = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $safe);
        // CONTRATANTE / CONTRATADA em negrito
        $safe = preg_replace('/\b(CONTRATANTES?|CONTRATADA)\b/u', '<strong>$1</strong>', $safe);
        // marcadores de pendência destacados
        $safe = preg_replace('/(\[\[PENDENTE[^\]]*\]\])/', '<span class="pendente">$1</span>', $safe);

        if (preg_match('/^\d+\.\d+(\.\d+)?\.?\s/', $t)) {
            $html .= '<p class="item">' . $safe . '</p>';
        } elseif (preg_match('/^[a-z]\)\s/i', $t)) {
            $html .= '<p class="alinea">' . $safe . '</p>';
        } elseif (preg_match('/^_{3,}$/', $t) || strpos($t, '________') !== false) {
            $html .= '<div class="assinatura-linha">' . $safe . '</div>';
        } else {
            $html .= '<p class="corpo">' . $safe . '</p>';
        }
    }
    $flushBox();
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato — <?= e_($projectCode) ?> v<?= $version ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/html-docx-js/dist/html-docx.js"></script>
    <style>
        body { background:#f4f6f9; font-family:'Poppins', sans-serif; }
        .toolbar { text-align:center; margin:1rem 0 1.5rem; }
        .doc { background:#fff; max-width:820px; margin:0 auto 2rem; padding:2.5cm 2.5cm 2cm; box-shadow:0 4px 20px rgba(0,0,0,.12); }
        .doc-header { text-align:left; margin-bottom:1.6rem; }
        .doc-header img { max-height:52px; }
        .clause-title { text-align:center; font-weight:700; text-transform:uppercase; margin:1.4rem 0 .9rem; font-size:12pt; letter-spacing:.5px; }
        .doc p { font-size:12pt; line-height:1.7; text-align:justify; margin:0 0 8pt; color:#000; }
        .doc p.alinea { padding-left:1.8rem; text-indent:0; }
        .pendente { background:#ffe08a; color:#8a5a00; font-weight:600; padding:0 3px; border-radius:3px; }
        .assinatura-linha { text-align:center; margin-top:1.2rem; white-space:pre; font-family:monospace; }
        .spacer { height:6pt; }
        @media print { .toolbar { display:none; } body { background:#fff; } .doc { box-shadow:none; margin:0; max-width:100%; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-primary" id="btnPdf"><i class="bi bi-file-earmark-pdf"></i> Baixar PDF</button>
        <button class="btn btn-dark" id="btnDocx"><i class="bi bi-file-earmark-word"></i> Baixar DOCX</button>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <a class="btn btn-outline-secondary" href="/admin/contracts/show/<?= (int)($contract['id'] ?? 0) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="doc" id="docContent">
        <?php if (!empty($logoUrl)): ?>
        <div class="doc-header">
            <img src="<?= e_($logoUrl) ?>" alt="Logo">
        </div>
        <?php endif; ?>
        <?= render_contract_html($markdown) ?>
    </div>

    <script>
        const fileName = '<?= $fileSafe ?>';
        const docEl = document.getElementById('docContent');

        document.getElementById('btnPdf').addEventListener('click', async function () {
            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando…';
            try {
                await buildPdf();
            } catch (e) {
                console.error(e);
                alert('Erro ao gerar o PDF.');
            }
            this.innerHTML = original;
            this.disabled = false;
        });

        // Paginação por blocos: rasteriza cada elemento e nunca corta um bloco
        // no meio; quando não cabe na página atual, abre uma nova. Blocos mais
        // altos que uma página são fatiados por linhas de pixel (fallback).
        async function buildPdf() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageW = pdf.internal.pageSize.getWidth();
            const pageH = pdf.internal.pageSize.getHeight();
            const marginX = 22;                      // margem lateral (mm)
            const marginY = 22;                      // margem sup/inf (mm)
            const contentW = pageW - marginX * 2;
            const usableH = pageH - marginY * 2;     // altura útil por página em mm
            const gap = 7;                           // respiro entre parágrafos/itens (mm)
            const gapItem = 9;                       // respiro antes de item numerado (1.1, 3.2.1…)
            const gapBeforeTitle = 14;               // respiro extra antes de títulos de cláusula
            const gapAfterTitle = 5;                 // respiro depois do título de cláusula
            let cursorY = marginY;
            let prevWasTitle = false;

            const blocks = Array.from(docEl.children);

            for (const el of blocks) {
                if (!el.textContent.trim() && !el.querySelector('img')) continue;

                const isTitle = el.classList.contains('clause-title');
                const isHeader = el.classList.contains('doc-header');
                const isItem = el.classList.contains('item');

                const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                const imgH = canvas.height * contentW / canvas.width; // altura do bloco em mm

                // respiro antes deste bloco
                if (cursorY > marginY) {
                    if (isTitle) cursorY += gapBeforeTitle;
                    else if (prevWasTitle) cursorY += gapAfterTitle;
                    else if (isItem) cursorY += gapItem;
                }

                if (imgH <= usableH) {
                    // Bloco cabe inteiro: nova página se não couber no resto.
                    if (cursorY + imgH > pageH - marginY) {
                        pdf.addPage();
                        cursorY = marginY;
                    }
                    pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', marginX, cursorY, contentW, imgH);
                    cursorY += imgH + (isHeader ? gap * 2 : gap);
                    prevWasTitle = isTitle;
                } else {
                    // Bloco maior que a página: fatia o canvas em pedaços de página.
                    if (cursorY > marginY) { pdf.addPage(); cursorY = marginY; }
                    const pxPerMm = canvas.width / contentW;
                    const pageHpx = Math.floor(usableH * pxPerMm);
                    let offset = 0;
                    while (offset < canvas.height) {
                        const sliceHpx = Math.min(pageHpx, canvas.height - offset);
                        const part = document.createElement('canvas');
                        part.width = canvas.width;
                        part.height = sliceHpx;
                        const ctx = part.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, part.width, part.height);
                        ctx.drawImage(canvas, 0, offset, canvas.width, sliceHpx, 0, 0, canvas.width, sliceHpx);
                        const sliceHmm = sliceHpx / pxPerMm;
                        if (offset > 0) { pdf.addPage(); }
                        pdf.addImage(part.toDataURL('image/jpeg', 0.95), 'JPEG', marginX, marginY, contentW, sliceHmm);
                        offset += sliceHpx;
                    }
                    cursorY = marginY + ((canvas.height % pageHpx) / pxPerMm) + gap;
                    if (cursorY > pageH - marginY) { pdf.addPage(); cursorY = marginY; }
                    prevWasTitle = false;
                }
            }

            pdf.save(fileName + '.pdf');
        }

        document.getElementById('btnDocx').addEventListener('click', function () {
            const header = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' +
                'body{font-family:Poppins,Arial,sans-serif;font-size:12pt;}' +
                'p{text-align:justify;line-height:1.5;margin:0 0 6pt;}' +
                '.clause-title{text-align:center;font-weight:bold;text-transform:uppercase;margin:14px 0 8px;}' +
                '.alinea{margin-left:24px;}.pendente{background:#ffe08a;font-weight:bold;}' +
                '.assinatura-linha{text-align:center;margin-top:16px;}' +
                '</style></head><body>';
            const html = header + docEl.innerHTML + '</body></html>';
            const blob = window.htmlDocx.asBlob(html);
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = fileName + '.docx';
            a.click();
            URL.revokeObjectURL(a.href);
        });
    </script>
</body>
</html>
