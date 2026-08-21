<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\WeeklyMaterialManager;
use App\Models\WeeklyMaterialRequest;
use App\Models\Setting;

class WeeklyMaterialController extends Controller
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
     * Painel principal: semanas + status
     */
    public function index(): void
    {
        $weeks = WeeklyMaterialRequest::getWeeks();
        $managers = WeeklyMaterialManager::allWithSite();
        $currentWeek = WeeklyMaterialRequest::currentWeekStart();

        $this->view('admin.weekly_materials.index', [
            'weeks' => $weeks,
            'managers' => $managers,
            'currentWeek' => $currentWeek,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Lista Semanal de Materiais',
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Detalhes de uma semana específica
     */
    public function week(string $weekStart = ''): void
    {
        if (!$weekStart) {
            $weekStart = $this->input('week', WeeklyMaterialRequest::currentWeekStart());
        }

        $requests = WeeklyMaterialRequest::getByWeek($weekStart);

        // Carregar itens de cada request preenchido
        foreach ($requests as &$req) {
            if ($req['status'] === 'filled') {
                $req['items'] = WeeklyMaterialRequest::getItems($req['id']);
            } else {
                $req['items'] = [];
            }
        }

        $this->view('admin.weekly_materials.week', [
            'requests' => $requests,
            'weekStart' => $weekStart,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Semana ' . date('d/m/Y', strtotime($weekStart)),
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Gerar registros da semana (manual ou via cron)
     */
    public function generate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $weekStart = $this->input('week_start', WeeklyMaterialRequest::nextWeekStart());
        $created = WeeklyMaterialRequest::createWeekRecords($weekStart);

        $this->setFlash('success', "Registros gerados: {$created} gerente(s) para a semana de " . date('d/m/Y', strtotime($weekStart)));
        $this->redirect('/admin/weekly-materials');
    }

    // ─── Gerentes ────────────────────────────────────────────────────────────

    /**
     * Cadastrar gerente
     */
    public function storeManager(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'Nome é obrigatório.');
            $this->redirect('/admin/weekly-materials');
            return;
        }

        WeeklyMaterialManager::create([
            'name' => $name,
            'phone' => trim($this->input('phone', '')) ?: null,
            'email' => trim($this->input('email', '')) ?: null,
            'construction_site_id' => $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : null,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Gerente cadastrado com sucesso!');
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Ativar/desativar gerente
     */
    public function toggleManager(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $id = (int) $this->input('id', 0);
        $manager = WeeklyMaterialManager::find($id);
        if (!$manager) {
            $this->setFlash('error', 'Gerente não encontrado.');
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $newStatus = $manager['active'] ? 0 : 1;
        WeeklyMaterialManager::updateById($id, ['active' => $newStatus]);

        $this->setFlash('success', $newStatus ? 'Gerente ativado.' : 'Gerente desativado.');
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Excluir gerente
     */
    public function deleteManager(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $id = (int) $this->input('id', 0);
        WeeklyMaterialManager::deleteById($id);

        $this->setFlash('success', 'Gerente removido.');
        $this->redirect('/admin/weekly-materials');
    }
}
