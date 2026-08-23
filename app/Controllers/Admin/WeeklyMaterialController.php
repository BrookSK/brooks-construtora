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

        $stats = WeeklyMaterialRequest::getWeekStats($weekStart);

        // Controle por responsável (PARTE 23): status + últimos 4 ciclos
        $managerControl = WeeklyMaterialRequest::getManagerControl($weekStart);
        foreach ($managerControl as &$mc) {
            $mc['recent_cycles'] = WeeklyMaterialRequest::getRecentCyclesForManager((int) $mc['manager_id'], 4);
        }
        unset($mc);

        $this->view('admin.weekly_materials.week', [
            'requests' => $requests,
            'stats' => $stats,
            'managerControl' => $managerControl,
            'weekStart' => $weekStart,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Semana ' . date('d/m/Y', strtotime($weekStart)),
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Lista consolidada de compras (PARTE 26-28): itens dos PEDIDOS reais
     * gerados pela Lista Semanal de uma semana.
     */
    public function purchases(string $weekStart = ''): void
    {
        if (!$weekStart) {
            $weekStart = $this->input('week', WeeklyMaterialRequest::currentWeekStart());
        }

        $filters = [
            'construction_site_id' => $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : null,
            'manager_id' => $this->input('manager_id') ? (int) $this->input('manager_id') : null,
            'urgency' => $this->input('urgency') ?: null,
            'order_status' => $this->input('order_status') ?: null,
            'sort' => $this->input('sort') ?: 'urgency_date',
        ];

        $items = WeeklyMaterialRequest::getConsolidatedPurchaseItems($weekStart, $filters);
        $sites = \App\Models\ConstructionSite::allActive();

        $this->view('admin.weekly_materials.purchases', [
            'items' => $items,
            'weekStart' => $weekStart,
            'filters' => $filters,
            'sites' => $sites,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Lista de Compras — Semana ' . date('d/m/Y', strtotime($weekStart)),
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Detalhes de um responsável (PARTE 25): total de envios, respostas,
     * atrasos, materiais e histórico de semanas/pedidos gerados.
     */
    public function manager(string $managerId = ''): void
    {
        $managerId = (int) ($managerId ?: $this->input('id', 0));
        if (!$managerId) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $manager = \App\Models\PinUser::find($managerId);
        if (!$manager) {
            $this->setFlash('error', 'Responsável não encontrado.');
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $summary = WeeklyMaterialRequest::getManagerSummary($managerId);
        $requests = WeeklyMaterialRequest::getManagerRequests($managerId);

        // Carregar itens de cada solicitação preenchida
        foreach ($requests as &$req) {
            $req['items'] = ($req['status'] === 'filled')
                ? WeeklyMaterialRequest::getItems($req['id'])
                : [];
        }
        unset($req);

        $this->view('admin.weekly_materials.manager', [
            'manager' => $manager,
            'summary' => $summary,
            'requests' => $requests,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Detalhes — ' . $manager['name'],
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

        // Marcar como atrasadas as pendentes de semanas anteriores (PARTE 20)
        $overdue = WeeklyMaterialRequest::markOverduePastWeeks($weekStart);
        if ($overdue > 0) {
            \App\Models\WeeklyMaterialLog::record(
                \App\Models\WeeklyMaterialLog::ACTION_MARKED_OVERDUE,
                null,
                "{$overdue} solicitação(ões) pendente(s) de semanas anteriores marcadas como atrasadas",
                $weekStart
            );
        }

        $created = WeeklyMaterialRequest::createWeekRecords($weekStart);

        $this->setFlash('success', "Registros gerados: {$created} gerente(s) para a semana de " . date('d/m/Y', strtotime($weekStart)));
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Enviar notificações manualmente (mesma lógica do cron terça)
     */
    public function sendNow(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        // Gerar registros primeiro (se não existem)
        $nextWeek = WeeklyMaterialRequest::nextWeekStart();
        WeeklyMaterialRequest::createWeekRecords($nextWeek);

        $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
        $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
        $baseUrl = Setting::get('site_url', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
        $sent = 0;

        foreach ($requests as $req) {
            if ($req['status'] !== 'pending') continue;

            $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
            $message = "Olá {$req['manager_name']}! 📋\n\n"
                . "Preciso que você envie a lista de materiais que vai precisar na semana de "
                . date('d/m/Y', strtotime($nextWeek)) . ".\n\n"
                . "Acesse o link abaixo e preencha:\n{$formUrl}\n\n"
                . "Obrigado!";

            if ($webhookUrl && !empty($req['manager_phone'])) {
                \App\Services\NotificationService::queueWebhook($webhookUrl, [
                    'phone' => $req['manager_phone'],
                    'name' => $req['manager_name'],
                    'message' => $message,
                    'type' => 'weekly_material_request',
                ]);
                $sent++;
            }

            if (!empty($req['manager_email'])) {
                \App\Services\NotificationService::queueEmails(
                    $req['manager_email'],
                    'Lista Semanal de Materiais - Semana ' . date('d/m', strtotime($nextWeek)),
                    "<p>Olá <strong>{$req['manager_name']}</strong>!</p>"
                    . "<p>Precisamos que você envie a lista de materiais da semana de " . date('d/m/Y', strtotime($nextWeek)) . ".</p>"
                    . "<p><a href=\"{$formUrl}\" style=\"background:#3a3b4e; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none;\">Preencher Lista</a></p>"
                );
            }

            WeeklyMaterialRequest::updateById($req['id'], [
                'notified_at' => date('Y-m-d H:i:s'),
                'link_channel' => !empty($req['manager_phone']) ? 'whatsapp' : (!empty($req['manager_email']) ? 'email' : null),
            ]);

            \App\Models\WeeklyMaterialLog::record(
                \App\Models\WeeklyMaterialLog::ACTION_LINK_SENT,
                (int) $req['id'],
                "Link enviado para {$req['manager_name']}",
                $nextWeek
            );
        }

        $this->setFlash('success', "Notificações enviadas para {$sent} gerente(s).");
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Enviar cobrança manual (mesma lógica do cron quinta)
     */
    public function sendReminder(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $nextWeek = WeeklyMaterialRequest::nextWeekStart();
        $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
        $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
        $adminWebhook = Setting::get('orders_weekly_materials_admin_webhook', '');
        $adminPhone = Setting::get('orders_weekly_materials_admin_phone', '');
        $adminName = Setting::get('orders_weekly_materials_admin_name', '');
        $baseUrl = Setting::get('site_url', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
        $pendingNames = [];
        $sent = 0;

        foreach ($requests as $req) {
            if ($req['status'] !== 'pending') continue;

            $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
            $pendingNames[] = $req['manager_name'];

            $message = "⚠️ {$req['manager_name']}, você ainda NÃO preencheu a lista de materiais da semana de "
                . date('d/m/Y', strtotime($nextWeek)) . "!\n\n"
                . "Por favor, preencha o mais rápido possível:\n{$formUrl}";

            if ($webhookUrl && !empty($req['manager_phone'])) {
                \App\Services\NotificationService::queueWebhook($webhookUrl, [
                    'phone' => $req['manager_phone'],
                    'name' => $req['manager_name'],
                    'message' => $message,
                    'type' => 'weekly_material_reminder',
                ]);
                $sent++;
            }

            WeeklyMaterialRequest::updateById($req['id'], ['reminder_sent_at' => date('Y-m-d H:i:s')]);

            \App\Models\WeeklyMaterialLog::record(
                \App\Models\WeeklyMaterialLog::ACTION_REMINDER_SENT,
                (int) $req['id'],
                "Cobrança enviada para {$req['manager_name']}",
                $nextWeek
            );
        }

        // Notificar admin
        if (!empty($pendingNames) && $adminWebhook && $adminPhone) {
            $adminMessage = "📋 Lista Semanal — " . count($pendingNames) . " gerente(s) NÃO preencheram:\n\n"
                . implode("\n", array_map(fn($n) => "❌ {$n}", $pendingNames))
                . "\n\nSemana: " . date('d/m/Y', strtotime($nextWeek));

            \App\Services\NotificationService::queueWebhook($adminWebhook, [
                'phone' => $adminPhone,
                'name' => $adminName ?: 'Admin',
                'message' => $adminMessage,
                'type' => 'weekly_material_admin_alert',
            ]);
        }

        if (empty($pendingNames)) {
            $this->setFlash('success', 'Todos os gerentes já preencheram! Nenhuma cobrança necessária.');
        } else {
            $this->setFlash('success', "Cobrança enviada para {$sent} gerente(s): " . implode(', ', $pendingNames));
        }
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
