<?php
/**
 * Executa migração 055 - Tabelas de lançamentos e categorias
 */

// Define ROOT_PATH
define('ROOT_PATH', __DIR__);

// Habilita erros
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migração 055</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-radius:5px;overflow:auto;white-space:pre-wrap;}";
echo ".error{color:#f48771;} .success{color:#4ec9b0;} .info{color:#9cdcfe;}</style>";
echo "</head><body>";

echo "<h1>Migração 055 - Lançamentos e Categorias</h1>";

try {
    // Carrega autoloader e config
    require_once ROOT_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    
    $config = require_once ROOT_PATH . '/app/Config/app.php';
    $app = new \App\Core\Application($config);
    
    echo "<div class='success'>✓ Aplicação inicializada</div>";
    
    // Lê o arquivo SQL
    $sqlFile = ROOT_PATH . '/database/migrations/055_painel_obras_parcelas.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo de migração não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "<div class='info'>✓ Arquivo SQL carregado (" . strlen($sql) . " bytes)</div>";
    
    // Separa em statements individuais
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql)),
        fn($s) => !empty($s) && !preg_match('/^--/', $s)
    );
    
    echo "<div class='info'>Total de statements: " . count($statements) . "</div>";
    echo "<hr>";
    
    $success = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($statements as $i => $statement) {
        $num = $i + 1;
        
        // Ignora comentários
        if (preg_match('/^--/', $statement)) {
            continue;
        }
        
        try {
            // Mostra preview do statement
            $preview = substr($statement, 0, 100);
            echo "<div class='info'><strong>[$num]</strong> " . htmlspecialchars($preview) . "...</div>";
            
            \App\Core\Database::query($statement);
            
            echo "<div class='success'>✓ Executado com sucesso</div>";
            $success++;
            
        } catch (\PDOException $e) {
            // Se o erro for "já existe", apenas avisa
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<div style='color:#f59e0b'>⚠ Já existe (ignorado)</div>";
                $skipped++;
            } else {
                echo "<div class='error'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors++;
            }
        }
        
        echo "<br>";
    }
    
    echo "<hr>";
    echo "<h2>Resumo</h2>";
    echo "<div class='success'>✓ Executados com sucesso: $success</div>";
    echo "<div style='color:#f59e0b'>⚠ Ignorados (já existem): $skipped</div>";
    echo "<div class='error'>✗ Erros: $errors</div>";
    
    if ($errors === 0) {
        echo "<br><div class='success'><h3>✓ MIGRAÇÃO CONCLUÍDA!</h3></div>";
        echo "<p>Agora você pode usar:</p>";
        echo "<ul>";
        echo "<li><a href='/admin/painel-obras/lancamentos' style='color:#4ec9b0'>Lançamentos Financeiros</a></li>";
        echo "<li><a href='/admin/painel-obras/categorias' style='color:#4ec9b0'>Gerenciar Categorias</a></li>";
        echo "<li><a href='/admin/painel-obras/orcamento' style='color:#4ec9b0'>Orçamento por Obra</a></li>";
        echo "</ul>";
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
