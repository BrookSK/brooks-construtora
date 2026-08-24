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

echo "[weekly-cron:$action] Iniciando " . date('Y-m-d H:i:s') . "\n";

/**
 * Gera o próximo ciclo e envia os links agrupados por responsável.
 */
function wm_notify(string $baseUrl, string $webhookUrl): int
{
    $nextCycle = WeeklyMaterialRequest::nextCycleStart();
    $created = WeeklyMaterialRequest::createWeekRecords($nextCycle);
    echo "Ciclo {$nextCycle}: {$created} solicitação(ões) criada(s)\n";

    $requests = WeeklyMaterialRequest::getByWeek($nextCycle);
    $grouped = WeeklyMaterialController::groupPendingByManager($requests);
    $sent = WeeklyMaterialController::dispatchGroupedLinks($grouped, $nextCycle, $webhookUrl, $baseUrl);

    echo "Notificações enviadas para {$sent} responsável(is)\n";
    return $sent;
}

/**
 * Cobra os responsáveis com solicitação pendente no ciclo mais recente.
 */
function wm_remind(string $baseUrl, string $webhookUrl): int
{
    $cycle = WeeklyMaterialRequest::latestCycleStart() ?: WeeklyMaterialRequest::nextCycleStart();
    $requests = WeeklyMaterialRequest::getByWeek($cycle);
    // SÓ cobra quem já recebeu o link (notified_at preenchido)
    $grouped = WeeklyMaterialController::groupPendingByManager($requests, true);

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

    echo "Cobranças enviadas: {$sent}\n";
    if (!empty($pendingNames)) echo "Pendentes: " . implode(', ', $pendingNames) . "\n";
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
    $shouldNotify = false;

    if ($cycleMode === 'hourly') {
        // Modo teste: envia toda hora se houver pendências ou não houver ciclo hoje
        $shouldNotify = ($latest !== date('Y-m-d'));
    } elseif ($cycleMode === 'interval') {
        // A cada X dias, no horário configurado
        $shouldNotify = ($daysSinceLatest >= $interval) && ($currentHour >= $sendHour);
    } else {
        // daily/weekly: no dia da semana e horário configurados
        $shouldNotify = ($todayDow === $sendDay) && ($currentHour >= $sendHour) && ($latest !== date('Y-m-d'));
    }

    if ($shouldNotify) {
        echo "Agendamento atingido → gerando e enviando ciclo\n";
        wm_notify($baseUrl, $webhookUrl);
    } else {
        echo "Fora do horário/agendamento de envio (mode={$cycleMode}, dia={$sendDay}, hora={$sendTime}, últimoCiclo=" . ($latest ?: 'nenhum') . ")\n";
    }

    // Cobrança automática dos pendentes do ciclo atual
    if ($autoReminder) {
        wm_remind($baseUrl, $webhookUrl);
    }

    // Marcar ciclos passados como atrasados
    if ($autoOverdue) {
        $overdue = WeeklyMaterialRequest::markOverduePastWeeks(date('Y-m-d'));
        if ($overdue > 0) echo "Marcados como atrasados: {$overdue}\n";
    }
}

echo "[weekly-cron] Finalizado " . date('Y-m-d H:i:s') . "\n";
