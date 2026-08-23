<?php
/**
 * Cron: Notificações da Lista Semanal de Materiais
 * 
 * Executar toda terça-feira de manhã (enviar links para preenchimento)
 * e toda quinta-feira (cobrar quem não preencheu).
 * 
 * Sugestão de crontab:
 * 0 8 * * 2 php /caminho/cron/weekly_material_notifications.php --action=notify
 * 0 8 * * 4 php /caminho/cron/weekly_material_notifications.php --action=remind
 * 
 * Ou via HTTP: /public/cron-weekly-materials.php?token=SEU_TOKEN&action=notify|remind
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('BACKGROUND_PROCESS')) define('BACKGROUND_PROCESS', true);

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\WeeklyMaterialManager;
use App\Models\WeeklyMaterialRequest;
use App\Models\Setting;
use App\Services\NotificationService;

// Determinar ação
$action = 'notify';
if (php_sapi_name() === 'cli') {
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--action=')) {
            $action = str_replace('--action=', '', $arg);
        }
    }
} else {
    $action = $_GET['action'] ?? 'notify';
}

$baseUrl = Setting::get('site_url', 'https://brooksconstrutora.com.br');

echo "[$action] Iniciando às " . date('Y-m-d H:i:s') . "\n";

if ($action === 'notify') {
    // Permite forçar o disparo (ex.: botão "Enviar Agora" ou teste manual),
    // ignorando as travas de dia/horário: ?force=1 ou --force
    $force = false;
    if (php_sapi_name() === 'cli') {
        foreach ($argv ?? [] as $arg) { if ($arg === '--force') $force = true; }
    } else {
        $force = !empty($_GET['force']);
    }

    // Modo POR HORA (teste): ignora travas de dia/horário. O cron pode rodar
    // de hora em hora e o envio acontece assim que houver pendências.
    $hourly = Setting::get('weekly_cycle_mode', 'daily') === 'hourly';
    if ($hourly) { $force = true; }

    // ─── TRAVA DE DIA DA SEMANA (send_day) ───────────────────────────────
    // Só aplica quando o ciclo NÃO é diário. 1=Seg ... 7=Dom (ISO-8601).
    $interval = WeeklyMaterialRequest::cycleIntervalDays();
    if (!$force && $interval > 1) {
        $sendDay = (int) Setting::get('weekly_send_day', '1');
        $todayDow = (int) date('N');
        if ($sendDay > 0 && $todayDow !== $sendDay) {
            echo "Hoje não é o dia de envio (configurado: {$sendDay}, hoje: {$todayDow}).\n";
            echo "Finalizado às " . date('Y-m-d H:i:s') . "\n";
            return;
        }
    }

    // ─── TRAVA DE HORÁRIO (send_time) ────────────────────────────────────
    // Só envia se a hora atual já alcançou o horário configurado. Assim o
    // cron do servidor pode rodar de hora em hora (ou a cada X min).
    if (!$force) {
        $sendTime = Setting::get('weekly_send_time', '08:00');
        $sendMinutes = 0;
        if (preg_match('/^(\d{1,2}):(\d{2})$/', trim($sendTime), $m)) {
            $sendMinutes = ((int) $m[1]) * 60 + (int) $m[2];
        }
        $nowMinutes = ((int) date('H')) * 60 + (int) date('i');
        if ($nowMinutes < $sendMinutes) {
            echo "Ainda não chegou o horário de envio (configurado: {$sendTime}, agora: " . date('H:i') . ").\n";
            echo "Finalizado às " . date('Y-m-d H:i:s') . "\n";
            return;
        }
    }

    // Gera e envia respeitando o INTERVALO (X) e a ANTECEDÊNCIA DE ENVIO (Y).
    //  - X = frequência do ciclo (a cada X dias)
    //  - Y = envia o link Y dias ANTES do próximo ciclo
    // Ex.: X=15 e Y=5 → o link é enviado no dia 10 após o último ciclo
    // (5 dias antes do próximo ciclo, que cai no dia 15).
    $notifyAdvance = (int) Setting::get('weekly_notify_advance_days', '5');
    // A antecedência de envio não pode ser maior que o intervalo
    $notifyAdvance = max(0, min($notifyAdvance, $interval));
    $latest = WeeklyMaterialRequest::latestCycleStart();
    $today = strtotime(date('Y-m-d'));

    if ($latest && !$force) {
        // Dia de envio = último ciclo + (intervalo - antecedência de envio)
        $sendOffset = max(0, $interval - $notifyAdvance);
        $due = strtotime($latest . ' +' . $sendOffset . ' days');
        if ($today < $due) {
            echo "Ainda não é hora de enviar. Último ciclo: {$latest}, intervalo: {$interval} dias, antecedência de envio: {$notifyAdvance} dias. Envio em " . date('Y-m-d', $due) . ".\n";
            echo "Finalizado às " . date('Y-m-d H:i:s') . "\n";
            return;
        }
    }

    $nextWeek = WeeklyMaterialRequest::nextCycleStart();

    // Marca ciclos anteriores pendentes como atrasados antes de abrir o novo
    WeeklyMaterialRequest::markOverduePastWeeks($nextWeek);

    // Criar registros se não existem
    $created = WeeklyMaterialRequest::createWeekRecords($nextWeek);
    echo "Registros criados: {$created} (ciclo {$nextWeek}, intervalo {$interval} dias)\n";

    // Buscar registros pendentes da semana
    $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
    $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
    $dataFmt = date('d/m/Y', strtotime($nextWeek));

    // AGRUPAR por responsável → uma ÚNICA mensagem com todos os links (obra + link)
    $grouped = [];
    foreach ($requests as $req) {
        if ($req['status'] !== 'pending') continue;
        if (!empty($req['notified_at'])) continue;
        $mid = (int) $req['manager_id'];
        if (!isset($grouped[$mid])) {
            $grouped[$mid] = [
                'name' => $req['manager_name'],
                'phone' => $req['manager_phone'] ?? '',
                'email' => $req['manager_email'] ?? '',
                'items' => [],
            ];
        }
        $siteLabel = !empty($req['construction_site_name'])
            ? ((!empty($req['construction_site_code']) ? $req['construction_site_code'] . ' - ' : '') . $req['construction_site_name'])
            : 'Obra não especificada';
        $grouped[$mid]['items'][] = ['site' => $siteLabel, 'token' => $req['token'], 'req_id' => (int) $req['id']];
    }

    $sent = 0;
    foreach ($grouped as $g) {
        $linhas = ''; $linhasHtml = '';
        foreach ($g['items'] as $it) {
            $url = $baseUrl . '/lista-semanal/' . $it['token'];
            $linhas .= "🏗️ *{$it['site']}*\n{$url}\n\n";
            $linhasHtml .= "<p style=\"margin:0 0 4px;\"><strong>🏗️ " . htmlspecialchars($it['site']) . "</strong><br><a href=\"{$url}\">{$url}</a></p>";
        }
        $totalObras = count($g['items']);
        $message = "Olá {$g['name']}! 📋\n\n"
            . "Envie a lista de materiais do ciclo de {$dataFmt}.\n"
            . ($totalObras > 1 ? "Você é responsável por {$totalObras} obras. Preencha uma solicitação para cada:\n\n" : "\n")
            . $linhas
            . "Obrigado!";

        if ($webhookUrl && !empty($g['phone'])) {
            NotificationService::queueWebhook($webhookUrl, [
                'phone' => $g['phone'],
                'name' => $g['name'],
                'message' => $message,
                'type' => 'weekly_material_request',
            ]);
        }
        if (!empty($g['email'])) {
            NotificationService::queueEmails(
                $g['email'],
                'Lista Semanal de Materiais - Ciclo ' . date('d/m', strtotime($nextWeek)),
                "<p>Olá <strong>" . htmlspecialchars($g['name']) . "</strong>!</p>"
                . "<p>Envie a lista de materiais do ciclo de {$dataFmt}."
                . ($totalObras > 1 ? " Você é responsável por {$totalObras} obras — preencha uma para cada:" : "") . "</p>"
                . $linhasHtml
            );
        }

        foreach ($g['items'] as $it) {
            WeeklyMaterialRequest::updateById($it['req_id'], ['notified_at' => date('Y-m-d H:i:s')]);
        }
        $sent++;
    }

    echo "Notificações enviadas: {$sent} responsável(is) (mensagem única com todos os links)\n";

} elseif ($action === 'remind') {
    // Cobrança sobre o ciclo ativo mais recente
    $nextWeek = WeeklyMaterialRequest::latestCycleStart() ?: WeeklyMaterialRequest::nextCycleStart();
    $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
    $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
    $adminWebhook = Setting::get('orders_weekly_materials_admin_webhook', '');
    $adminPhone = Setting::get('orders_weekly_materials_admin_phone', '');
    $adminName = Setting::get('orders_weekly_materials_admin_name', '');
    $pendingNames = [];
    $sent = 0;

    foreach ($requests as $req) {
        if ($req['status'] !== 'pending') continue;
        if (!empty($req['reminder_sent_at'])) continue;

        $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
        $pendingNames[] = $req['manager_name'];

        $message = "⚠️ {$req['manager_name']}, você ainda NÃO preencheu a lista de materiais da semana de "
            . date('d/m/Y', strtotime($nextWeek)) . "!\n\n"
            . "Por favor, preencha o mais rápido possível:\n{$formUrl}";

        // Webhook pro gerente
        if ($webhookUrl && !empty($req['manager_phone'])) {
            NotificationService::queueWebhook($webhookUrl, [
                'phone' => $req['manager_phone'],
                'name' => $req['manager_name'],
                'message' => $message,
                'type' => 'weekly_material_reminder',
            ]);
        }

        // Email pro gerente
        if (!empty($req['manager_email'])) {
            NotificationService::queueEmails(
                $req['manager_email'],
                '⚠️ PENDENTE - Lista Semanal de Materiais',
                "<p><strong>⚠️ {$req['manager_name']}</strong>, você ainda não preencheu!</p>"
                . "<p>A lista de materiais da semana de " . date('d/m/Y', strtotime($nextWeek)) . " está pendente.</p>"
                . "<p><a href=\"{$formUrl}\" style=\"background:#dc3545; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none;\">Preencher Agora</a></p>"
            );
        }

        WeeklyMaterialRequest::updateById($req['id'], ['reminder_sent_at' => date('Y-m-d H:i:s')]);
        $sent++;
    }

    // Notificar admin sobre pendentes
    if (!empty($pendingNames) && $adminWebhook && $adminPhone) {
        $adminMessage = "📋 Lista Semanal — " . count($pendingNames) . " gerente(s) NÃO preencheram:\n\n"
            . implode("\n", array_map(fn($n) => "❌ {$n}", $pendingNames))
            . "\n\nSemana: " . date('d/m/Y', strtotime($nextWeek));

        NotificationService::queueWebhook($adminWebhook, [
            'phone' => $adminPhone,
            'name' => $adminName ?: 'Admin',
            'message' => $adminMessage,
            'type' => 'weekly_material_admin_alert',
        ]);
    }

    echo "Lembretes enviados: {$sent}\n";
    echo "Pendentes: " . implode(', ', $pendingNames) . "\n";
}

echo "Finalizado às " . date('Y-m-d H:i:s') . "\n";
