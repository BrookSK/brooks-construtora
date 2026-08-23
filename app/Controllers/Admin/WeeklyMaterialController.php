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
     * Painel principal (dashboard consolidado): stats, controle por
     * responsável, lista de compras, automação e organização.
     */
    public function index(): void
    {
        $weeks = WeeklyMaterialRequest::getWeeks();

        // Semana selecionada (via filtro) ou a semana mais recente com registros
        $selectedWeek = $this->input('week', '');
        if (!$selectedWeek) {
            $selectedWeek = !empty($weeks) ? $weeks[0]['week_start'] : WeeklyMaterialRequest::currentWeekStart();
        }

        // Cards de indicadores (PARTE 22)
        $stats = WeeklyMaterialRequest::getWeekStats($selectedWeek);

        // Controle por responsável (PARTE 23) + últimos 4 ciclos
        $managerControl = WeeklyMaterialRequest::getManagerControl($selectedWeek);
        foreach ($managerControl as &$mc) {
            $mc['recent_cycles'] = WeeklyMaterialRequest::getRecentCyclesForManager((int) $mc['manager_id'], 4);
        }
        unset($mc);

        // Lista de compras consolidada (PARTE 26)
        $sortMode = $this->input('sort', Setting::get('weekly_purchase_sort', 'urgency_date'));
        $purchaseItems = WeeklyMaterialRequest::getConsolidatedPurchaseItems($selectedWeek, [
            'construction_site_id' => $this->input('purchase_site') ? (int) $this->input('purchase_site') : null,
            'urgency' => $this->input('purchase_urgency') ?: null,
            'order_status' => $this->input('purchase_status') ?: null,
            'sort' => $sortMode,
        ]);

        // Log de notificações da semana (PARTE 31)
        $logs = \App\Models\WeeklyMaterialLog::getByWeek($selectedWeek);

        // Configuração de automação (PARTE 17)
        $automation = [
            'cycle_interval_days' => Setting::get('weekly_cycle_interval_days', Setting::get('weekly_min_advance_days', '15')),
            'send_day' => Setting::get('weekly_send_day', '1'),
            'send_time' => Setting::get('weekly_send_time', '08:00'),
            'response_deadline' => Setting::get('weekly_response_deadline', 'same_day_18'),
            'min_advance_days' => Setting::get('weekly_min_advance_days', '15'),
            'auto_reminder' => Setting::get('weekly_auto_reminder', '1'),
            'auto_overdue' => Setting::get('weekly_auto_overdue', '1'),
            'notify_supervisor' => Setting::get('weekly_notify_supervisor', '0'),
            'channel' => Setting::get('weekly_channel', 'whatsapp'),
        ];

        // Organização da lista de compras (PARTE 27)
        $organization = [
            'sort' => Setting::get('weekly_purchase_sort', 'urgency_date'),
            'group_by' => Setting::get('weekly_purchase_group', 'site_category'),
            'flag_outside_15' => Setting::get('weekly_flag_outside_15', '1'),
        ];

        $sites = \App\Models\ConstructionSite::allActive();

        $this->view('admin.weekly_materials.index', [
            'weeks' => $weeks,
            'selectedWeek' => $selectedWeek,
            'stats' => $stats,
            'managerControl' => $managerControl,
            'purchaseItems' => $purchaseItems,
            'logs' => $logs,
            'automation' => $automation,
            'organization' => $organization,
            'sites' => $sites,
            'currentWeek' => WeeklyMaterialRequest::currentWeekStart(),
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Lista Semanal de Materiais',
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Salvar configuração de automação semanal e organização (PARTE 17/27).
     */
    public function saveAutomation(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        $minAdvance = (int) $this->input('min_advance_days', '15');
        // Intervalo do ciclo: se não informado, acompanha a antecedência mínima
        $cycleInterval = (int) $this->input('cycle_interval_days', 0);
        if ($cycleInterval <= 0) $cycleInterval = $minAdvance;

        Setting::setMultiple([
            'weekly_cycle_interval_days' => (string) max(1, $cycleInterval),
            'weekly_send_day' => $this->input('send_day', '1'),
            'weekly_send_time' => $this->input('send_time', '08:00'),
            'weekly_response_deadline' => $this->input('response_deadline', 'same_day_18'),
            'weekly_min_advance_days' => (string) max(1, $minAdvance),
            'weekly_auto_reminder' => $this->input('auto_reminder') ? '1' : '0',
            'weekly_auto_overdue' => $this->input('auto_overdue') ? '1' : '0',
            'weekly_notify_supervisor' => $this->input('notify_supervisor') ? '1' : '0',
            'weekly_channel' => $this->input('channel', 'whatsapp'),
        ]);

        $this->setFlash('success', 'Automação semanal salva com sucesso.');
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Salvar configuração de organização da lista de compras (PARTE 27).
     */
    public function saveOrganization(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials');
            return;
        }

        Setting::setMultiple([
            'weekly_purchase_sort' => $this->input('sort', 'urgency_date'),
            'weekly_purchase_group' => $this->input('group_by', 'site_category'),
            'weekly_flag_outside_15' => $this->input('flag_outside_15') ? '1' : '0',
        ]);

        $this->setFlash('success', 'Organização da lista de compras salva.');
        $this->redirect('/admin/weekly-materials');
    }

    /**
     * Exportar controle por responsável da semana em CSV (PARTE 23).
     */
    public function exportControl(string $weekStart = ''): void
    {
        if (!$weekStart) {
            $weekStart = $this->input('week', WeeklyMaterialRequest::currentWeekStart());
        }

        $rows = WeeklyMaterialRequest::getManagerControl($weekStart);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="controle-semana-' . $weekStart . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['Responsável', 'Obra', 'Link enviado', 'Status', 'Último pedido', 'Próxima necessidade'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['manager_name'],
                $r['construction_site_name'] ?? '',
                !empty($r['notified_at']) ? date('d/m/Y H:i', strtotime($r['notified_at'])) : '',
                $r['status'],
                !empty($r['order_code']) ? '#' . $r['order_code'] : '',
                !empty($r['needed_date']) ? date('d/m/Y', strtotime($r['needed_date'])) : '',
            ], ';');
        }
        fclose($out);
        exit;
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
     * Monitoramento de pontualidade (rastreamento): quem está em dia, quem
     * preencheu com atraso e quem não preencheu, com filtros por período e
     * responsável.
     */
    public function monitoring(): void
    {
        // Período: presets ou intervalo custom
        $period = $this->input('period', '8w');
        $end = WeeklyMaterialRequest::currentWeekStart();
        $start = $this->input('start', '');
        $endInput = $this->input('end', '');

        if ($period === 'custom' && $start && $endInput) {
            $end = $endInput;
        } else {
            $weeksBack = ['4w' => 4, '8w' => 8, '12w' => 12, '24w' => 24, '52w' => 52][$period] ?? 8;
            $start = date('Y-m-d', strtotime($end . " -" . ($weeksBack - 1) . " weeks"));
        }

        $managerId = $this->input('manager_id') ? (int) $this->input('manager_id') : null;

        $report = WeeklyMaterialRequest::getPunctualityReport($start, $end, $managerId);
        $totals = WeeklyMaterialRequest::getPunctualityTotals($start, $end, $managerId);
        $list = WeeklyMaterialRequest::getMonitoringList($start, $end, $managerId);
        $managers = WeeklyMaterialRequest::managersForFilter();

        $this->view('admin.weekly_materials.monitoring', [
            'report' => $report,
            'totals' => $totals,
            'list' => $list,
            'managers' => $managers,
            'filters' => [
                'period' => $period,
                'start' => $start,
                'end' => $end,
                'manager_id' => $managerId,
            ],
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Monitoramento de Pontualidade',
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Gerenciar ciclos: lista os ciclos gerados para permitir exclusão.
     */
    public function cycles(): void
    {
        $weeks = WeeklyMaterialRequest::getWeeks();
        $nextCycle = WeeklyMaterialRequest::nextCycleStart();
        $cycleDays = WeeklyMaterialRequest::cycleIntervalDays();

        $this->view('admin.weekly_materials.cycles', [
            'weeks' => $weeks,
            'nextCycle' => $nextCycle,
            'cycleDays' => $cycleDays,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Gerenciar Ciclos',
            'currentPage' => 'weekly_materials',
        ]);
    }

    /**
     * Apagar um ciclo já gerado (para testes). Após apagar, a próxima
     * geração volta a partir do último ciclo restante.
     */
    public function deleteCycle(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/weekly-materials/cycles');
            return;
        }

        $weekStart = $this->input('week_start', '');
        if (!$weekStart) {
            $this->setFlash('error', 'Ciclo não informado.');
            $this->redirect('/admin/weekly-materials/cycles');
            return;
        }

        $removed = WeeklyMaterialRequest::deleteCycle($weekStart);
        $this->setFlash('success', "Ciclo de " . date('d/m/Y', strtotime($weekStart)) . " apagado ({$removed} solicitação(ões)). A próxima geração recomeça a partir daqui.");
        $this->redirect('/admin/weekly-materials/cycles');
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

        $weekStart = $this->input('week_start', WeeklyMaterialRequest::nextCycleStart());

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

        // Gerar registros do próximo ciclo (respeita o intervalo configurado)
        $nextWeek = WeeklyMaterialRequest::nextCycleStart();
        WeeklyMaterialRequest::createWeekRecords($nextWeek);

        $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
        $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
        $baseUrl = Setting::get('site_url', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
        $sent = 0;

        foreach ($requests as $req) {
            if ($req['status'] !== 'pending') continue;

            // Identifica a obra no link/mensagem (um link por obra)
            $siteLabel = !empty($req['construction_site_name'])
                ? (($req['construction_site_code'] ? $req['construction_site_code'] . ' - ' : '') . $req['construction_site_name'])
                : '';

            $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
            $obraLinha = $siteLabel ? "*Obra:* {$siteLabel}\n" : '';
            $message = "Olá {$req['manager_name']}! 📋\n\n"
                . $obraLinha
                . "Preciso que você envie a lista de materiais que vai precisar no ciclo de "
                . date('d/m/Y', strtotime($nextWeek)) . ".\n\n"
                . "Acesse o link abaixo e preencha:\n{$formUrl}\n\n"
                . "Obrigado!";

            if ($webhookUrl && !empty($req['manager_phone'])) {
                \App\Services\NotificationService::queueWebhook($webhookUrl, [
                    'phone' => $req['manager_phone'],
                    'name' => $req['manager_name'],
                    'message' => $message,
                    'type' => 'weekly_material_request',
                    'construction_site' => $siteLabel,
                ]);
                $sent++;
            }

            if (!empty($req['manager_email'])) {
                $obraHtml = $siteLabel ? "<p><strong>Obra:</strong> " . htmlspecialchars($siteLabel) . "</p>" : '';
                \App\Services\NotificationService::queueEmails(
                    $req['manager_email'],
                    'Lista Semanal de Materiais' . ($siteLabel ? ' - ' . $siteLabel : '') . ' - ' . date('d/m', strtotime($nextWeek)),
                    "<p>Olá <strong>{$req['manager_name']}</strong>!</p>"
                    . $obraHtml
                    . "<p>Precisamos que você envie a lista de materiais do ciclo de " . date('d/m/Y', strtotime($nextWeek)) . ".</p>"
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
                "Link enviado para {$req['manager_name']}" . ($siteLabel ? " ({$siteLabel})" : ''),
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

        // Cobrança age sobre o ciclo ativo mais recente já gerado
        $nextWeek = WeeklyMaterialRequest::latestCycleStart() ?: WeeklyMaterialRequest::nextCycleStart();
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
