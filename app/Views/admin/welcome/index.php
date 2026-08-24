<?php $pageTitle = 'Boas-vindas'; $currentPage = 'welcome'; ob_start(); ?>

<?php
$_profilePhoto  = $profile['photo'] ?? '';
$_userName      = htmlspecialchars($profile['name'] ?? $user['name'] ?? 'Usuário');
$_userInitial   = mb_strtoupper(mb_substr($profile['name'] ?? $user['name'] ?? 'U', 0, 1));
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Card principal de boas-vindas -->
        <div class="card mb-4 overflow-hidden" style="border:none;box-shadow:0 2px 12px rgba(58,59,78,.10);">
            <!-- Faixa decorativa superior -->
            <div style="height:6px;background:linear-gradient(90deg,var(--color-primary) 0%,var(--color-accent) 100%);"></div>

            <div class="card-body py-5 px-4 text-center" style="background:linear-gradient(160deg,#f8f9fc 0%,#ffffff 100%);">
                <!-- Avatar -->
                <div class="mb-4 d-inline-block">
                    <div style="width:96px;height:96px;border-radius:50%;overflow:hidden;margin:0 auto;background:var(--color-primary);display:flex;align-items:center;justify-content:center;border:4px solid #fff;box-shadow:0 4px 16px rgba(58,59,78,.18);">
                        <?php if (!empty($_profilePhoto)): ?>
                            <img src="<?= htmlspecialchars($_profilePhoto) ?>"
                                 alt="Foto de perfil"
                                 style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <span style="color:#fff;font-size:2.2rem;font-weight:700;line-height:1;user-select:none;">
                                <?= $_userInitial ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Saudação -->
                <h2 class="fw-bold mb-1" style="color:var(--color-primary);font-size:1.6rem;">
                    Olá, <?= $_userName ?>!
                </h2>
                <p class="text-muted mb-4" style="font-size:1rem;">
                    Bem-vindo(a) ao painel administrativo da <strong>Brooks Construtora</strong>.
                </p>

                <!-- Ações rápidas -->
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="/admin/dashboard" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                    </a>
                    <?php if (\App\Core\Auth::hasPermission('orders')): ?>
                    <a href="/admin/orders" class="btn btn-outline-primary btn-sm px-3">
                        <i class="bi bi-cart3 me-1"></i> Pedidos
                    </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('settings')): ?>
                    <a href="/admin/settings" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-gear me-1"></i> Configurações
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cards informativos -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center" style="border-top:3px solid var(--color-accent);">
                    <i class="bi bi-grid-1x2-fill fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Dashboard</h6>
                    <p class="text-muted small mb-3">Visualize métricas e o resumo geral do sistema.</p>
                    <a href="/admin/dashboard" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
            <?php if (\App\Core\Auth::hasPermission('orders')): ?>
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center" style="border-top:3px solid var(--color-accent);">
                    <i class="bi bi-cart3 fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Pedidos</h6>
                    <p class="text-muted small mb-3">Gerencie pedidos de materiais e acompanhe cotações.</p>
                    <a href="/admin/orders" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('settings')): ?>
            <div class="col-md-4">
                <div class="card p-3 h-100 text-center" style="border-top:3px solid var(--color-accent);">
                    <i class="bi bi-gear fs-2 mb-2" style="color:var(--color-accent);"></i>
                    <h6 class="fw-semibold mb-1">Configurações</h6>
                    <p class="text-muted small mb-3">Ajuste preferências e parâmetros do sistema.</p>
                    <a href="/admin/settings" class="btn btn-sm btn-outline-primary mt-auto">Acessar</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info do usuário -->
        <div class="card p-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-person-circle me-2"></i>Sua conta</h6>
            <div class="row g-2">
                <div class="col-sm-6">
                    <span class="text-muted small">Nome</span>
                    <div class="fw-medium"><?= $_userName ?></div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">E-mail</span>
                    <div class="fw-medium"><?= htmlspecialchars($profile['email'] ?? $user['email'] ?? '-') ?></div>
                </div>
                <div class="col-sm-6 mt-2">
                    <span class="text-muted small">Perfil</span>
                    <div>
                        <span class="badge bg-primary"><?= htmlspecialchars($user['role'] ?? '-') ?></span>
                    </div>
                </div>
                <?php if (\App\Core\Auth::hasPermission('settings')): ?>
                <div class="col-sm-6 mt-2 d-flex align-items-end">
                    <a href="/admin/settings#perfil" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i> Editar perfil
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
