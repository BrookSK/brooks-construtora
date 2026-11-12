<?php
/**
 * Teste direto do PainelObrasController::index()
 * Captura e exibe erros detalhados
 */

// Habilita todos os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste Painel Obras</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-radius:5px;overflow:auto;}";
echo ".error{color:#f48771;} .success{color:#4ec9b0;} .info{color:#9cdcfe;}</style>";
echo "</head><body>";

echo "<h1>Teste Painel Obras - Diagnóstico 500</h1>";

try {
    echo "<div class='info'><h2>1. Carregando autoloader...</h2></div>";
    require_once __DIR__ . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    echo "<div class='success'>✓ Autoloader carregado</div>";
    
    echo "<div class='info'><h2>2. Testando PainelObrasService...</h2></div>";
    
    // Testa apenas a classe
    if (class_exists('\App\Services\PainelObrasService')) {
        echo "<div class='success'>✓ Classe PainelObrasService encontrada</div>";
    } else {
        echo "<div class='error'>✗ Classe PainelObrasService NÃO encontrada</div>";
        exit;
    }
    
    // Testa sintaxe do arquivo
    echo "<div class='info'><h3>2.1. Verificando sintaxe do arquivo...</h3></div>";
    $file = __DIR__ . '/app/Services/PainelObrasService.php';
    $content = file_get_contents($file);
    
    // Conta chaves
    $openBraces = substr_count($content, '{');
    $closeBraces = substr_count($content, '}');
    echo "<pre>";
    echo "Chaves abertas:   {$openBraces}\n";
    echo "Chaves fechadas:  {$closeBraces}\n";
    echo "Diferença:        " . ($openBraces - $closeBraces) . "\n";
    echo "</pre>";
    
    if ($openBraces !== $closeBraces) {
        echo "<div class='error'>✗ ERRO: Chaves desbalanceadas!</div>";
    } else {
        echo "<div class='success'>✓ Chaves balanceadas</div>";
    }
    
    // Testa se o método existe
    echo "<div class='info'><h3>2.2. Verificando métodos...</h3></div>";
    $reflection = new \ReflectionClass('\App\Services\PainelObrasService');
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    
    echo "<pre>Métodos públicos encontrados:\n";
    foreach ($methods as $method) {
        echo "  - " . $method->getName() . "()\n";
    }
    echo "</pre>";
    
    if ($reflection->hasMethod('getDashboardData')) {
        echo "<div class='success'>✓ Método getDashboardData existe</div>";
    } else {
        echo "<div class='error'>✗ Método getDashboardData NÃO existe</div>";
        exit;
    }
    
    echo "<div class='info'><h2>3. Testando chamada real (pode falhar se faltar auth/db)...</h2></div>";
    
    try {
        $result = \App\Services\PainelObrasService::getDashboardData('2024-11', 'brooks');
        echo "<div class='success'>✓ getDashboardData() executou sem erros!</div>";
        echo "<pre>";
        echo "Estrutura retornada:\n";
        echo "  - kpis: " . (isset($result['kpis']) ? 'OK' : 'FALTA') . "\n";
        echo "  - obras: " . (isset($result['obras']) ? count($result['obras']) . ' obra(s)' : 'FALTA') . "\n";
        echo "  - gasto_por_cat: " . (isset($result['gasto_por_cat']) ? count($result['gasto_por_cat']) . ' categoria(s)' : 'FALTA') . "\n";
        echo "</pre>";
    } catch (\Throwable $e) {
        echo "<div class='error'><h3>✗ Erro na execução:</h3></div>";
        echo "<pre class='error'>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    }
    
    echo "<div class='info'><h2>4. CONCLUSÃO</h2></div>";
    echo "<p>Se chegou até aqui, o erro 500 está em outro lugar (Controller, Auth, View, etc)</p>";
    
} catch (\Throwable $e) {
    echo "<div class='error'><h2>✗ ERRO FATAL</h2></div>";
    echo "<pre class='error'>";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

echo "</body></html>";
