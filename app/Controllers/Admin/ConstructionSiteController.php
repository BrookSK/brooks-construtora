<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
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
     * Listagem de obras
     */
    public function index(): void
    {
        $status = $this->input('status');
        $sites = ConstructionSite::allWithFilter($status);

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
