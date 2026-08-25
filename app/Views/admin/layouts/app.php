<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Painel' ?> - Brooks Construtora Admin</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/searchable-select.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
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
            overflow-x: hidden;
            z-index: 1000;
            transition: width 0.3s ease;
        }
        .sidebar .brand {
            padding: 1.25rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .sidebar .brand img { max-width: 160px; transition: all 0.3s; }
        .sidebar .brand p { margin: 0.5rem 0 0; font-size: 0.75rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; transition: opacity 0.2s; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.08);
            border-left-color: var(--color-accent);
        }
        .sidebar .nav-link i { font-size: 1.1rem; min-width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar .nav-link .link-text { transition: opacity 0.2s; }
        .sidebar .nav-section-label { transition: opacity 0.2s; }

        /* Botão de toggle na sidebar */
        .sidebar-toggle-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: rgba(255,255,255,0.6);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
        }
        .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        /* Estado colapsado */
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        .sidebar.collapsed .brand img {
            max-width: 36px;
        }
        .sidebar.collapsed .brand p {
            opacity: 0;
            height: 0;
            margin: 0;
            overflow: hidden;
        }
        .sidebar.collapsed .nav-link {
            padding: 0.75rem;
            justify-content: center;
            gap: 0;
            border-left-width: 2px;
        }
        .sidebar.collapsed .nav-link .link-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        .sidebar.collapsed .nav-section-label {
            opacity: 0;
            height: 0;
            overflow: hidden;
            padding: 0 !important;
            margin: 0 !important;
        }
        .sidebar.collapsed .sidebar-toggle-btn {
            right: 50%;
            transform: translateX(50%);
            top: 10px;
        }
        .sidebar.collapsed .nav-link i {
            font-size: 1.2rem;
        }

        /* Tooltip no estado colapsado (desktop) */
        @media (min-width: 769px) {
            .sidebar.collapsed .nav-link {
                position: relative;
            }
            .sidebar.collapsed .nav-link:hover::after {
                content: attr(data-title);
                position: absolute;
                left: calc(var(--sidebar-collapsed-width) - 5px);
                top: 50%;
                transform: translateY(-50%);
                background: #333;
                color: #fff;
                padding: 0.4rem 0.75rem;
                border-radius: 4px;
                font-size: 0.8rem;
                white-space: nowrap;
                z-index: 1100;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
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

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }
            .sidebar.show { transform: translateX(0); }
            .sidebar.collapsed { width: var(--sidebar-width) !important; }
            .sidebar .nav-link .link-text { opacity: 1 !important; width: auto !important; }
            .sidebar .nav-section-label { opacity: 1 !important; height: auto !important; overflow: visible !important; }
            .sidebar .brand img { max-width: 160px !important; }
            .sidebar .brand p { opacity: 0.6 !important; height: auto !important; }
            .sidebar .nav-link { justify-content: flex-start !important; gap: 0.75rem !important; padding: 0.75rem 1.25rem !important; }
            .sidebar-toggle-btn { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0.75rem; }
            .top-bar { 
                padding: 0.75rem 1rem; 
                margin-bottom: 1rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .top-bar h5 { font-size: 1rem; }
            .stat-card .stat-number { font-size: 1.5rem; }
            .card-header { padding: 0.6rem 0.8rem; font-size: 0.9rem; }
            .card-body { padding: 0.8rem; }
            .table { font-size: 0.8rem; }
            .table th, .table td { padding: 0.4rem 0.5rem; }
            .btn-lg { padding: 0.6rem 1rem; font-size: 0.95rem; }
            .modal-dialog { margin: 0.5rem; }
            /* Prevenir zoom no iOS ao focar inputs */
            input[type="text"],
            input[type="email"],
            input[type="number"],
            input[type="url"],
            input[type="tel"],
            input[type="password"],
            input[type="search"],
            textarea,
            select,
            .ss-input,
            .form-control,
            .form-select {
                font-size: 16px !important;
            }
        }
        /* Overlay para sidebar mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora">
            <p>Painel Admin</p>
            <button class="sidebar-toggle-btn" id="sidebarCollapseBtn" title="Recolher menu">
                <i class="bi bi-chevron-left" id="collapseIcon"></i>
            </button>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>" href="/admin/dashboard" data-title="Dashboard">
                    <i class="bi bi-grid-1x2-fill"></i> <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'welcome' ? 'active' : '' ?>" href="/admin/welcome" data-title="Boas-vindas">
                    <i class="bi bi-hand-wave"></i> <span class="link-text">Boas-vindas</span>
                </a>
            </li>
            <?php if (\App\Core\Auth::hasPermission('magazines')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'magazines' ? 'active' : '' ?>" href="/admin/magazines" data-title="Revistas">
                    <i class="bi bi-journal-richtext"></i> <span class="link-text">Revistas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'topics' ? 'active' : '' ?>" href="/admin/magazines/topics" data-title="Temas">
                    <i class="bi bi-lightbulb"></i> <span class="link-text">Temas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'schedule' ? 'active' : '' ?>" href="/admin/magazines/schedule" data-title="Agendamento">
                    <i class="bi bi-calendar-event"></i> <span class="link-text">Agendamento</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('newsletter')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'newsletter' ? 'active' : '' ?>" href="/admin/newsletter" data-title="Newsletter">
                    <i class="bi bi-envelope-paper"></i> <span class="link-text">Newsletter</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('orders')): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Contratos</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'briefing' ? 'active' : '' ?>" href="/admin/briefing" data-title="Briefing & Contratos">
                    <i class="bi bi-file-earmark-text"></i> <span class="link-text">Briefing & Contratos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('orders')): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Pedidos</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'orders' ? 'active' : '' ?>" href="/admin/orders" data-title="Pedidos">
                    <i class="bi bi-cart3"></i> <span class="link-text">Pedidos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('obras')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'obras' ? 'active' : '' ?>" href="/admin/obras" data-title="Obras">
                    <i class="bi bi-buildings"></i> <span class="link-text">Obras</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('suppliers')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'suppliers' ? 'active' : '' ?>" href="/admin/suppliers" data-title="Fornecedores">
                    <i class="bi bi-building"></i> <span class="link-text">Fornecedores</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('materials')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'materials' ? 'active' : '' ?>" href="/admin/materials" data-title="Materiais">
                    <i class="bi bi-box-seam"></i> <span class="link-text">Materiais</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('orders.settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'orders_settings' ? 'active' : '' ?>" href="/admin/orders/settings" data-title="Config. Pedidos">
                    <i class="bi bi-sliders"></i> <span class="link-text">Config. Pedidos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('transport')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'transport' ? 'active' : '' ?>" href="/admin/transport" data-title="Transporte">
                    <i class="bi bi-truck"></i> <span class="link-text">Transporte</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('stock')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'stock' ? 'active' : '' ?>" href="/admin/stock" data-title="Estoque">
                    <i class="bi bi-boxes"></i> <span class="link-text">Estoque</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('orders')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'price_history' ? 'active' : '' ?>" href="/admin/orders/price-history" data-title="Histórico Preços">
                    <i class="bi bi-graph-up"></i> <span class="link-text">Histórico Preços</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('orders.payment') || \App\Core\Auth::isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'payments' ? 'active' : '' ?>" href="/admin/orders/payments" data-title="NF / Boletos">
                    <i class="bi bi-receipt"></i> <span class="link-text">NF / Boletos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'quote_logs' ? 'active' : '' ?>" href="/admin/orders/quote-logs" data-title="Logs Cotação">
                    <i class="bi bi-bug"></i> <span class="link-text">Logs Cotação</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('epi')): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">EPIs</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'epi_delivery' ? 'active' : '' ?>" href="/registro-de-entrega" data-title="Registro de Entrega">
                    <i class="bi bi-box-seam"></i> <span class="link-text">Registro de Entrega</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'epi_replacement' ? 'active' : '' ?>" href="/substituicao-de-epi" data-title="Devoluções e Substituições">
                    <i class="bi bi-arrow-repeat"></i> <span class="link-text">Devoluções e Substituições</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'epi_thirdparty' ? 'active' : '' ?>" href="/distribuicao-terceiros" data-title="Distribuição para Terceiros">
                    <i class="bi bi-people-fill"></i> <span class="link-text">Distribuição para Terceiros</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'epi_catalog' ? 'active' : '' ?>" href="/cadastro-de-epi" data-title="Cadastro de EPI">
                    <i class="bi bi-shield-check"></i> <span class="link-text">Cadastro de EPI</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'epi_history' ? 'active' : '' ?>" href="/historico-de-epi" data-title="Histórico de EPI">
                    <i class="bi bi-clock-history"></i> <span class="link-text">Histórico de EPI</span>
                </a>
            </li>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Limpeza</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= in_array($currentPage ?? '', ['cleaning_index', 'cleaning_create']) ? 'active' : '' ?>" href="/checklist-limpeza" data-title="Checklist de Limpeza">
                    <i class="bi bi-clipboard2-check"></i> <span class="link-text">Checklist de Limpeza</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('epi') || \App\Core\Auth::isAdmin()): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Presença</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'presence' ? 'active' : '' ?>" href="/lista-de-presenca" data-title="Lista de Presença">
                    <i class="bi bi-clipboard-check"></i> <span class="link-text">Lista de Presença</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'presence_history' ? 'active' : '' ?>" href="/historico-presenca" data-title="Histórico de Presença">
                    <i class="bi bi-calendar2-week"></i> <span class="link-text">Histórico de Presença</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('orders')): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Planejamento</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'weekly_materials' ? 'active' : '' ?>" href="/admin/weekly-materials" data-title="Lista Semanal">
                    <i class="bi bi-calendar-week"></i> <span class="link-text">Lista Semanal</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'finance' ? 'active' : '' ?>" href="/admin/finance" data-title="Financeiro">
                    <i class="bi bi-cash-coin"></i> <span class="link-text">Financeiro</span>
                </a>
            </li>
            <?php endif; ?>

            <?php // Veículo - qualquer usuário logado pode acessar ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Veículo</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'vehicle' ? 'active' : '' ?>" href="/admin/vehicle" data-title="Saveiro">
                    <i class="bi bi-truck-front"></i> <span class="link-text">Saveiro</span>
                </a>
            </li>

            <?php if (\App\Core\Auth::hasPermission('users')): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <a class="nav-link <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>" href="/admin/users" data-title="Usuários">
                    <i class="bi bi-people"></i> <span class="link-text">Usuários</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasPermission('settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>" href="/admin/settings" data-title="Configurações">
                    <i class="bi bi-gear"></i> <span class="link-text">Configurações</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <li class="nav-item mt-2" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <small class="text-uppercase px-3 opacity-50 nav-section-label" style="font-size:0.65rem; letter-spacing:1px;">Desenvolvimento</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage ?? '') === 'dev_nibo' ? 'active' : '' ?>" href="/admin/dev/nibo" data-title="API Nibo">
                    <i class="bi bi-plug"></i> <span class="link-text">API Nibo</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-3" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.5rem;">
                <a class="nav-link" href="/" target="_blank" data-title="Ver Site">
                    <i class="bi bi-box-arrow-up-right"></i> <span class="link-text">Ver Site</span>
                </a>
            </li>
            <?php if (!empty($_SESSION['pin_auth'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/pin/minha-conta" data-title="Minha Conta">
                    <i class="bi bi-person-circle"></i> <span class="link-text">Minha Conta</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="/admin/logout" style="color: var(--color-accent);" data-title="Sair">
                    <i class="bi bi-box-arrow-left"></i> <span class="link-text">Sair</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Overlay sidebar mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary d-md-none me-2" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="d-inline mb-0"><?= $pageTitle ?? 'Dashboard' ?></h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small d-none d-sm-inline"><?= htmlspecialchars($user['name'] ?? '') ?></span>
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
    <script>
    (function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const collapseIcon = document.getElementById('collapseIcon');
        const STORAGE_KEY = 'sidebar_collapsed';

        // Restaurar estado salvo (apenas desktop)
        function isDesktop() { return window.innerWidth > 768; }

        function applySavedState() {
            if (isDesktop() && localStorage.getItem(STORAGE_KEY) === '1') {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
                collapseIcon.className = 'bi bi-chevron-right';
            }
        }
        applySavedState();

        // Toggle mobile (hamburger)
        window.toggleSidebar = function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        };

        // Toggle collapse (desktop)
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!isDesktop()) return;

                const isCollapsed = sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed', isCollapsed);
                collapseIcon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
                localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
            });
        }

        // Fechar sidebar ao clicar em link no mobile
        if (!isDesktop()) {
            document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
                link.addEventListener('click', function() { 
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            });
        }

        // Ajustar ao redimensionar
        window.addEventListener('resize', function() {
            if (isDesktop()) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                applySavedState();
            } else {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }
        });
    })();
    </script>

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
