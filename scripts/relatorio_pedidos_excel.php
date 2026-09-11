<?php
/**
 * =====================================================================
 * RELATÓRIO DE PEDIDOS DE COMPRA -> EXCEL (.xlsx)  — versão CLI
 * =====================================================================
 *
 * Gera o mesmo arquivo do endpoint /admin/relatorio-pedidos/download, mas
 * pela linha de comando (útil para rodar no servidor via SSH/cron).
 *
 * USO (rodar no servidor, onde o banco MySQL está acessível):
 *   php scripts/relatorio_pedidos_excel.php
 *   php scripts/relatorio_pedidos_excel.php "/caminho/saida.xlsx"
 *
 * Saída padrão: arquivos/relatorio_pedidos_<data>.xlsx
 *
 * OBS: reaproveita o bootstrap do projeto (autoloader + config) e o
 * PurchaseOrderReportService. Requer as extensões pdo_mysql e zip.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
new App\Core\Application($config);

use App\Services\PurchaseOrderReportService;

$db = $config['database'] ?? [];
echo "Conectando ao banco '" . ($db['dbname'] ?? '?') . "' em " . ($db['host'] ?? '?') . "...\n";
echo "Calculando indicadores e montando o Excel...\n";

try {
    $binary = PurchaseOrderReportService::buildXlsx();
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao gerar o relatório: " . $e->getMessage() . "\n");
    exit(1);
}

$outPath = $argv[1] ?? (ROOT_PATH . '/arquivos/' . PurchaseOrderReportService::suggestedFilename());
$dir = dirname($outPath);
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

if (file_put_contents($outPath, $binary) === false) {
    fwrite(STDERR, "Não consegui gravar o arquivo em: {$outPath}\n");
    exit(1);
}

echo "\nOK! Relatório gerado em:\n  {$outPath}\n";
