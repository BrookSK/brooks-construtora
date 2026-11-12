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
    
    echo "2. Inicializando Application...\n";
    $app = \App\Core\Application::getInstance();
    if (!$app) {
        echo "   ⚠ Application null, criando nova instância...\n";
        // Carrega configuração
        $config = require __DIR__ . '/app/Config/app.php';
        $app = new \App\Core\Application($config);
    }
    echo "   ✓ OK\n\n";
    
    echo "3. Testando conexão com banco...\n";
    $testDb = \App\Core\Database::fetch("SELECT 1 as test");
    echo "   ✓ OK - Banco conectado\n\n";
    
    echo "4. Testando PainelObrasService::getDashboardData()...\n";
    $result = \App\Services\PainelObrasService::getDashboardData(date('Y-m'), 'brooks');
    echo "   ✓ OK - Retornou dados\n\n";
    
    echo "5. Verificando estrutura do retorno:\n";
    $campos = ['kpis', 'obras', 'gasto_por_cat', 'orcado_por_cat', 'a_pagar_por_cat'];
    foreach ($campos as $campo) {
        $existe = isset($result[$campo]);
        echo "   - $campo: " . ($existe ? '✓ OK' : '✗ FALTA') . "\n";
    }
    echo "\n";
    
    echo "6. Valores:\n";
    echo "   - Total obras: " . ($result['kpis']['obras_total'] ?? 0) . "\n";
    echo "   - Contratado: R$ " . number_format($result['kpis']['contratado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Orçado: R$ " . number_format($result['kpis']['orcado'] ?? 0, 2, ',', '.') . "\n";
    echo "   - Gasto: R$ " . number_format($result['kpis']['gasto'] ?? 0, 2, ',', '.') . "\n";
    echo "   - orcado_por_cat: " . count($result['orcado_por_cat'] ?? []) . " categorias\n";
    echo "   - gasto_por_cat: " . count($result['gasto_por_cat'] ?? []) . " categorias\n\n";
    
    if (!empty($result['orcado_por_cat'])) {
        echo "7. Categorias com orçamento:\n";
        foreach ($result['orcado_por_cat'] as $cat => $valor) {
            echo "   - $cat: R$ " . number_format($valor, 2, ',', '.') . "\n";
        }
        echo "\n";
    }
    
    echo "=== TODOS OS TESTES PASSARAM ===\n";
    echo "\n✅ O código está funcionando!\n";
    echo "O erro 500 na rota /admin/painel-obras deve ser por outro motivo.\n";
    echo "Possíveis causas:\n";
    echo "  - Problema no Router\n";
    echo "  - Middleware de autenticação\n";
    echo "  - Sessão não iniciada\n\n";
    
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
