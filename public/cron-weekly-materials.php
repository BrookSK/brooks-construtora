<?php
/**
 * Endpoint HTTP para cron de lista semanal de materiais
 * URL: /cron-weekly-materials.php?token=XXX&action=notify|remind
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

// Validar token
$token = $_GET['token'] ?? '';
$cronToken = App\Models\Setting::get('cron_token', '');

if (!$token || $token !== $cronToken) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Executar o cron
require ROOT_PATH . '/cron/weekly_material_notifications.php';
