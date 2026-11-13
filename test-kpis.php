<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__);

$config = require __DIR__ . '/app/Config/app.php';
require_once __DIR__ . '/app/Core/Autoloader.php';

$autoloader = new App\Core\Autoloader();
$autoloader->register();

$app = new App\Core\Application($config);

echo "Testing KPIs data...\n\n";

$data = App\Services\PainelObrasService::getDashboardData('2024-11', 'brooks');

echo "KPIs:\n";
echo "=====\n";
foreach ($data['kpis'] as $key => $value) {
    if (is_numeric($value)) {
        echo "- {$key}: R$ " . number_format($value, 2, ',', '.') . "\n";
    } else {
        echo "- {$key}: {$value}\n";
    }
}

echo "\n\nCálculo manual:\n";
echo "===============\n";
echo "Contratado: R$ " . number_format($data['kpis']['contratado'], 2, ',', '.') . "\n";
echo "Gasto: R$ " . number_format($data['kpis']['gasto'], 2, ',', '.') . "\n";
echo "Saldo (manual): R$ " . number_format($data['kpis']['contratado'] - $data['kpis']['gasto'], 2, ',', '.') . "\n";
echo "Saldo (retornado): R$ " . number_format($data['kpis']['saldo'] ?? 0, 2, ',', '.') . "\n";
