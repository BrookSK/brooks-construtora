<?php
/**
 * Script de diagnóstico para testar a reimportação de materiais
 * Acesse: /test_reimport.php
 * REMOVA DEPOIS DE TESTAR!
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Database;

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>Diagnóstico de Reimportação de Materiais</h1>";

// 1. Verificar especificações únicas na tabela materials
echo "<h2>1. Especificações únicas na tabela materials:</h2>";
$specs = Database::fetchAll(
    "SELECT 
        COALESCE(NULLIF(TRIM(specification), ''), 'Sem Especificação') AS spec_name,
        COUNT(*) as total
     FROM materials
     WHERE active = 1
     GROUP BY spec_name
     ORDER BY total DESC
     LIMIT 30"
);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Especificação</th><th>Qtd Materiais</th></tr>";
foreach ($specs as $s) {
    echo "<tr><td>" . htmlspecialchars($s['spec_name']) . "</td><td>{$s['total']}</td></tr>";
}
echo "</table>";

// 2. Verificar alguns materiais específicos
echo "<h2>2. Amostra de materiais (primeiros 20):</h2>";
$materials = Database::fetchAll(
    "SELECT id, name, specification, classification, category_id, unit_id
     FROM materials
     WHERE active = 1
     ORDER BY name ASC
     LIMIT 20"
);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th><th>Especificação</th><th>Classificação</th><th>Cat ID</th><th>Unit ID</th></tr>";
foreach ($materials as $m) {
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td>" . htmlspecialchars($m['name']) . "</td>";
    echo "<td>" . htmlspecialchars($m['specification'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($m['classification'] ?? 'NULL') . "</td>";
    echo "<td>{$m['category_id']}</td>";
    echo "<td>{$m['unit_id']}</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Verificar listas de materiais existentes
echo "<h2>3. Listas de Materiais (templates) existentes:</h2>";
$templates = Database::fetchAll("SELECT id, name, description, active, created_at FROM material_templates ORDER BY name ASC");
if (empty($templates)) {
    echo "<p style='color:red;'>Nenhuma lista encontrada!</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Ativo</th><th>Criada em</th></tr>";
    foreach ($templates as $t) {
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>" . htmlspecialchars($t['name']) . "</td>";
        echo "<td>" . htmlspecialchars($t['description'] ?? '') . "</td>";
        echo "<td>{$t['active']}</td>";
        echo "<td>{$t['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Verificar categorias
echo "<h2>4. Categorias de Materiais:</h2>";
$cats = Database::fetchAll("SELECT id, name FROM material_categories ORDER BY name ASC LIMIT 30");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th></tr>";
foreach ($cats as $c) {
    echo "<tr><td>{$c['id']}</td><td>" . htmlspecialchars($c['name']) . "</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>Dica:</strong> Após a reimportação, a coluna 'specification' dos materiais deve estar preenchida com os valores do CSV (coluna 'especificacao').</p>";
echo "<p><strong>Se as especificações estão corretas acima, clique em 'Recriar Listas' novamente.</strong></p>";
