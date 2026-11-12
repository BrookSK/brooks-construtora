<?php
/**
 * DEBUG SIMPLES - Encontrar erro do Painel de Obras
 * Acesse: https://www.brooksconstrutora.com.br/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== DEBUG PAINEL DE OBRAS ===\n";
echo "Hora: " . date('Y-m-d H:i:s') . "\n\n";

define('ROOT_PATH', __DIR__);

try {
    echo "1. Carregando Autoloader...\n";
    require_once __DIR__ . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    echo "   ✓ OK\n\n";
    
    echo "2. Testando PainelObrasService::getDashboardData()...\n";
    $result = \App\Services\PainelObrasService::getDashboardData(date('Y-m'), 'brooks');
    echo "   ✓ OK - Retornou dados\n\n";
    
    echo "3. Verificando estrutura do retorno:\n";
    $campos = ['kpis', 'obras', 'gasto_por_cat', 'orcado_por_cat', 'a_pagar_por_cat'];
    foreach ($campos as $campo) {
        $existe = isset($result[$campo]);
        echo "   - $campo: " . ($existe ? '✓ OK' : '✗ FALTA') . "\n";
    }
    echo "\n";
    
    echo "4. Valores:\n";
    echo "   - Total obras: " . ($result['kpis']['obras_total'] ?? 0) . "\n";
    echo "   - Contratado: R$ " . number_format($result['kpis']['contratado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Orçado: R$ " . number_format($result['kpis']['orcado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Gasto: R$ " . number_format($result['kpis']['gasto'] ?? 0, 2, ',', '.') . "\n";
    echo "   - orcado_por_cat: " . count($result['orcado_por_cat'] ?? []) . " categorias\n";
    echo "   - gasto_por_cat: " . count($result['gasto_por_cat'] ?? []) . " categorias\n\n";
    
    echo "5. Testando PainelObrasController::index()...\n";
    
    // Simula a requisição
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['company'] = 'brooks';
    $_GET['periodo'] = date('Y-m');
    
    ob_start();
    
    // Cria instância do controller
    $controller = new \App\Controllers\Admin\PainelObrasController();
    
    // Simula sessão
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['user'] = ['id' => 1, 'name' => 'Test'];
    
    // Tenta chamar o método index
    try {
        $controller->index();
        $output = ob_get_clean();
        echo "   ✓ Controller executou sem erros\n";
        echo "   Output length: " . strlen($output) . " bytes\n\n";
    } catch (\Throwable $e2) {
        ob_end_clean();
        throw $e2;
    }
    
    echo "=== TODOS OS TESTES PASSARAM ===\n";
    echo "O erro deve estar em outro lugar (Router, .htaccess, etc)\n";
    
} catch (\Throwable $e) {
    echo "\n❌ ERRO ENCONTRADO:\n\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
