<?php
/**
 * Debug do Painel de Obras
 * Acesse: https://www.brooksconstrutora.com.br/debug-painel.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="background:#1a1a1a;color:#00ff00;padding:20px;font-family:monospace">';
echo "🔍 DEBUG PAINEL DE OBRAS\n";
echo str_repeat('=', 60) . "\n\n";

try {
    echo "✓ Bootstrap carregado\n";
    
    require_once __DIR__ . '/../app/Core/Auth.php';
    echo "✓ Auth carregado\n";
    
    require_once __DIR__ . '/../app/Services/PainelObrasService.php';
    echo "✓ PainelObrasService carregado\n";
    
    require_once __DIR__ . '/../app/Core/Database.php';
    echo "✓ Database carregado\n\n";
    
    echo "Testando getDashboardData...\n";
    echo str_repeat('-', 60) . "\n";
    
    $periodo = date('Y-m');
    $company = 'brooks';
    
    echo "Período: $periodo\n";
    echo "Company: $company\n\n";
    
    $data = \App\Services\PainelObrasService::getDashboardData($periodo, $company);
    
    echo "✓ getDashboardData() executado com sucesso!\n\n";
    
    echo "Resultados:\n";
    echo "  - Total de obras: " . count($data['obras'] ?? []) . "\n";
    echo "  - KPIs: " . json_encode($data['kpis'] ?? [], JSON_PRETTY_PRINT) . "\n";
    echo "  - Gasto por categoria: " . count($data['gasto_por_cat'] ?? []) . "\n";
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "✅ TUDO OK! O dashboard deve funcionar normalmente.\n";
    echo "\nDelete este arquivo (debug-painel.php) após usar.\n";
    
} catch (\Throwable $e) {
    echo "\n❌ ERRO ENCONTRADO:\n";
    echo str_repeat('=', 60) . "\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo '</pre>';
