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
            'started_at' => $this->input('started_at') ?: null,
            'expected_end_at' => $this->input('expected_end_at') ?: null,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Salvar aprovadores
        $approvers = $_POST['approvers'] ?? [];
        if (!empty($approvers)) {
            ConstructionSite::syncApprovers($id, $approvers);
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

        // Salvar aprovadores
        $approvers = $_POST['approvers'] ?? [];
        ConstructionSite::syncApprovers($id, $approvers);

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
