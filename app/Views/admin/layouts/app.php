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

    <!-- Indicador global de geração em background -->
    <div id="global-job-indicator" style="display:none; position:fixed; bottom:20px; right:20px; z-index:9999; max-width:380px;">
        <div class="card shadow-lg border-0" style="border-radius:12px; overflow:hidden;">
            <div id="job-indicator-header" class="card-header py-2 px-3 d-flex align-items-center justify-content-between" style="background:#3a3b4e; color:#fff; font-size:0.8rem;">
                <span>
                    <span id="job-indicator-icon" class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span>
                    <strong>Gerando Revista</strong>
                </span>
                <button type="button" class="btn btn-sm p-0 text-white opacity-75" onclick="toggleJobDetails()" style="line-height:1;">
                    <i class="bi bi-chevron-down" id="job-toggle-icon"></i>
                </button>
            </div>
            <div id="job-indicator-body" class="card-body p-3">
                <div class="progress mb-2" style="height:6px;">
                    <div id="job-global-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;"></div>
                </div>
                <p id="job-global-label" class="mb-0 small text-muted" style="font-size:0.75rem;">Aguardando início...</p>
            </div>
            <!-- Footer aparece quando completo -->
            <div id="job-indicator-footer" class="card-footer py-2 px-3 d-none" style="font-size:0.8rem;">
                <a href="#" id="job-edit-link" class="text-decoration-none">
                    <i class="bi bi-pencil"></i> Abrir revista
                </a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const indicator = document.getElementById('global-job-indicator');
        const bar = document.getElementById('job-global-bar');
        const label = document.getElementById('job-global-label');
        const icon = document.getElementById('job-indicator-icon');
        const header = document.getElementById('job-indicator-header');
        const body = document.getElementById('job-indicator-body');
        const footer = document.getElementById('job-indicator-footer');
        const editLink = document.getElementById('job-edit-link');
        const toggleIcon = document.getElementById('job-toggle-icon');

        let collapsed = false;
        let currentJobId = null;
        let pollInterval = null;
        let dismissTimeout = null;

        window.toggleJobDetails = function() {
            collapsed = !collapsed;
            body.style.display = collapsed ? 'none' : 'block';
            toggleIcon.className = collapsed ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        };

        function showIndicator() {
            indicator.style.display = 'block';
        }

        function hideIndicator() {
            indicator.style.display = 'none';
        }

        function updateIndicator(data) {
            if (data.active) {
                showIndicator();
                footer.classList.add('d-none');
                icon.className = 'spinner-border spinner-border-sm me-1';
                icon.style.width = '12px';
                icon.style.height = '12px';
                header.style.background = '#3a3b4e';

                const pct = data.total_steps > 0 
                    ? Math.round((data.current_step / data.total_steps) * 100) 
                    : 0;
                bar.style.width = pct + '%';
                bar.className = 'progress-bar progress-bar-striped progress-bar-animated';
                label.textContent = data.current_step_label || 'Processando...';
                currentJobId = data.job_id;
            } else if (data.recent) {
                showIndicator();
                currentJobId = data.job_id;

                if (data.status === 'completed') {
                    icon.className = 'bi bi-check-circle-fill me-1';
                    icon.style.width = 'auto';
                    icon.style.height = 'auto';
                    header.style.background = '#28a745';
                    bar.style.width = '100%';
                    bar.className = 'progress-bar bg-success';
                    label.textContent = data.current_step_label || 'Concluído!';

                    if (data.magazine_id) {
                        editLink.href = '/admin/magazines/edit/' + data.magazine_id;
                        footer.classList.remove('d-none');
                    }

                    // Esconde após 15 segundos
                    if (dismissTimeout) clearTimeout(dismissTimeout);
                    dismissTimeout = setTimeout(hideIndicator, 15000);
                } else if (data.status === 'failed') {
                    icon.className = 'bi bi-x-circle-fill me-1';
                    icon.style.width = 'auto';
                    icon.style.height = 'auto';
                    header.style.background = '#dc3545';
                    bar.style.width = '100%';
                    bar.className = 'progress-bar bg-danger';
                    label.textContent = data.error_message || data.current_step_label || 'Erro na geração.';

                    if (dismissTimeout) clearTimeout(dismissTimeout);
                    dismissTimeout = setTimeout(hideIndicator, 20000);
                }
            } else {
                // Nenhum job ativo nem recente
                if (!currentJobId) {
                    hideIndicator();
                }
            }
        }

        async function pollStatus() {
            try {
                const resp = await fetch('/admin/magazines/active-job');
                if (resp.ok) {
                    const data = await resp.json();
                    updateIndicator(data);

                    // Se não tem mais job ativo, diminui frequência de polling
                    if (!data.active && !data.recent) {
                        clearInterval(pollInterval);
                        pollInterval = setInterval(pollStatus, 10000); // a cada 10s quando idle
                    } else if (data.active) {
                        clearInterval(pollInterval);
                        pollInterval = setInterval(pollStatus, 3000); // a cada 3s quando ativo
                    }
                }
            } catch(e) {
                // Silencioso em caso de erro de rede
            }
        }

        // Inicia o polling
        pollStatus();
        pollInterval = setInterval(pollStatus, 5000); // começa a cada 5s

        // Expõe função para forçar refresh (usado pela página de topics)
        window.forceJobPoll = function() {
            clearInterval(pollInterval);
            pollInterval = setInterval(pollStatus, 3000);
            pollStatus();
        };
    })();
    </script>
</body>
</html>
