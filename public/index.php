<?php
/**
 * Brooks Construtora - Front Controller
 * Ponto de entrada da aplicação
 */

// Define o diretório raiz da aplicação
define('ROOT_PATH', dirname(__DIR__));

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
