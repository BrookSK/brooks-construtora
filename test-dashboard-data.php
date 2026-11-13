<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define ROOT_PATH
define('ROOT_PATH', __DIR__);

try {
    // Load config
    $config = require __DIR__ . '/app/Config/app.php';
    require_once __DIR__ . '/app/Core/Autoloader.php';
    
    // Initialize autoloader
    $autoloader = new App\Core\Autoloader();
    $autoloader->register();
    
    // Initialize Application
    $app = new App\Core\Application($config);
    
    // Simulate authenticated session
    session_start();
    $_SESSION['user_id'] = 1; // Admin user
    
    echo "Testing dashboardData endpoint...\n\n";
    
    // Simulate request
    $_GET['periodo'] = '2024-11';
    $_GET['company'] = 'completo';
    
    // Create controller
    $controller = new App\Controllers\Admin\PainelObrasController();
    
    // Call dashboardData
    ob_start();
    $controller->dashboardData();
    $output = ob_get_clean();
    
    echo "OUTPUT:\n";
    echo $output;
    
} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
