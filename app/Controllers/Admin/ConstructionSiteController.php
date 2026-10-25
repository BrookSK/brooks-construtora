<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\ConstructionSite;

class ConstructionSiteController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('orders')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Verifica se a tabela construction_sites existe no banco. Se não, cria automaticamente.
     */
    private function ensureTableExists(): bool
    {
        $result = Database::fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'construction_sites' LIMIT 1");
        if (empty($result)) {
            // Auto-criar a tabela
            try {
                Database::getConnection()->exec("
                    CREATE TABLE IF NOT EXISTS construction_sites (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        code VARCHAR(50) NULL,
                        address VARCHAR(500) NULL,
                        city VARCHAR(100) NULL,
                        state VARCHAR(2) NULL,
                        responsible_name VARCHAR(255) NULL,
                        responsible_phone VARCHAR(30) NULL,
                        client_name VARCHAR(255) NULL,
                        description TEXT NULL,
                        status ENUM('active', 'inactive', 'completed') NOT NULL DEFAULT 'active',
                        started_at DATE NULL,
                        expected_end_at DATE NULL,
                        completed_at DATE NULL,
                        created_by INT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_status (status),
                        INDEX idx_code (code),
                        INDEX idx_name (name)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } catch (\Exception $e) {
                $this->setFlash('error', 'Erro ao criar tabela de obras: ' . $e->getMessage());
                $this->redirect('/admin/dashboard');
                return false;
            }

            // Adicionar coluna em purchase_orders se não existir
            try {
                $colCheck = Database::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1");
                if (empty($colCheck)) {
                    Database::getConnection()->exec("ALTER TABLE purchase_orders ADD COLUMN construction_site_id INT NULL AFTER order_type, ADD INDEX idx_construction_site (construction_site_id)");
                }
            } catch (\Exception $e) {
                // Não fatal - a coluna pode ser adicionada depois
            }
        }
        return true;
    }

    /** Fases de notificação (mesmas da edição da obra). */
    private const PHASES = [
        'quote' => 'Cotação',
        'approval' => 'Aprovação',
        'completed' => 'Conclusão',
        'payment' => 'Pagamento',
        'delivery' => 'Entrega',
        'spare' => 'Sobressalentes',
        'transport' => 'Transporte',
        'weekly' => 'Lista Semanal',
    ];

    private const STATUS_LABELS = ['active' => 'Ativa', 'inactive' => 'Inativa', 'completed' => 'Concluída'];

    /** Todos os vínculos obra × fase × usuário. */
    private function fetchResponsibles(): array
    {
        return Database::fetchAll(
            "SELECT csa.construction_site_id AS site_id, csa.phase, csa.created_at AS vinculado_em,
                    cs.code AS site_code, cs.name AS site_name, cs.status AS site_status,
                    pu.id AS user_id, pu.name AS user_name, pu.phone AS user_phone,
                    pu.email AS user_email, pu.role AS user_role, pu.active AS user_active
             FROM construction_site_approvers csa
             INNER JOIN construction_sites cs ON cs.id = csa.construction_site_id
             INNER JOIN pin_users pu ON pu.id = csa.pin_user_id
             ORDER BY cs.name ASC, csa.phase ASC, pu.name ASC"
        );
    }

    /**
     * Relação de responsáveis por obra e fase (com filtros por usuário/fase/obra).
     */
    public function responsibles(): void
    {
        if (!$this->ensureTableExists()) return;

        $rows = $this->fetchResponsibles();
        $sites = Database::fetchAll("SELECT id, code, name, status FROM construction_sites ORDER BY name ASC");

        // Matriz: obra → fase → [usuários]
        $matrix = [];
        $users = [];
        foreach ($rows as $r) {
            $matrix[$r['site_id']][$r['phase']][] = $r;
            $users[$r['user_id']] = $r['user_name'];
        }
        asort($users, SORT_NATURAL | SORT_FLAG_CASE);

        $this->view('admin.obras.responsibles', [
            'sites' => $sites,
            'matrix' => $matrix,
            'users' => $users,
            'phases' => self::PHASES,
            'statusLabels' => self::STATUS_LABELS,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Exporta a relação (1 linha por vínculo obra × fase × usuário) em XLSX.
     * Respeita os filtros user_id, phase e status da tela.
     */
    public function exportResponsibles(): void
    {
        if (!$this->ensureTableExists()) return;

        $userId = (int) ($_GET['user_id'] ?? 0);
        $phase = (string) ($_GET['phase'] ?? '');
        $status = (string) ($_GET['status'] ?? '');

        $rows = array_filter($this->fetchResponsibles(), function ($r) use ($userId, $phase, $status) {
            return (!$userId || (int) $r['user_id'] === $userId)
                && ($phase === '' || $r['phase'] === $phase)
                && ($status === '' || $r['site_status'] === $status);
        });

        $xlsx = new \App\Services\XlsxService();
        $xlsx->setStrictTypes(true);
        $xlsx->setSheetName('Responsáveis');
        $xlsx->setColumnWidths([12, 35, 12, 16, 30, 18, 30, 12, 10, 18]);
        $xlsx->addRow(['Obra Código', 'Obra', 'Status Obra', 'Fase', 'Usuário', 'Telefone', 'E-mail', 'Perfil', 'Usuário Ativo', 'Vinculado em'], 'header');
        foreach ($rows as $r) {
            $xlsx->addRow([
                (string) ($r['site_code'] ?? ''),
                (string) $r['site_name'],
                self::STATUS_LABELS[$r['site_status']] ?? (string) $r['site_status'],
                self::PHASES[$r['phase']] ?? (string) $r['phase'],
                (string) $r['user_name'],
                (string) ($r['user_phone'] ?? ''),
                (string) ($r['user_email'] ?? ''),
                (string) ($r['user_role'] ?? ''),
                (int) $r['user_active'] ? 'Sim' : 'Não',
                (string) ($r['vinculado_em'] ?? ''),
            ]);
        }

        // Aba 2: matriz obra × fase (nomes separados por vírgula)
        $xlsx->addSheet('Matriz');
        $xlsx->setColumnWidths(array_merge([12, 35, 12], array_fill(0, count(self::PHASES), 30)));
        $xlsx->addRow(array_merge(['Obra Código', 'Obra', 'Status'], array_values(self::PHASES)), 'header');
        $byPhase = [];
        foreach ($rows as $r) {
            $byPhase[$r['site_id']]['site'] = $r;
            $byPhase[$r['site_id']][$r['phase']][] = $r['user_name'];
        }
        $sites = Database::fetchAll("SELECT id, code, name, status FROM construction_sites ORDER BY name ASC");
        foreach ($sites as $s) {
            if ($status !== '' && $s['status'] !== $status) continue;
            if (($userId || $phase !== '') && !isset($byPhase[$s['id']])) continue;
            $line = [(string) ($s['code'] ?? ''), (string) $s['name'], self::STATUS_LABELS[$s['status']] ?? (string) $s['status']];
            foreach (array_keys(self::PHASES) as $p) {
                $line[] = implode(', ', $byPhase[$s['id']][$p] ?? []);
            }
            $xlsx->addRow($line);
        }

        $xlsx->download('responsaveis_obras_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Listagem de obras
     */
    public function index(): void
    {
        if (!$this->ensureTableExists()) return;

        try {
            $status = $this->input('status');
            $sites = ConstructionSite::allWithFilter($status);
        } catch (\Exception $e) {
            $this->setFlash('error', 'A tabela de obras ainda não foi criada. Execute a migration SQL.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->view('admin.obras.index', [
            'sites' => $sites,
            'currentStatus' => $status,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(): void
    {
        if (!$this->ensureTableExists()) return;

        $this->view('admin.obras.create', [
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar nova obra
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/obras');
            return;
        }

        if (!$this->ensureTableExists()) return;

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome da obra é obrigatório.');
            $this->redirect('/admin/obras/create');
            return;
        }

        $code = ConstructionSite::generateCode();

        $id = ConstructionSite::create([
            'name' => $name,
            'code' => $code,
            'address' => trim($this->input('address', '')),
            'city' => trim($this->input('city', '')),
            'state' => trim($this->input('state', '')),
            'responsible_name' => trim($this->input('responsible_name', '')),
            'responsible_phone' => trim($this->input('responsible_phone', '')),
            'client_name' => trim($this->input('client_name', '')),
            'description' => trim($this->input('description', '')),
            'status' => $this->input('status', 'active'),
            'project_type' => $this->input('project_type', 'construction'),
            'started_at' => $this->input('started_at') ?: null,
            'expected_end_at' => $this->input('expected_end_at') ?: null,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Salvar responsáveis por fase
        $notifiers = $_POST['notifiers'] ?? [];
        $allPhases = ['quote', 'approval', 'completed', 'payment', 'delivery', 'spare', 'transport', 'weekly'];
        foreach ($allPhases as $phase) {
            $phaseUsers = $notifiers[$phase] ?? [];
            if (!empty($phaseUsers)) {
                ConstructionSite::syncApprovers($id, $phaseUsers, $phase);
            }
        }

        $this->setFlash('success', "Obra \"{$name}\" ({$code}) cadastrada com sucesso!");
        $this->redirect('/admin/obras');
    }

    /**
     * Formulário de edição
     */
    public function edit(int $id = 0): void
    {
        $id = $id ?: (int) $this->input('id', 0);
        $site = ConstructionSite::findWithStats($id);

        if (!$site) {
            $this->setFlash('error', 'Obra não encontrada.');
            $this->redirect('/admin/obras');
            return;
        }

        $orders = ConstructionSite::getOrders($id);

        $this->view('admin.obras.edit', [
            'site' => $site,
            'orders' => $orders,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Atualizar obra
     */
    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/obras');
            return;
        }

        $id = (int) $this->input('id', 0);
        $site = ConstructionSite::find($id);

        if (!$site) {
            $this->setFlash('error', 'Obra não encontrada.');
            $this->redirect('/admin/obras');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome da obra é obrigatório.');
            $this->redirect('/admin/obras/edit/' . $id);
            return;
        }

        $status = $this->input('status', 'active');
        $projectType = $this->input('project_type', 'construction');
        $data = [
            'name' => $name,
            'address' => trim($this->input('address', '')),
            'city' => trim($this->input('city', '')),
            'state' => trim($this->input('state', '')),
            'responsible_name' => trim($this->input('responsible_name', '')),
            'responsible_phone' => trim($this->input('responsible_phone', '')),
            'client_name' => trim($this->input('client_name', '')),
            'description' => trim($this->input('description', '')),
            'status' => $status,
            'project_type' => $projectType,
            'started_at' => $this->input('started_at') ?: null,
            'expected_end_at' => $this->input('expected_end_at') ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Se marcou como concluída, registra data de conclusão
        if ($status === 'completed' && empty($site['completed_at'])) {
            $data['completed_at'] = date('Y-m-d');
        } elseif ($status !== 'completed') {
            $data['completed_at'] = null;
        }

        ConstructionSite::updateById($id, $data);

        // Salvar responsáveis por fase
        $notifiers = $_POST['notifiers'] ?? [];
        $allPhases = ['quote', 'approval', 'completed', 'payment', 'delivery', 'spare', 'transport', 'weekly'];
        foreach ($allPhases as $phase) {
            $phaseUsers = $notifiers[$phase] ?? [];
            ConstructionSite::syncApprovers($id, $phaseUsers, $phase);
        }

        $this->setFlash('success', "Obra \"{$name}\" atualizada com sucesso!");
        $this->redirect('/admin/obras/edit/' . $id);
    }

    /**
     * Excluir obra
     */
    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/obras');
            return;
        }

        $id = (int) $this->input('id', 0);
        $site = ConstructionSite::findWithStats($id);

        if (!$site) {
            $this->setFlash('error', 'Obra não encontrada.');
            $this->redirect('/admin/obras');
            return;
        }

        if ($site['orders_count'] > 0) {
            $this->setFlash('error', 'Não é possível excluir uma obra que possui pedidos vinculados. Inative a obra em vez disso.');
            $this->redirect('/admin/obras/edit/' . $id);
            return;
        }

        ConstructionSite::deleteById($id);

        $this->setFlash('success', "Obra \"{$site['name']}\" excluída com sucesso.");
        $this->redirect('/admin/obras');
    }

    /**
     * Busca AJAX de obras (para select no formulário de pedido)
     */
    public function search(): void
    {
        $term = trim($this->input('term', ''));
        if (empty($term)) {
            $this->json(['results' => ConstructionSite::allActive()]);
            return;
        }
        $this->json(['results' => ConstructionSite::search($term)]);
    }
}
