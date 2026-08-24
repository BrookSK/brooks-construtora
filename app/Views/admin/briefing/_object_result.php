<?php
// Partial incluído dentro da aba 3 quando $contractObject já existe no banco.
// Variáveis esperadas: $contractObject (array do model ContractObject)
if (empty($contractObject)) return;

$statusLabels = [
    'generated' => ['label' => 'Gerado',   'class' => 'bg-warning text-dark'],
    'approved'  => ['label' => 'Aprovado', 'class' => 'bg-success'],
    'rejected'  => ['label' => 'Rejeitado','class' => 'bg-danger'],
];
$statusInfo = $statusLabels[$contractObject['status'] ?? 'generated'] ?? $statusLabels['generated'];
?>

<div class="card mb-4" id="object-card-<?= (int)$contractObject['id'] ?>">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-check me-2"></i>Objeto do Contrato
            <span id="obj-status-badge" class="badge <?= $statusInfo['class'] ?> ms-2">
                <?= $statusInfo['label'] ?>
            </span>
        </h6>
        <div class="d-flex gap-2 flex-wrap">
            <small class="text-muted align-self-center">
                <?= date('d/m/Y H:i', strtotime($contractObject['created_at'])) ?>
            </small>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="copyObjectText()" title="Copiar texto">
                <i class="bi bi-clipboard"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="downloadObjectPdf()" title="Baixar PDF">
                <i class="bi bi-file-earmark-pdf"></i>
            </button>
            <?php if (($contractObject['status'] ?? '') !== 'approved'): ?>
            <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="goToStep(2)">
                <i class="bi bi-arrow-repeat me-1"></i> Re-gerar
            </button>
            <button type="button" id="btn-approve" class="btn btn-sm btn-success"
                    onclick="approveObject()">
                <i class="bi bi-check2-circle me-1"></i> Aprovar
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div id="object-text-display"><?php
            $rawText = $contractObject['generated_text'] ?? '';
            // Converte markdown básico para HTML formatado
            $html = htmlspecialchars($rawText, ENT_QUOTES);
            // Negrito: **texto** → <strong>texto</strong>
            $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
            // Itálico: *texto* → <em>texto</em>  (somente se não for negrito)
            $html = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $html);
            // Parágrafos: linha dupla vira separação visual
            $html = str_replace("\n\n", '</p><p>', $html);
            // Quebras simples viram <br>
            $html = str_replace("\n", '<br>', $html);
            // Envolve em parágrafo
            $html = '<p>' . $html . '</p>';
            echo $html;
        ?></div>
        <span class="expand-toggle" id="expand-object-btn" onclick="toggleExpandObject()">
            <i class="bi bi-chevron-down me-1"></i> Ver contrato completo
        </span>
    </div>
</div>

<script>
// Disponibiliza o texto para cópia (carregado do banco)
document.getElementById('object-result-wrapper').dataset.objectText = <?= json_encode($contractObject['generated_text'] ?? '') ?>;
document.getElementById('object-result-wrapper').dataset.objectId   = <?= (int)$contractObject['id'] ?>;
</script>
