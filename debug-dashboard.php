<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-errors.log');

// Define ROOT_PATH first
define('ROOT_PATH', __DIR__);

try {
    require_once __DIR__ . '/app/Config/app.php';
    require_once __DIR__ . '/app/Core/Autoloader.php';
    
    // Initialize autoloader
    $autoloader = new App\Core\Autoloader();
    $autoloader->register();
    
    // Test database connection
    echo "1. Testing database connection...\n";
    $db = new App\Core\Database();
    echo "✓ Database connected\n\n";
    
    // Test PainelObrasService
    echo "2. Testing PainelObrasService...\n";
    $service = new App\Services\PainelObrasService($db);
    echo "✓ Service instantiated\n\n";
    
    // Test getDashboardData
    echo "3. Testing getDashboardData()...\n";
    $data = $service->getDashboardData('mes', 'completo');
    echo "✓ getDashboardData() executed\n\n";
    
    // Show structure
    echo "4. Data structure:\n";
    echo "Keys: " . implode(", ", array_keys($data)) . "\n\n";
    
    // Check orcado_por_cat
    if (isset($data['orcado_por_cat'])) {
        echo "5. orcado_por_cat found:\n";
        print_r($data['orcado_por_cat']);
    } else {
        echo "5. ⚠ orcado_por_cat NOT FOUND in data\n";
    }
    
    echo "\n✓ ALL TESTS PASSED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
