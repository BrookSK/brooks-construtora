<?php
// =====================================================================
// PDF de dados brutos do briefing (client-side via jsPDF/html2canvas)
// =====================================================================
$project    = $project    ?? [];
$briefing   = $briefing   ?? [];
$contractor = $contractor ?? null;

function e_($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES); }
function doc_($v): string { $d=preg_replace('/\D/','',(string)($v??'')); if(strlen($d)===11)return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/','$1.$2.$3-$4',$d); if(strlen($d)===14)return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/','$1.$2.$3/$4-$5',$d); return e_($v); }
function phone_($v): string { $d=preg_replace('/\D/','',(string)($v??'')); if(strlen($d)===11)return preg_replace('/(\d{2})(\d{5})(\d{4})/','($1) $2-$3',$d); if(strlen($d)===10)return preg_replace('/(\d{2})(\d{4})(\d{4})/','($1) $2-$3',$d); return e_($v); }
function cep_($v): string { $d=preg_replace('/\D/','',(string)($v??'')); if(strlen($d)===8)return preg_replace('/(\d{5})(\d{3})/','$1-$2',$d); return e_($v); }
function date_($v): string { if(empty($v))return '—'; $dt=\DateTime::createFromFormat('Y-m-d',substr((string)$v,0,10)); return $dt?$dt->format('d/m/Y'):e_($v); }
function money_($v): string { if($v===null||$v===''||(float)$v==0)return '—'; return 'R$ '.number_format((float)$v,2,',','.'); }
function pct_($v): string { if($v===null||$v===''||(float)$v==0)return '—'; return number_format((float)$v,2,',','.').'%'; }
function val_($v): string { $v=trim((string)($v??'')); return $v===''?'—':e_($v); }

$clientName = $project['client_name'] ?? 'Briefing';
$fileSafe = preg_replace('/[^A-Za-z0-9_-]+/','_', $clientName) ?: 'Briefing';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Briefing — <?= e_($clientName) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { background:#f4f6f9; font-family:'Segoe UI', sans-serif; }
        .pdf-container { background:#fff; max-width:820px; margin:1.5rem auto; padding:2.5rem; box-shadow:0 4px 20px rgba(0,0,0,.1); border-radius:8px; }
        .pdf-header { border-bottom:3px solid #3a3b4e; padding-bottom:1rem; margin-bottom:1.5rem; }
        .pdf-header .company-name { font-size:1.4rem; font-weight:700; color:#3a3b4e; letter-spacing:1px; }
        .pdf-header .doc-title { font-size:.78rem; color:#666; text-transform:uppercase; letter-spacing:1px; }
        .section-title { background:#3a3b4e; color:#fff; padding:6px 12px; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; border-radius:4px; margin:1.4rem 0 .8rem; }
        .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:.6rem 1.4rem; }
        .field-grid.one { grid-template-columns:1fr; }
        .field label { font-size:.62rem; color:#888; text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:1px; }
        .field span { display:block; font-weight:600; color:#333; font-size:.86rem; white-space:pre-wrap; word-break:break-word; }
        .pdf-footer { border-top:1px solid #ddd; padding-top:1rem; margin-top:2rem; text-align:center; font-size:.65rem; color:#999; }
        .download-bar { text-align:center; margin:1rem 0 2rem; }
        @media print { .download-bar { display:none; } body { background:#fff; } .pdf-container { box-shadow:none; margin:0; max-width:100%; } }
    </style>
</head>
<body>
    <div class="download-bar">
        <button class="btn btn-primary" id="downloadPdfBtn"><i class="bi bi-download"></i> Baixar PDF</button>
        <button class="btn btn-outline-secondary ms-2" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <a class="btn btn-outline-secondary ms-2" href="/admin/briefing/edit/<?= (int)($project['id'] ?? 0) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="pdf-container" id="pdfContent">
        <div class="pdf-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="company-name">BROOKS CONSTRUTORA</div>
                <div class="doc-title">Briefing — Dados do Formulário</div>
            </div>
            <div class="text-end doc-title">Emitido em <?= date('d/m/Y H:i') ?></div>
        </div>

        <div class="section-title">Dados do Contratante</div>
        <div class="field-grid">
            <div class="field"><label>Nome / Razão Social</label><span><?= val_($project['client_name'] ?? '') ?></span></div>
            <div class="field"><label>CPF / CNPJ</label><span><?= doc_($project['client_document'] ?? '') ?: '—' ?></span></div>
            <div class="field"><label>Telefone</label><span><?= phone_($project['client_phone'] ?? '') ?: '—' ?></span></div>
            <div class="field"><label>E-mail</label><span><?= val_($project['client_email'] ?? '') ?></span></div>
        </div>

        <div class="section-title">Informações da Obra</div>
        <div class="field-grid">
            <div class="field"><label>Tipo</label><span><?= val_($project['project_type'] ?? '') ?></span></div>
            <div class="field"><label>Área (m²)</label><span><?= val_($project['project_area'] ?? '') ?></span></div>
            <div class="field"><label>Nº Projeto</label><span><?= val_($briefing['project_number'] ?? '') ?></span></div>
            <div class="field"><label>CEP</label><span><?= cep_($project['project_cep'] ?? '') ?: '—' ?></span></div>
            <div class="field"><label>Endereço</label><span><?= val_($project['project_address'] ?? '') ?></span></div>
            <div class="field"><label>Número</label><span><?= val_($project['project_address_number'] ?? '') ?></span></div>
            <div class="field"><label>Complemento</label><span><?= val_($project['project_complement'] ?? '') ?></span></div>
            <div class="field"><label>Bairro</label><span><?= val_($project['project_neighborhood'] ?? '') ?></span></div>
            <div class="field"><label>Cidade</label><span><?= val_($project['project_city'] ?? '') ?></span></div>
            <div class="field"><label>UF</label><span><?= val_($project['project_state'] ?? '') ?></span></div>
        </div>
        <div class="field-grid one" style="margin-top:.6rem;">
            <div class="field"><label>Objetivo</label><span><?= val_($project['project_goal'] ?? '') ?></span></div>
        </div>

        <div class="section-title">Briefing da Negociação</div>
        <div class="field-grid">
            <div class="field"><label>Preferências</label><span><?= val_($briefing['preferences'] ?? '') ?></span></div>
            <div class="field"><label>Prioridades</label><span><?= val_($briefing['priorities'] ?? '') ?></span></div>
            <div class="field"><label>Necessidades</label><span><?= val_($briefing['needs'] ?? '') ?></span></div>
            <div class="field"><label>Restrições</label><span><?= val_($briefing['restrictions'] ?? '') ?></span></div>
        </div>
        <div class="field-grid one" style="margin-top:.6rem;">
            <div class="field"><label>Resumo</label><span><?= val_($briefing['briefing_summary'] ?? '') ?></span></div>
            <div class="field"><label>Detalhes da Negociação</label><span><?= val_($briefing['negotiation_details'] ?? '') ?></span></div>
        </div>

        <div class="section-title">Condições Comerciais</div>
        <div class="field-grid">
            <div class="field"><label>Valor Total</label><span><?= money_($briefing['contract_value'] ?? null) ?></span></div>
            <div class="field"><label>Entrada</label><span><?= money_($briefing['down_payment'] ?? null) ?></span></div>
            <div class="field"><label>Desconto (R$)</label><span><?= money_($briefing['discount_value'] ?? null) ?></span></div>
            <div class="field"><label>Desconto (%)</label><span><?= pct_($briefing['discount_percent'] ?? null) ?></span></div>
            <div class="field"><label>Forma de Pagamento</label><span><?= val_($briefing['payment_method'] ?? '') ?></span></div>
            <div class="field"><label>Parcelas</label><span><?= val_($briefing['payment_installments'] ?? '') ?></span></div>
            <div class="field"><label>Detalhes do Parcelamento</label><span><?= val_($briefing['payment_details'] ?? '') ?></span></div>
            <div class="field"><label>Início</label><span><?= date_($briefing['start_date'] ?? '') ?></span></div>
            <div class="field"><label>Conclusão</label><span><?= date_($briefing['end_date'] ?? '') ?></span></div>
            <div class="field"><label>Prazo (dias)</label><span><?= val_($briefing['deadline_days'] ?? '') ?></span></div>
            <div class="field"><label>Responsável</label><span><?= val_($briefing['responsible_name'] ?? '') ?></span></div>
            <div class="field"><label>Cargo</label><span><?= val_($briefing['responsible_role'] ?? '') ?></span></div>
        </div>
        <div class="field-grid one" style="margin-top:.6rem;">
            <div class="field"><label>Cláusulas</label><span><?= val_($briefing['clauses'] ?? '') ?></span></div>
        </div>

        <?php if ($contractor): ?>
        <div class="section-title">Empresa Contratada</div>
        <div class="field-grid">
            <div class="field"><label>Razão Social</label><span><?= val_($contractor['company_name'] ?? '') ?></span></div>
            <div class="field"><label>Nome Fantasia</label><span><?= val_($contractor['trade_name'] ?? '') ?></span></div>
            <div class="field"><label>CNPJ</label><span><?= doc_($contractor['cnpj'] ?? '') ?: '—' ?></span></div>
            <div class="field"><label>Telefone</label><span><?= phone_($contractor['phone'] ?? '') ?: '—' ?></span></div>
            <div class="field"><label>Endereço</label><span><?= val_($contractor['address'] ?? '') ?></span></div>
            <div class="field"><label>Cidade / UF</label><span><?= val_($contractor['city'] ?? '') ?>/<?= val_($contractor['state'] ?? '') ?></span></div>
            <div class="field"><label>Representante</label><span><?= val_($contractor['representative_name'] ?? '') ?></span></div>
            <div class="field"><label>Cargo do Representante</label><span><?= val_($contractor['representative_role'] ?? '') ?></span></div>
        </div>
        <?php endif; ?>

        <div class="pdf-footer">Documento gerado automaticamente pelo sistema Brooks Construtora — dados brutos do formulário de briefing.</div>
    </div>

    <script>
    document.getElementById('downloadPdfBtn').addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';
        try {
            const element = document.getElementById('pdfContent');
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
            pdf.save('Briefing_<?= $fileSafe ?>.pdf');
        } catch (e) {
            alert('Erro ao gerar PDF: ' + e.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download"></i> Baixar PDF';
    });
    </script>
</body>
</html>
