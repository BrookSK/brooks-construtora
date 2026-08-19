<?php $pageTitle = 'Boas-vindas'; $currentPage = 'welcome'; ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Card principal de boas-vindas -->
        <div class="card text-center py-5 px-4 mb-4">
            <div class="mb-4">
                <div style="width:80px;height:80px;background:var(--color-primary);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="bi bi-hand-wave-fill text-white" style="font-size:2rem;"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-1" style="color:var(--color-primary);">
                Olá, <?= htmlspecialchars($user['name'] ?? 'Usuário') ?>!
            </h2>
            <p class="text-muted mb-0 fs-5">
                Bem-vindo(a) ao painel administrativo da <strong>Brooks Construtora</strong>.
            </p>
        </div>

        <!-- Cards informativos -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center">
                    <i class="bi bi-grid-1x2-fill fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Dashboard</h6>
                    <p class="text-muted small mb-3">Visualize as métricas e o resumo geral do sistema.</p>
                    <a href="/admin/dashboard" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center">
                    <i class="bi bi-cart3 fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Pedidos</h6>
                    <p class="text-muted small mb-3">Gerencie pedidos de materiais e acompanhe cotações.</p>
                    <a href="/admin/orders" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center">
                    <i class="bi bi-gear fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Configurações</h6>
                    <p class="text-muted small mb-3">Ajuste as preferências e parâmetros do sistema.</p>
                    <a href="/admin/settings" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
        </div>

        <!-- Info do usuário -->
        <div class="card p-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-person-circle me-2"></i>Sua conta</h6>
            <div class="row g-2">
                <div class="col-sm-6">
                    <span class="text-muted small">Nome</span>
                    <div class="fw-medium"><?= htmlspecialchars($user['name'] ?? '-') ?></div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">E-mail</span>
                    <div class="fw-medium"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
                </div>
                <div class="col-sm-6 mt-2">
                    <span class="text-muted small">Perfil</span>
                    <div>
                        <span class="badge bg-primary"><?= htmlspecialchars($user['role'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
