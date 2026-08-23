<?php
/**
 * Brooks Construtora - Front Controller
 * Ponto de entrada da aplicação
 */

// Define o diretório raiz da aplicação
define('ROOT_PATH', dirname(__DIR__));

// Registra erros fatais em arquivo de log para diagnóstico (sem exibir ao usuário)
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/php-error.log');

// Modo debug: ?debug=1 exibe erros na tela (inclui fatais/parse via shutdown).
// Use apenas para diagnóstico pontual.
$__DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($__DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    // Captura erros fatais/parse que não passam por try/catch
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Limpa qualquer buffer para garantir que a mensagem apareça
            while (ob_get_level() > 0) { ob_end_clean(); }
            http_response_code(500);
            echo '<pre style="white-space:pre-wrap;font-family:monospace;color:#b00;padding:16px;">';
            echo "ERRO FATAL:\n" . htmlspecialchars($err['message']) . "\n\n";
            echo htmlspecialchars($err['file'] . ':' . $err['line']);
            echo '</pre>';
        }
    });
}

// Carrega o autoloader
require_once ROOT_PATH . '/app/Core/Autoloader.php';

// Inicializa o autoloader
App\Core\Autoloader::register();

// Carrega as configurações
$config = require_once ROOT_PATH . '/app/Config/app.php';

// Inicializa a aplicação
$app = new App\Core\Application($config);

// Executa a aplicação
$app->run();
