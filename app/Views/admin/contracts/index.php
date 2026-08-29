<?php $pageTitle = 'Elaboração de Contrato'; $currentPage = 'contracts'; ?>
<?php ob_start(); ?>

<?php
function c_date($v): string { if (empty($v)) return '—'; $t = strtotime((string)$v); return $t ? date('d/m/Y H:i', $t) : '—'; }
function c_status_badge(string $s): string {
    return match ($s) {
        'exported'  => '<span class="badge bg-success">Exportado</span>',
        'generated' => '<span class="badge bg-primary">Gerado</span>',
        default     => '<span class="badge bg-secondary">Rascunho</span>',
    };
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-ruled"></i> Elaboração de Contrato</h5>
    <a href="/admin/contracts/wizard" class="btn btn-primary">
        <i class="bi bi-magic"></i> Novo Contrato
    </a>
</div>

<?php $contractTab = 'contracts'; require __DIR__ . '/_subnav.php'; ?>

<div class="alert alert-light border small">
    <i class="bi bi-info-circle"></i>
    Faça upload do PDF da Proposta Comercial e o sistema monta o Contrato de Empreitada preenchido,
    seguindo o modelo-base da empresa. Você pode <strong>salvar como rascunho</strong> e continuar depois;
    cada geração cria uma nova versão — o histórico nunca é sobrescrito.
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Projeto</th>
                    <th>Versão</th>
                    <th class="d-none d-md-table-cell">Modelo-base</th>
                    <th class="d-none d-md-table-cell">Origem (revisão)</th>
                    <th>Validação</th>
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Gerado por</th>
                    <th class="d-none d-md-table-cell">Data</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        Nenhum contrato gerado ainda. Clique em <strong>Novo Contrato</strong> para começar.
                    </td></tr>
                <?php else: foreach ($contracts as $c):
                    $val = json_decode($c['validation_json'] ?? 'null', true);
                    $blocked = !empty($val['blocked']);
                    $issues = $val['issues'] ?? [];
                    $isDraft = ($c['status'] ?? '') === 'draft';
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($c['project_code'] ?? '—') ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($c['project_name'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if ($isDraft): ?>
                                <span class="badge bg-secondary"><i class="bi bi-pencil"></i> rascunho</span>
                            <?php else: ?>
                                <span class="badge bg-dark">v<?= (int)$c['version'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell small"><?= htmlspecialchars($c['template_name'] ?? '—') ?></td>
                        <td class="d-none d-md-table-cell small"><?= htmlspecialchars($c['proposal_revision'] ?? '—') ?></td>
                        <td>
                            <?php if ($val === null): ?>
                                <span class="text-muted small">—</span>
                            <?php elseif ($blocked): ?>
                                <span class="badge bg-danger" title="<?= (int)count($issues) ?> problema(s)"><i class="bi bi-x-octagon"></i> Bloqueado</span>
                            <?php elseif (!empty($issues)): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> <?= (int)count($issues) ?> alerta(s)</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-check2"></i> OK</span>
                            <?php endif; ?>
                        </td>
                        <td><?= c_status_badge($c['status'] ?? 'draft') ?></td>
                        <td class="d-none d-lg-table-cell small"><?= htmlspecialchars($c['created_by_name'] ?? '—') ?></td>
                        <td class="d-none d-md-table-cell small text-muted"><?= c_date($c['created_at'] ?? null) ?></td>
                        <td class="text-end">
                            <?php if ($isDraft): ?>
                                <a href="/admin/contracts/wizard/<?= (int)$c['id'] ?>" class="btn btn-sm btn-primary" title="Continuar edição">
                                    <i class="bi bi-pencil-square"></i> Continuar
                                </a>
                            <?php else: ?>
                                <a href="/admin/contracts/show/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Abrir e editar"><i class="bi bi-pencil-square"></i></a>
                                <a href="/admin/contracts/wizard/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-info" title="Reabrir dados e regerar"><i class="bi bi-arrow-repeat"></i></a>
                                <a href="/admin/contracts/export/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Exportar"><i class="bi bi-download"></i></a>
                            <?php endif; ?>
                            <form method="POST" action="/admin/contracts/delete" class="d-inline" onsubmit="return confirm('<?= $isDraft ? 'Excluir este rascunho?' : 'Excluir esta versão do contrato?' ?>');">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
