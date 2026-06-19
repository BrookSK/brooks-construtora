<?php
/**
 * Endpoint HTTP para processar fila de notificações
 * Chamar: /cron-notifications.php?token=SEU_CRON_TOKEN
 * Pode ser acionado por um serviço de cron externo (cron-job.org, etc.)
 */

define('ROOT_PATH', dirname(__DIR__));
define('BACKGROUND_PROCESS', true);

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\Setting;

// Validar token
$token = $_GET['token'] ?? '';
$cronToken = Setting::get('cron_token', '');

if (empty($cronToken) || $token !== $cronToken) {
    http_response_code(403);
    die('Token inválido');
}

// Executar processador
require ROOT_PATH . '/cron/process_notifications.php';
