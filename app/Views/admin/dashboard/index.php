<?php $pageTitle = 'Dashboard'; $currentPage = 'dashboard'; ob_start(); ?>

<?php if (\App\Core\Auth::hasPermission('magazines')): ?>
<!-- Stats gerais (revistas/newsletter) -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="stat-number"><?= $totalSubscribers ?? 0 ?></div>
            <div class="text-muted">Inscritos Newsletter</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="stat-number"><?= $publishedMagazines ?? 0 ?></div>
            <div class="text-muted">Revistas Publicadas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="stat-number"><?= $pendingMagazines ?? 0 ?></div>
            <div class="text-muted">Revistas Pendentes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3">
            <div class="stat-number"><?= $totalUsers ?? 0 ?></div>
            <div class="text-muted">Usuários</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($totalOrders)): ?>
<!-- Stats de pedidos -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #ffc107;">
            <div class="stat-number"><?= $totalOrders ?? 0 ?></div>
            <div class="text-muted">Total Pedidos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #fd7e14;">
            <div class="stat-number"><?= $pendingQuoteOrders ?? 0 ?></div>
            <div class="text-muted">Aguard. Cotação</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #0dcaf0;">
            <div class="stat-number"><?= $pendingApprovalOrders ?? 0 ?></div>
            <div class="text-muted">Aguard. Aprovação</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #28a745;">
            <div class="stat-number"><?= $approvedOrders ?? 0 ?></div>
            <div class="text-muted">Aprovados</div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <?php if (\App\Core\Auth::hasPermission('magazines')): ?>
    <!-- Revistas recentes (só quem tem permissão) -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Revistas Recentes</h6>
                <a href="/admin/magazines" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentMagazines)): ?>
                    <p class="text-muted text-center py-3">Nenhuma revista publicada ainda.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead><tr><th>Título</th><th>Status</th><th>Data</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentMagazines as $mag): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mag['title']) ?></td>
                                    <td><span class="badge bg-<?= $mag['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($mag['status']) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($mag['published_at'] ?? $mag['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
    <?php else: ?>
    <div class="col-12">
    <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Ações Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (\App\Core\Auth::hasPermission('orders')): ?>
                    <a href="/admin/orders/create" class="btn btn-outline-primary">
                        <i class="bi bi-cart-plus"></i> Novo Pedido
                    </a>
                    <a href="/admin/orders" class="btn btn-outline-primary">
                        <i class="bi bi-cart3"></i> Ver Pedidos
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('suppliers')): ?>
                    <a href="/admin/suppliers" class="btn btn-outline-secondary">
                        <i class="bi bi-building"></i> Fornecedores
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('materials')): ?>
                    <a href="/admin/materials" class="btn btn-outline-secondary">
                        <i class="bi bi-box-seam"></i> Materiais
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('magazines')): ?>
                    <a href="/admin/magazines/topics" class="btn btn-outline-primary">
                        <i class="bi bi-lightbulb"></i> Gerar Temas
                    </a>
                    <a href="/admin/magazines" class="btn btn-outline-primary">
                        <i class="bi bi-journal-richtext"></i> Ver Revistas
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('newsletter')): ?>
                    <a href="/admin/newsletter" class="btn btn-outline-primary">
                        <i class="bi bi-envelope-paper"></i> Newsletter
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('settings')): ?>
                    <a href="/admin/settings" class="btn btn-outline-secondary">
                        <i class="bi bi-gear"></i> Configurações
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
