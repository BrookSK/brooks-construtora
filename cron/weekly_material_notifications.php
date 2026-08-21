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
    // Terça: gerar registros da semana e enviar link para gerentes
    $nextWeek = WeeklyMaterialRequest::nextWeekStart();
    
    // Criar registros se não existem
    $created = WeeklyMaterialRequest::createWeekRecords($nextWeek);
    echo "Registros criados: {$created}\n";

    // Buscar registros pendentes da semana
    $requests = WeeklyMaterialRequest::getByWeek($nextWeek);
    $webhookUrl = Setting::get('orders_weekly_materials_webhook', '');
    $sent = 0;

    foreach ($requests as $req) {
        if ($req['status'] !== 'pending') continue;
        if (!empty($req['notified_at'])) continue;

        $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
        $message = "Olá {$req['manager_name']}! 📋\n\n"
            . "Preciso que você envie a lista de materiais que vai precisar na semana de "
            . date('d/m/Y', strtotime($nextWeek)) . ".\n\n"
            . "Acesse o link abaixo e preencha:\n{$formUrl}\n\n"
            . "Obrigado!";

        // Enviar via webhook (WhatsApp)
        if ($webhookUrl && !empty($req['manager_phone'])) {
            NotificationService::queueWebhook($webhookUrl, [
                'phone' => $req['manager_phone'],
                'name' => $req['manager_name'],
                'message' => $message,
                'type' => 'weekly_material_request',
            ]);
        }

        // Enviar via email
        if (!empty($req['manager_email'])) {
            try {
                \App\Services\MailService::send(
                    $req['manager_email'],
                    'Lista Semanal de Materiais - Semana ' . date('d/m', strtotime($nextWeek)),
                    "<p>Olá <strong>{$req['manager_name']}</strong>!</p>"
                    . "<p>Precisamos que você envie a lista de materiais da semana de " . date('d/m/Y', strtotime($nextWeek)) . ".</p>"
                    . "<p><a href=\"{$formUrl}\" style=\"background:#3a3b4e; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none;\">Preencher Lista</a></p>"
                    . "<p>Obrigado!</p>"
                );
            } catch (\Exception $e) {
                echo "Erro email {$req['manager_email']}: {$e->getMessage()}\n";
            }
        }

        // Marcar como notificado
        WeeklyMaterialRequest::updateById($req['id'], ['notified_at' => date('Y-m-d H:i:s')]);
        $sent++;
    }

    echo "Notificações enviadas: {$sent}\n";

} elseif ($action === 'remind') {
    // Quinta: cobrar quem não preencheu
    $nextWeek = WeeklyMaterialRequest::nextWeekStart();
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
            try {
                \App\Services\MailService::send(
                    $req['manager_email'],
                    '⚠️ PENDENTE - Lista Semanal de Materiais',
                    "<p><strong>⚠️ {$req['manager_name']}</strong>, você ainda não preencheu!</p>"
                    . "<p>A lista de materiais da semana de " . date('d/m/Y', strtotime($nextWeek)) . " está pendente.</p>"
                    . "<p><a href=\"{$formUrl}\" style=\"background:#dc3545; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none;\">Preencher Agora</a></p>"
                );
            } catch (\Exception $e) {}
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
