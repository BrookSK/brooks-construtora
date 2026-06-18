<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?= htmlspecialchars($order['code']) ?> - PDF | Brooks Construtora</title>
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
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .pdf-header {
            border-bottom: 3px solid #3a3b4e;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .pdf-header .company-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #3a3b4e;
            letter-spacing: 1px;
        }
        .pdf-header .doc-title {
            font-size: 1rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        .info-grid .info-item label {
            font-size: 0.7rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }
        .info-grid .info-item span {
            display: block;
            font-weight: 600;
            color: #333;
        }
        .items-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 0.85rem; }
        .items-table th { background: #3a3b4e; color: #fff; padding: 8px 10px; text-align: left; font-size: 0.75rem; text-transform: uppercase; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .items-table tr:last-child td { border-bottom: none; }
        .items-table .total-row { background: #f8f9fa; font-weight: 700; }
        .items-table .total-row td { border-top: 2px solid #3a3b4e; }
        .approval-section { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 6px; padding: 1.2rem; margin-top: 1.5rem; }
        .approval-section h6 { color: #2e7d32; margin-bottom: 0.5rem; }
        .history-section { margin-top: 1.5rem; font-size: 0.8rem; }
        .history-section .timeline-item { padding: 0.5rem 0; border-left: 2px solid #ddd; padding-left: 1rem; margin-left: 0.5rem; }
        .history-section .timeline-item:last-child { border-left-color: #28a745; }
        .pdf-footer { border-top: 1px solid #ddd; padding-top: 1rem; margin-top: 2rem; text-align: center; font-size: 0.7rem; color: #999; }
        .download-bar { text-align: center; margin: 1rem 0 2rem; }
        @media print {
            .download-bar { display: none; }
            body { background: #fff; }
            .pdf-container { box-shadow: none; margin: 0; padding: 1rem; }
        }
        @media (max-width: 768px) {
            .pdf-container { padding: 1.5rem; margin: 1rem; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="download-bar">
        <button class="btn btn-primary btn-lg" id="downloadPdfBtn">
            <i class="bi bi-download"></i> Baixar PDF
        </button>
        <button class="btn btn-outline-secondary btn-lg ms-2" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>

    <div class="pdf-container" id="pdfContent">
        <!-- Header -->
        <div class="pdf-header d-flex justify-content-between align-items-center">
            <div>
                <div class="company-name">BROOKS CONSTRUTORA</div>
                <div class="doc-title">Formalização de Pedido de Materiais</div>
            </div>
            <div class="text-end">
                <div style="font-size:1.4rem; font-weight:700; color:#3a3b4e;"><?= htmlspecialchars($order['code']) ?></div>
                <div style="font-size:0.8rem; color:#28a745; font-weight:600;">✓ APROVADO</div>
            </div>
        </div>

        <!-- Informações -->
        <div class="info-grid">
            <div class="info-item">
                <label>Fornecedor</label>
                <span><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <label>Data do Pedido</label>
                <span><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-item">
                <label>Solicitado por</label>
                <span><?= htmlspecialchars($order['created_by_name']) ?></span>
            </div>
            <div class="info-item">
                <label>Cotado por</label>
                <span><?= htmlspecialchars($order['quoted_by_name'] ?? '-') ?> <?= $order['quoted_at'] ? '(' . date('d/m/Y', strtotime($order['quoted_at'])) . ')' : '' ?></span>
            </div>
            <?php if (!empty($order['supplier_cnpj'])): ?>
            <div class="info-item">
                <label>CNPJ Fornecedor</label>
                <span><?= htmlspecialchars($order['supplier_cnpj']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <label>Valor Total</label>
                <span style="color:#28a745; font-size:1.1rem;">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></span>
            </div>
        </div>

        <?php if (!empty($order['description'])): ?>
        <div class="mb-3 p-2 bg-light rounded" style="font-size:0.85rem;">
            <strong>Observações:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?>
        </div>
        <?php endif; ?>

        <!-- Itens -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Material</th>
                    <th>Espec.</th>
                    <th>Class.</th>
                    <th>Unid.</th>
                    <th style="text-align:center;">Qtd</th>
                    <th style="text-align:right;">Valor Unit.</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                    <td><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                    <td style="text-align:center;"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                    <td style="text-align:right;">R$ <?= number_format($item['unit_price'] ?? 0, 2, ',', '.') ?></td>
                    <td style="text-align:right;">R$ <?= number_format($item['total_price'] ?? 0, 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="7" style="text-align:right;">TOTAL:</td>
                    <td style="text-align:right; color:#28a745;">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Aprovação -->
        <div class="approval-section">
            <h6><i class="bi bi-check-circle-fill"></i> Aprovação</h6>
            <div class="row">
                <div class="col-sm-6">
                    <small class="text-muted">Aprovado por:</small><br>
                    <strong><?= htmlspecialchars($order['approved_by_name'] ?? '-') ?></strong>
                </div>
                <div class="col-sm-6">
                    <small class="text-muted">Data da Aprovação:</small><br>
                    <strong><?= $order['approved_at'] ? date('d/m/Y \à\s H:i', strtotime($order['approved_at'])) : '-' ?></strong>
                </div>
            </div>
            <?php if (!empty($order['approval_notes'])): ?>
            <div class="mt-2">
                <small class="text-muted">Observações:</small><br>
                <?= nl2br(htmlspecialchars($order['approval_notes'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Histórico -->
        <div class="history-section">
            <h6><i class="bi bi-clock-history"></i> Histórico</h6>
            <?php foreach ($history as $entry): ?>
            <div class="timeline-item">
                <strong><?= htmlspecialchars($entry['performed_by_name'] ?? 'Sistema') ?></strong>
                <span class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></span><br>
                <small><?= htmlspecialchars($entry['description']) ?></small>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="pdf-footer">
            <p>Brooks Construtora — Av. Brigadeiro Faria Lima, 1811 — São Paulo/SP</p>
            <p>Documento gerado em <?= date('d/m/Y \à\s H:i') ?> | www.brooksconstrutora.com.br</p>
        </div>
    </div>

    <script>
    document.getElementById('downloadPdfBtn').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando PDF...';

        try {
            const element = document.getElementById('pdfContent');
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
            });

            const { jsPDF } = window.jspdf;
            const imgWidth = 210; // A4 width in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            // Se o conteúdo for maior que uma página A4
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

            pdf.save('Pedido_<?= $order['code'] ?>.pdf');
        } catch (e) {
            alert('Erro ao gerar PDF: ' + e.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download"></i> Baixar PDF';
    });
    </script>
</body>
</html>
