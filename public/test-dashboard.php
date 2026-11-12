<?php
/**
 * Teste do Dashboard sem modificações
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Services/PainelObrasService.php';
require_once __DIR__ . '/../app/Services/NiboService.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Testando getDashboardData...\n\n";

try {
    $periodo = date('Y-m');
    $company = 'brooks';
    
    echo "Chamando PainelObrasService::getDashboardData('$periodo', '$company')...\n";
    
    $data = \App\Services\PainelObrasService::getDashboardData($periodo, $company);
    
    echo "✅ SUCESSO!\n\n";
    echo "Obras: " . count($data['obras'] ?? []) . "\n";
    echo "Contratado: R$ " . number_format($data['kpis']['contratado'] ?? 0, 2, ',', '.') . "\n";
    echo "Gasto: R$ " . number_format($data['kpis']['gasto'] ?? 0, 2, ',', '.') . "\n";
    
    echo "\n\n=== DASHBOARD FUNCIONA! ===\n";
    echo "Acesse: /admin/painel-obras\n";
    
} catch (\Throwable $e) {
    echo "❌ ERRO:\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
