<?php
/**
 * Debug de Erros - Exibe últimas linhas do error_log
 * Acesse: https://www.brooksconstrutora.com.br/debug-errors.php
 * 
 * IMPORTANTE: REMOVER ESTE ARQUIVO APÓS O DEBUG!
 */

// Segurança básica - só funciona em dev ou com senha
$senha = $_GET['senha'] ?? '';
if ($senha !== 'debug2024') {
    die('Acesso negado. Use ?senha=debug2024');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DE ERROS - PAINEL DE OBRAS ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// Possíveis localizações do error_log
$possiveisLogs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    dirname(__DIR__) . '/error.log',
    dirname(__DIR__) . '/logs/error.log',
    '/usr/local/apache/logs/error_log',
    ini_get('error_log'),
];

echo "=== 1. BUSCANDO ARQUIVO DE LOG ===\n";
$logEncontrado = null;

foreach ($possiveisLogs as $caminho) {
    if (empty($caminho)) continue;
    
    echo "Testando: $caminho ... ";
    
    if (file_exists($caminho) && is_readable($caminho)) {
        echo "✓ ENCONTRADO!\n";
        $logEncontrado = $caminho;
        break;
    } else {
        echo "✗\n";
    }
}

if (!$logEncontrado) {
    echo "\n❌ NENHUM LOG ENCONTRADO!\n\n";
    echo "=== 2. FORÇANDO ERRO PARA CRIAR LOG ===\n";
    
    // Cria arquivo de log customizado
    $customLog = dirname(__DIR__) . '/debug_error.log';
    ini_set('error_log', $customLog);
    ini_set('log_errors', 1);
    
    echo "Configurado para: $customLog\n\n";
    
    // Tenta carregar o Service e forçar o erro
    define('ROOT_PATH', dirname(__DIR__));
    require_once ROOT_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    
    echo "=== 3. TESTANDO PAINELOBRASSERVICE ===\n";
    
    try {
        echo "Carregando service...\n";
        $result = \App\Services\PainelObrasService::getDashboardData(date('Y-m'), 'brooks');
        echo "✓ Service carregou sem erros!\n";
        echo "Estrutura retornada:\n";
        echo "  - kpis: " . (isset($result['kpis']) ? 'OK' : 'FALTA') . "\n";
        echo "  - obras: " . (isset($result['obras']) ? 'OK' : 'FALTA') . "\n";
        echo "  - gasto_por_cat: " . (isset($result['gasto_por_cat']) ? 'OK' : 'FALTA') . "\n";
        echo "  - orcado_por_cat: " . (isset($result['orcado_por_cat']) ? 'OK' : 'FALTA') . "\n\n";
        
        echo "=== 4. LENDO LOG CUSTOMIZADO ===\n";
        if (file_exists($customLog)) {
            echo file_get_contents($customLog);
        } else {
            echo "Nenhum erro registrado!\n";
        }
        
    } catch (\Throwable $e) {
        echo "\n❌ ERRO CAPTURADO:\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n\n";
        echo "Stack Trace:\n";
        echo $e->getTraceAsString() . "\n\n";
        
        // Tenta ler o log customizado
        if (file_exists($customLog)) {
            echo "=== LOG GERADO ===\n";
            echo file_get_contents($customLog);
        }
    }
    
    exit;
}

echo "\n=== 2. LENDO ÚLTIMAS 200 LINHAS DO LOG ===\n";
echo "Arquivo: $logEncontrado\n";
echo "Tamanho: " . number_format(filesize($logEncontrado) / 1024, 2) . " KB\n\n";

// Lê últimas 200 linhas
$linhas = file($logEncontrado);
$ultimasLinhas = array_slice($linhas, -200);

echo "=== 3. FILTRANDO ERROS RELACIONADOS A PAINEL ===\n\n";

$errosRelevantes = [];
foreach ($ultimasLinhas as $linha) {
    $linha = trim($linha);
    if (empty($linha)) continue;
    
    // Filtra apenas erros relacionados ao Painel de Obras
    if (
        stripos($linha, 'painel') !== false ||
        stripos($linha, 'Fatal error') !== false ||
        stripos($linha, 'Parse error') !== false ||
        stripos($linha, 'Uncaught') !== false ||
        stripos($linha, 'PainelObras') !== false
    ) {
        $errosRelevantes[] = $linha;
    }
}

if (empty($errosRelevantes)) {
    echo "✓ Nenhum erro recente encontrado!\n\n";
    echo "=== 4. ÚLTIMAS 50 LINHAS DO LOG (COMPLETO) ===\n\n";
    echo implode("\n", array_slice($ultimasLinhas, -50));
} else {
    echo "❌ ERROS ENCONTRADOS:\n\n";
    foreach ($errosRelevantes as $erro) {
        echo $erro . "\n";
    }
}

echo "\n\n=== FIM DO DEBUG ===\n";
echo "IMPORTANTE: Delete este arquivo após o debug!\n";
