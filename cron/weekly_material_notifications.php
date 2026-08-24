<?php
/**
 * Cron: Lista Semanal de Materiais (ciclos de solicitação)
 *
 * Respeita a configuração de "Automação Semanal" da tela
 * /admin/weekly-materials e faz exatamente o mesmo que os botões
 * "Enviar Agora" e "Cobrar Pendentes":
 *   - Gera o próximo ciclo (respeitando o intervalo X)
 *   - Envia UMA mensagem por responsável com todos os links das obras
 *   - Cobra pendentes (se auto_reminder estiver ligado)
 *   - Marca ciclos passados como atrasados (se auto_overdue estiver ligado)
 *
 * Chamado pelo endpoint público /cron-weekly-materials.php?token=XXX
 * O parâmetro action é opcional:
 *   (vazio) / auto  → decide sozinho pelo agendamento (recomendado p/ cron horário)
 *   notify           → força a geração + envio do próximo ciclo agora
 *   remind           → força a cobrança dos pendentes agora
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('BACKGROUND_PROCESS')) define('BACKGROUND_PROCESS', true);

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\WeeklyMaterialRequest;
use App\Models\Setting;
use App\Controllers\Admin\WeeklyMaterialController;

// Determinar ação
$action = 'auto';
if (php_sapi_name() === 'cli') {
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--action=')) {
            $action = str_replace('--action=', '', $arg);
        }
    }
} else {
    $action = $_GET['action'] ?? 'auto';
}

$baseUrl = Setting::get('site_url', 'https://www.brooksconstrutora.com.br');
$webhookUrl = Setting::get('orders_weekly_materials_webhook', '');

// Log persistente para diagnóstico (visível mesmo sem ver a saída do cron)
function wm_log(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    echo $line;
    $logFile = ROOT_PATH . '/cron/weekly_cron.log';
    @file_put_contents($logFile, $line, FILE_APPEND);
}

wm_log("[weekly-cron:$action] Iniciando");

/**
 * Gera o próximo ciclo e envia os links agrupados por responsável.
 */
function wm_notify(string $baseUrl, string $webhookUrl): int
{
    $nextCycle = WeeklyMaterialRequest::nextCycleStart();

    // Diagnóstico: quantos gerentes têm obra vinculada na fase 'weekly'?
    $managerCount = \App\Core\Database::fetch(
        "SELECT COUNT(DISTINCT pu.id) AS n
         FROM pin_users pu
         JOIN construction_site_approvers csa ON csa.pin_user_id = pu.id
         JOIN construction_sites cs ON cs.id = csa.construction_site_id
         WHERE pu.active = 1 AND csa.phase = 'weekly' AND cs.status = 'active'"
    );
    $nManagers = (int) ($managerCount['n'] ?? 0);
    wm_log("Gerentes vinculados na fase 'weekly': {$nManagers}");

    if ($nManagers === 0) {
        wm_log("NENHUM gerente vinculado a obra na fase 'Lista Semanal'. Configure em Obras > editar > aba Lista Semanal. Nada a criar.");
        return 0;
    }

    $created = WeeklyMaterialRequest::createWeekRecords($nextCycle);
    wm_log("Ciclo {$nextCycle}: {$created} solicitação(ões) criada(s)");

    $requests = WeeklyMaterialRequest::getByWeek($nextCycle);
    // Só enviar para quem AINDA NÃO recebeu o link (evita reenvio duplicado)
    $notYetNotified = array_filter($requests, fn($r) => empty($r['notified_at']));
    if (empty($notYetNotified)) {
        wm_log("Todos os links deste ciclo já foram enviados. Nada a reenviar.");
        return 0;
    }
    $grouped = WeeklyMaterialController::groupPendingByManager(array_values($notYetNotified));
    $sent = WeeklyMaterialController::dispatchGroupedLinks($grouped, $nextCycle, $webhookUrl, $baseUrl);

    wm_log("Notificações enviadas para {$sent} responsável(is)");
    return $sent;
}

/**
 * Cobra os responsáveis com solicitação pendente no ciclo mais recente.
 *
 * Regras (evitam spam):
 *   - Só cobra quem JÁ recebeu o link (notified_at preenchido)
 *   - Só cobra depois de passado um tempo mínimo desde o envio (grace period)
 *   - Só cobra 1x por dia por solicitação (reminder_sent_at não é de hoje)
 */
function wm_remind(string $baseUrl, string $webhookUrl): int
{
    $cycle = WeeklyMaterialRequest::latestCycleStart() ?: WeeklyMaterialRequest::nextCycleStart();
    $requests = WeeklyMaterialRequest::getByWeek($cycle);

    // Período de carência: quantas horas esperar após o envio antes de cobrar.
    // Baseado no prazo de resposta configurado.
    $deadlineCfg = Setting::get('weekly_response_deadline', 'same_day_18');
    $graceHours = 4; // padrão: cobra só 4h depois de enviar
    if ($deadlineCfg === 'next_day') $graceHours = 24;
    elseif ($deadlineCfg === 'same_day_18') $graceHours = 4;
    elseif ($deadlineCfg === '48h') $graceHours = 48;

    $today = date('Y-m-d');
    $now = time();

    // Filtrar apenas itens elegíveis para cobrança
    $eligible = array_filter($requests, function ($req) use ($graceHours, $today, $now) {
        if ($req['status'] === 'filled') return false;
        if (!empty($req['order_id'])) return false;
        if ($req['status'] !== 'pending') return false;
        // Precisa ter sido notificado
        if (empty($req['notified_at'])) return false;
        // Precisa ter passado o período de carência desde o envio
        $hoursSinceNotify = ($now - strtotime($req['notified_at'])) / 3600;
        if ($hoursSinceNotify < $graceHours) return false;
        // Não cobrar de novo se já cobrou hoje
        if (!empty($req['reminder_sent_at']) && date('Y-m-d', strtotime($req['reminder_sent_at'])) === $today) return false;
        return true;
    });

    if (empty($eligible)) {
        wm_log("Nenhuma solicitação elegível para cobrança (carência {$graceHours}h / 1x por dia).");
        return 0;
    }

    $grouped = WeeklyMaterialController::groupPendingByManager(array_values($eligible), true);

    $adminWebhook = Setting::get('orders_weekly_materials_admin_webhook', '');
    $adminPhone = Setting::get('orders_weekly_materials_admin_phone', '');
    $adminName = Setting::get('orders_weekly_materials_admin_name', '');
    $dataFmt = date('d/m/Y', strtotime($cycle));
    $pendingNames = [];
    $sent = 0;

    foreach ($grouped as $g) {
        $pendingNames[] = $g['name'];

        $linhas = '';
        foreach ($g['items'] as $it) {
            $url = $baseUrl . '/lista-semanal/' . $it['token'];
            $linhas .= "🏗️ *{$it['site']}*\n{$url}\n\n";
        }
        $totalObras = count($g['items']);
        $message = "⚠️ {$g['name']}, você ainda NÃO preencheu a lista de materiais do ciclo de {$dataFmt}!\n\n"
            . ($totalObras > 1 ? "Pendentes ({$totalObras} obras):\n\n" : "")
            . $linhas
            . "Por favor, preencha o quanto antes.";

        if ($webhookUrl && !empty($g['phone'])) {
            \App\Services\NotificationService::queueWebhook($webhookUrl, [
                'phone' => $g['phone'],
                'name' => $g['name'],
                'message' => $message,
                'type' => 'weekly_material_reminder',
            ]);
            $sent++;
        }

        foreach ($g['items'] as $it) {
            WeeklyMaterialRequest::updateById($it['req_id'], ['reminder_sent_at' => date('Y-m-d H:i:s')]);
            \App\Models\WeeklyMaterialLog::record(
                \App\Models\WeeklyMaterialLog::ACTION_REMINDER_SENT,
                $it['req_id'],
                "Cobrança automática enviada para {$g['name']} ({$it['site']})",
                $cycle
            );
        }
    }

    if (!empty($pendingNames) && $adminWebhook && $adminPhone) {
        $adminMessage = "📋 Lista Semanal — " . count($pendingNames) . " gerente(s) NÃO preencheram:\n\n"
            . implode("\n", array_map(fn($n) => "❌ {$n}", $pendingNames))
            . "\n\nCiclo: " . $dataFmt;
        \App\Services\NotificationService::queueWebhook($adminWebhook, [
            'phone' => $adminPhone,
            'name' => $adminName ?: 'Admin',
            'message' => $adminMessage,
            'type' => 'weekly_material_admin_alert',
        ]);
    }

    wm_log("Cobranças enviadas: {$sent}");
    if (!empty($pendingNames)) wm_log("Pendentes: " . implode(', ', $pendingNames));
    return $sent;
}

// ─── Execução ────────────────────────────────────────────────────────────

if ($action === 'notify') {
    wm_notify($baseUrl, $webhookUrl);

} elseif ($action === 'remind') {
    wm_remind($baseUrl, $webhookUrl);

} else {
    // Modo automático: decide pelo agendamento configurado
    $cycleMode = Setting::get('weekly_cycle_mode', 'daily');       // daily | interval | hourly (teste)
    $sendDay = (int) Setting::get('weekly_send_day', '1');         // 1=Seg ... 7=Dom
    $sendTime = Setting::get('weekly_send_time', '08:00');         // HH:MM
    $autoReminder = Setting::get('weekly_auto_reminder', '1') === '1';
    $autoOverdue = Setting::get('weekly_auto_overdue', '1') === '1';

    $todayDow = (int) date('N'); // 1=Seg ... 7=Dom
    $currentHour = (int) date('H');
    $sendHour = (int) explode(':', $sendTime)[0];

    $latest = WeeklyMaterialRequest::latestCycleStart();
    $interval = WeeklyMaterialRequest::cycleIntervalDays();
    $daysSinceLatest = $latest ? (int) ((strtotime(date('Y-m-d')) - strtotime($latest)) / 86400) : PHP_INT_MAX;

    // Deve gerar/enviar novo ciclo?
    // Regra base: só gera um novo ciclo quando o intervalo configurado já passou
    // desde o último ciclo. Isso evita criar ciclo/enviar link repetidamente.
    $intervalPassed = ($daysSinceLatest >= $interval);
    $shouldNotify = false;

    if ($cycleMode === 'hourly') {
        // Modo teste: gera 1 ciclo por dia no máximo (não a cada hora).
        // Só cria se ainda não existe ciclo criado hoje.
        $shouldNotify = ($latest !== date('Y-m-d'));
    } elseif ($cycleMode === 'interval') {
        // A cada X dias, a partir do horário configurado
        $shouldNotify = $intervalPassed && ($currentHour >= $sendHour);
    } else {
        // daily/weekly: no dia da semana e horário configurados, respeitando o intervalo
        $shouldNotify = ($todayDow === $sendDay) && ($currentHour >= $sendHour) && $intervalPassed;
    }

    if ($shouldNotify) {
        wm_log("Agendamento atingido → gerando e enviando ciclo");
        wm_notify($baseUrl, $webhookUrl);
    } else {
        wm_log("Fora do horário/agendamento de envio (mode={$cycleMode}, dia={$sendDay}, hora={$sendTime}, hora_atual={$currentHour}, últimoCiclo=" . ($latest ?: 'nenhum') . ", diasDesdeÚltimo={$daysSinceLatest}, intervalo={$interval})");
    }

    // Cobrança automática dos pendentes do ciclo atual
    if ($autoReminder) {
        wm_remind($baseUrl, $webhookUrl);
    }

    // Marcar ciclos passados como atrasados
    if ($autoOverdue) {
        $overdue = WeeklyMaterialRequest::markOverduePastWeeks(date('Y-m-d'));
        if ($overdue > 0) wm_log("Marcados como atrasados: {$overdue}");
    }
}

wm_log("[weekly-cron] Finalizado");
