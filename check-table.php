<?php
/**
 * Verificar estrutura da tabela
 * Acesse: https://www.brooksconstrutora.com.br/check-table.php
 */

define('ROOT_PATH', __DIR__);

require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$config = require __DIR__ . '/app/Config/app.php';
$app = new \App\Core\Application($config);

echo "<pre>";
echo "=== ESTRUTURA DA TABELA painel_obras_custos_previstos ===\n\n";

try {
    $colunas = \App\Core\Database::fetchAll("DESCRIBE painel_obras_custos_previstos");
    
    echo "Colunas encontradas:\n";
    foreach ($colunas as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n\n=== DADOS NA TABELA ===\n";
    $count = \App\Core\Database::fetch("SELECT COUNT(*) as total FROM painel_obras_custos_previstos");
    echo "Total de registros: " . $count['total'] . "\n";
    
    if ($count['total'] > 0) {
        echo "\nPrimeiros 5 registros:\n";
        $registros = \App\Core\Database::fetchAll("SELECT * FROM painel_obras_custos_previstos LIMIT 5");
        print_r($registros);
    }
    
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
