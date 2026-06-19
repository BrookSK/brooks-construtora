<?php
/**
 * Processador de Fila de Notificações
 * Executar via cron a cada minuto:
 * * * * * php /caminho/cron/process_notifications.php
 * 
 * Ou chamar via HTTP: /public/cron-notifications.php?token=SEU_TOKEN
 */

define('ROOT_PATH', dirname(__DIR__));
define('BACKGROUND_PROCESS', true);

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\NotificationQueue;
use App\Models\Setting;
use App\Services\MailService;

// Lock para evitar execução simultânea
$lockFile = ROOT_PATH . '/cron/notifications.lock';
if (file_exists($lockFile)) {
    $lockAge = time() - filemtime($lockFile);
    if ($lockAge < 120) { // Lock válido por 2 minutos
        exit('Lock ativo');
    }
}
file_put_contents($lockFile, date('Y-m-d H:i:s'));

try {
    $pending = NotificationQueue::getPending(30);
    
    if (empty($pending)) {
        @unlink($lockFile);
        exit('Nenhuma notificação pendente');
    }

    $mailService = null;
    $processed = 0;

    foreach ($pending as $notification) {
        NotificationQueue::markProcessing($notification['id']);

        try {
            if ($notification['type'] === 'email') {
                if (!$mailService) $mailService = new MailService();
                $mailService->send($notification['to_email'], $notification['subject'], $notification['body'], true);
                NotificationQueue::markSent($notification['id']);
            } elseif ($notification['type'] === 'webhook') {
                $ch = curl_init($notification['webhook_url']);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $notification['webhook_payload'],
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    NotificationQueue::markFailed($notification['id'], "cURL: {$error}");
                } elseif ($httpCode >= 400) {
                    NotificationQueue::markFailed($notification['id'], "HTTP {$httpCode}");
                } else {
                    NotificationQueue::markSent($notification['id']);
                }
            }
            $processed++;
        } catch (\Exception $e) {
            NotificationQueue::markFailed($notification['id'], $e->getMessage());
        }

        // Pequena pausa entre envios para não sobrecarregar SMTP
        usleep(500000); // 0.5s
    }

    echo "Processadas: {$processed}/" . count($pending);
} finally {
    @unlink($lockFile);
}
