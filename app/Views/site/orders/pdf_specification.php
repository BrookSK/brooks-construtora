<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiais por Especificação - <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .pdf-container {
            background: #fff;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .pdf-header {
            border-bottom: 3px solid #3a3b4e;
            padding-bottom: 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .pdf-header .company-name { font-size: 1.4rem; font-weight: 700; color: #3a3b4e; letter-spacing: 1px; }
        .pdf-header .doc-title { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.5rem; }
        .info-grid .info-item label { font-size: 0.65rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0; display: block; }
        .info-grid .info-item span { display: block; font-weight: 600; color: #333; font-size: 0.9rem; }

        .spec-group { margin-bottom: 1.5rem; page-break-inside: avoid; }
        .spec-group-title {
            background: #3a3b4e;
            color: #fff;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
            margin-bottom: 0;
        }
        .spec-group-count {
            font-weight: 400;
            font-size: 0.7rem;
            opacity: 0.8;
            margin-left: 8px;
        }

        .spec-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; margin-bottom: 0; }
        .spec-table th { background: #f0f1f3; color: #555; padding: 6px 8px; text-align: left; font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid #ddd; }
        .spec-table td { padding: 7px 8px; border-bottom: 1px solid #eee; }
        .spec-table tr:last-child td { border-bottom: none; }

        .pdf-footer { border-top: 1px solid #ddd; padding-top: 1rem; margin-top: 2rem; text-align: center; font-size: 0.65rem; color: #999; }
        .download-bar { text-align: center; margin: 1rem 0 2rem; }

        @media print {
            .download-bar { display: none; }
            body { background: #fff; }
            .pdf-container { box-shadow: none; margin: 0; padding: 1.5rem; max-width: 100%; }
        }
        @media (max-width: 768px) {
            .pdf-container { padding: 1rem; margin: 0.5rem; border-radius: 6px; }
            .info-grid { grid-template-columns: 1fr; gap: 0.5rem; }
            .pdf-header { text-align: center; justify-content: center; flex-direction: column; }
            .pdf-header .company-name { font-size: 1.1rem; }
            .download-bar { margin: 0.5rem 0 1rem; }
            .download-bar .btn { font-size: 0.85rem; padding: 0.5rem 1rem; }
            .spec-table { font-size: 0.75rem; }
            .spec-table th, .spec-table td { padding: 5px 6px; }
        }
    </style>
</head>
<body>
    <div class="download-bar">
        <button class="btn btn-primary" id="downloadPdfBtn">
            <i class="bi bi-download"></i> Baixar PDF
        </button>
        <button class="btn btn-outline-secondary ms-2" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>

    <div class="pdf-container" id="pdfContent">
        <!-- Header -->
        <div class="pdf-header">
            <div>
                <div class="company-name">BROOKS CONSTRUTORA</div>
                <div class="doc-title">Relatório de Materiais por Especificação</div>
            </div>
            <div class="text-end">
                <div style="font-size:1.3rem; font-weight:700; color:#3a3b4e;"><?= htmlspecialchars($order['code']) ?></div>
                <div style="font-size:0.75rem; color:#28a745; font-weight:600;">&#10003; APROVADO</div>
            </div>
        </div>

        <!-- Informações -->
        <div class="info-grid">
            <div class="info-item">
                <label>Fornecedor(es)</label>
                <span>
                <?php
                $allApproved = \App\Models\PurchaseOrderSupplier::getAllApproved($order['id']);
                if (!empty($allApproved) && count($allApproved) > 1):
                    echo htmlspecialchars(implode(', ', array_column($allApproved, 'supplier_name')));
                elseif ($approvedSupplier):
                    echo htmlspecialchars($approvedSupplier['supplier_name']);
                else:
                    echo htmlspecialchars($order['supplier_name'] ?? 'N/A');
                endif;
                ?>
                </span>
            </div>
            <div class="info-item">
                <label>Data do Pedido</label>
                <span><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-item">
                <label>Solicitado por</label>
                <span><?= htmlspecialchars($order['created_by_name']) ?></span>
            </div>
            <?php if (!empty($order['construction_site_name'])): ?>
            <div class="info-item">
                <label>Obra</label>
                <span><?= htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Resumo -->
        <div style="margin-bottom:1.5rem; padding:10px 14px; background:#f8f9fa; border-radius:6px; font-size:0.8rem; color:#555;">
            <strong><?= count($grouped) ?></strong> especificação(ões) &bull;
            <strong><?= array_sum(array_map(fn($g) => count($g['items']), $grouped)) ?></strong> item(ns) no total
        </div>

        <!-- Grupos por especificação -->
        <?php
        foreach ($grouped as $key => $group):
        ?>
        <div class="spec-group">
            <div class="spec-group-title">
                <?= htmlspecialchars(mb_convert_case($group['label'], MB_CASE_TITLE, 'UTF-8')) ?>
                <span class="spec-group-count">(<?= count($group['items']) ?> <?= count($group['items']) === 1 ? 'item' : 'itens' ?>)</span>
            </div>
            <table class="spec-table">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:40%;">Material</th>
                        <th style="width:20%;">Especificação</th>
                        <th style="width:15%;">Classificação</th>
                        <th style="width:20%; text-align:center;">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($group['items'] as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                        <td><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                        <td style="text-align:center;"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2, ',', '.') ?> <?= htmlspecialchars($item['unit'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Footer -->
        <div class="pdf-footer">
            <p class="mb-1">Brooks Construtora</p>
            <p class="mb-0">Documento gerado em <?= date('d/m/Y \à\s H:i') ?> | Relatório por Especificação</p>
        </div>
    </div>

    <script>
    document.getElementById('downloadPdfBtn').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';

        try {
            const element = document.getElementById('pdfContent');

            // Forçar largura fixa para o PDF ficar bonito
            const originalWidth = element.style.width;
            element.style.width = '800px';

            const canvas = await html2canvas(element, { scale: 2, useCORS: true, logging: false, windowWidth: 900 });

            element.style.width = originalWidth;

            const { jsPDF } = window.jspdf;
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pageHeight = 297;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            pdf.save('Materiais_Especificacao_<?= $order['code'] ?>.pdf');
        } catch (e) {
            alert('Erro ao gerar PDF: ' + e.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download"></i> Baixar PDF';
    });
    </script>
</body>
</html>
