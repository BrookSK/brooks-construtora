<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__);

$config = require __DIR__ . '/app/Config/app.php';
require_once __DIR__ . '/app/Core/Autoloader.php';

$autoloader = new App\Core\Autoloader();
$autoloader->register();

$app = new App\Core\Application($config);

echo "Verificando estrutura do snapshot do Nibo\n";
echo "==========================================\n\n";

$snapshot = App\Models\NiboSyncSnapshot::latest('brooks');

if (!$snapshot) {
    echo "❌ Nenhum snapshot encontrado!\n";
    exit;
}

echo "✓ Snapshot encontrado:\n";
echo "  ID: {$snapshot['id']}\n";
echo "  Company: {$snapshot['company']}\n";
echo "  Created at: {$snapshot['created_at']}\n\n";

$payload = App\Models\NiboSyncSnapshot::decodePayload($snapshot);

echo "Estrutura do payload:\n";
echo "=====================\n";
echo "Chaves principais: " . implode(", ", array_keys($payload)) . "\n\n";

foreach (array_keys($payload) as $key) {
    $value = $payload[$key];
    if (is_array($value)) {
        echo "- {$key}: array com " . count($value) . " itens\n";
        if (!empty($value)) {
            if (isset($value[0])) {
                echo "  Primeiro item: " . json_encode($value[0], JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "  Chaves: " . implode(", ", array_keys($value)) . "\n";
            }
        }
    } else {
        echo "- {$key}: " . (is_string($value) ? substr($value, 0, 100) : gettype($value)) . "\n";
    }
}

// Procura por "categories" em qualquer lugar
echo "\n\nProcurando 'categories' na estrutura:\n";
echo "=====================================\n";
function findCategories($arr, $path = '') {
    foreach ($arr as $key => $value) {
        $currentPath = $path ? "$path.$key" : $key;
        if (strtolower($key) === 'categories' || strpos(strtolower($key), 'categor') !== false) {
            echo "✓ ENCONTRADO em: {$currentPath}\n";
            if (is_array($value)) {
                echo "  Tipo: array com " . count($value) . " itens\n";
                if (!empty($value) && isset($value[0])) {
                    echo "  Sample: " . json_encode($value[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
        if (is_array($value) && count($value) < 100) { // Evita recursão em arrays muito grandes
            findCategories($value, $currentPath);
        }
    }
}
findCategories($payload);
