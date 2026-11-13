<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__);

$config = require __DIR__ . '/app/Config/app.php';
require_once __DIR__ . '/app/Core/Autoloader.php';

$autoloader = new App\Core\Autoloader();
$autoloader->register();

$app = new App\Core\Application($config);

// Test categorias endpoint
echo "Testing /admin/painel-obras/obras/categorias\n\n";

$_GET['company'] = 'brooks';

$controller = new App\Controllers\Admin\PainelObrasController();

ob_start();
$controller->obrasCategorias();
$output = ob_get_clean();

echo "OUTPUT:\n";
echo $output;
echo "\n\n";

$json = json_decode($output, true);
if ($json) {
    echo "PARSED JSON:\n";
    echo "- Construção: " . count($json['Construção'] ?? []) . " categorias\n";
    echo "- Reforma: " . count($json['Reforma'] ?? []) . " categorias\n";
    
    if (!empty($json['Construção'])) {
        echo "\nPrimeiras 5 categorias:\n";
        foreach (array_slice($json['Construção'], 0, 5) as $cat) {
            echo "  - ID: {$cat['id']}, Nome: {$cat['nome']}, Tipo: {$cat['tipo']}\n";
        }
    }
    
    if (isset($json['erro'])) {
        echo "\n⚠️  ERRO: " . $json['erro'] . "\n";
    }
}
