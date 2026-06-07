<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Painel' ?> - Brooks Construtora Admin</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --color-primary: #3a3b4e;
            --color-primary-hover: #446084;
            --color-accent: #dd3333;
            --color-bg: #f4f6f9;
        }
        body { background-color: var(--color-bg); font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--color-primary);
            color: #fff;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s;
        }
        .sidebar .brand {
            padding: 1.25rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand img { max-width: 160px; }
        .sidebar .brand p { margin: 0.5rem 0 0; font-size: 0.75rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.08);
            border-left-color: var(--color-accent);
        }
        .sidebar .nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
        }
        .top-bar {
            background: #fff;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .stat-card {
            border-left: 4px solid var(--color-accent);
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .btn-primary:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
        }
        .btn-outline-primary {
            color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }
        .badge.bg-primary { background-color: var(--color-primary) !important; }
        .badge.bg-success { background-color: #28a745 !important; }
        .badge.bg-danger { background-color: var(--color-accent) !important; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora">
            <p>Painel Admin</p>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>" href="/admin/dashboard">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <?php if (\App\Core\Auth::hasPermission('magazines')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'magazines' ? 'active' : '' ?>" href="/admin/magazines">
                    <i class="bi bi-journal-richtext"></i> Revistas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'topics' ? 'active' : '' ?>" href="/admin/magazines/topics">
                    <i class="bi bi-lightbulb"></i> Temas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'schedule' ? 'active' : '' ?>" href="/admin/magazines/schedule">
                    <i class="bi bi-calendar-event"></i> Agendamento
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('newsletter')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'newsletter' ? 'active' : '' ?>" href="/admin/newsletter">
                    <i class="bi bi-envelope-paper"></i> Newsletter
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('users')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>" href="/admin/users">
                    <i class="bi bi-people"></i> Usuários
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>" href="/admin/settings">
                    <i class="bi bi-gear"></i> Configurações
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-3" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <a class="nav-link" href="/" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/logout" style="color: var(--color-accent);">
                    <i class="bi bi-box-arrow-left"></i> Sair
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div>
                <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="d-inline mb-0 ms-2"><?= $pageTitle ?? 'Dashboard' ?></h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                <span class="badge bg-primary"><?= ucfirst(str_replace('_', ' ', $user['role'] ?? '')) ?></span>
            </div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
