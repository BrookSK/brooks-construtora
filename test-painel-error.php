<?php
// Teste direto do erro do Painel de Obras

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__);

require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

try {
    echo "=== TESTE DE ERRO DO PAINEL ===\n\n";
    
    // Tenta carregar o service
    echo "1. Carregando PainelObrasService...\n";
    $service = new \App\Services\PainelObrasService();
    echo "   ✓ Service carregado\n\n";
    
    // Tenta chamar o método
    echo "2. Chamando getDashboardData()...\n";
    $data = \App\Services\PainelObrasService::getDashboardData(date('Y-m'), 'brooks');
    echo "   ✓ getDashboardData executado\n\n";
    
    // Verifica estrutura
    echo "3. Verificando estrutura do retorno...\n";
    echo "   - kpis: " . (isset($data['kpis']) ? '✓' : '✗') . "\n";
    echo "   - obras: " . (isset($data['obras']) ? '✓' : '✗') . "\n";
    echo "   - gasto_por_cat: " . (isset($data['gasto_por_cat']) ? '✓' : '✗') . "\n";
    echo "   - orcado_por_cat: " . (isset($data['orcado_por_cat']) ? '✓' : '✗') . "\n";
    echo "   - a_pagar_por_cat: " . (isset($data['a_pagar_por_cat']) ? '✓' : '✗') . "\n\n";
    
    echo "4. Valores:\n";
    echo "   - Total obras: " . ($data['kpis']['obras_total'] ?? 0) . "\n";
    echo "   - Contratado: R$ " . number_format($data['kpis']['contratado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Orçado: R$ " . number_format($data['kpis']['orcado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Gasto: R$ " . number_format($data['kpis']['gasto'] ?? 0, 2, ',', '.') . "\n\n";
    
    echo "=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    
} catch (\Throwable $e) {
    echo "\n❌ ERRO ENCONTRADO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
