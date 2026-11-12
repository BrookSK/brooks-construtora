<?php
/**
 * Teste direto do PainelObrasController::index()
 * Captura e exibe erros detalhados
 */

// Define ROOT_PATH como o index.php faz
define('ROOT_PATH', __DIR__);

// Habilita todos os erros
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste Painel Obras</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-radius:5px;overflow:auto;white-space:pre-wrap;}";
echo ".error{color:#f48771;} .success{color:#4ec9b0;} .info{color:#9cdcfe;}</style>";
echo "</head><body>";

echo "<h1>Teste Painel Obras - Diagnóstico 500</h1>";

try {
    echo "<div class='info'><h2>1. Inicializando aplicação...</h2></div>";
    
    // Carrega autoloader
    require_once ROOT_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    echo "<div class='success'>✓ Autoloader registrado</div>";
    
    // Carrega config
    $config = require_once ROOT_PATH . '/app/Config/app.php';
    echo "<div class='success'>✓ Configuração carregada</div>";
    
    // Inicializa a aplicação (necessário para Database funcionar)
    $app = new \App\Core\Application($config);
    echo "<div class='success'>✓ Aplicação inicializada</div>";
    
    // Simula session (necessário para o controller)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<div class='info'><h2>2. Verificando classes...</h2></div>";
    
    // Verifica PainelObrasService
    if (!class_exists('\App\Services\PainelObrasService')) {
        echo "<div class='error'>✗ Classe PainelObrasService não encontrada</div>";
        exit;
    }
    echo "<div class='success'>✓ Classe PainelObrasService encontrada</div>";
    
    // Verifica PainelObrasController
    if (!class_exists('\App\Controllers\Admin\PainelObrasController')) {
        echo "<div class='error'>✗ Classe PainelObrasController não encontrada</div>";
        exit;
    }
    echo "<div class='success'>✓ Classe PainelObrasController encontrada</div>";
    
    echo "<div class='info'><h2>3. Testando getDashboardData()...</h2></div>";
    
    try {
        $competencia = '2024-11';
        $company = 'brooks';
        
        echo "<pre>Chamando: PainelObrasService::getDashboardData('$competencia', '$company')</pre>";
        
        $result = \App\Services\PainelObrasService::getDashboardData($competencia, $company);
        
        echo "<div class='success'>✓ getDashboardData() executou SEM ERROS!</div>";
        echo "<pre>";
        echo "Estrutura retornada:\n";
        foreach (array_keys($result) as $key) {
            $value = $result[$key];
            if (is_array($value)) {
                echo "  $key: array(" . count($value) . " itens)\n";
            } else {
                echo "  $key: " . json_encode($value) . "\n";
            }
        }
        echo "</pre>";
        
        if (isset($result['kpis'])) {
            echo "<div class='info'><h3>KPIs retornados:</h3></div>";
            echo "<pre>" . json_encode($result['kpis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
        
    } catch (\Throwable $e) {
        echo "<div class='error'><h3>✗ ERRO no getDashboardData():</h3></div>";
        echo "<pre class='error'>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n\n";
        echo "Stack trace completo:\n" . $e->getTraceAsString();
        echo "</pre>";
    }
    
    echo "<div class='info'><h2>4. CONCLUSÃO</h2></div>";
    
    if (isset($result)) {
        echo "<div class='success'><p><strong>✓ CÓDIGO FUNCIONANDO!</strong></p>";
        echo "<p>Se /admin/painel-obras retorna 500, verifique:</p>";
        echo "<ul>";
        echo "<li>Autenticação (Auth::check())</li>";
        echo "<li>Permissões (Auth::hasPermission())</li>";
        echo "<li>Renderização da view</li>";
        echo "<li>Teste com: <a href='/admin/painel-obras?debug=1' style='color:#4ec9b0;'>/admin/painel-obras?debug=1</a></li>";
        echo "</ul></div>";
    } else {
        echo "<div class='error'><p>O método getDashboardData() falhou. Veja o erro acima.</p></div>";
    }
    
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
