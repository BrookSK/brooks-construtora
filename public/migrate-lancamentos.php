<?php
/**
 * Script de migração para tabelas de lançamentos
 * Acesse: https://www.brooksconstrutora.com.br/migrate-lancamentos.php
 * 
 * IMPORTANTE: Delete este arquivo após executar!
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migração Lançamentos</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#00ff00}';
echo '.ok{color:#00ff00}.error{color:#ff0000}.info{color:#00aaff}</style></head><body>';

echo '<h1>🚀 Migração de Tabelas de Lançamentos</h1>';

try {
    // Lê o arquivo de migration
    $sqlFile = __DIR__ . '/../database/migrations/055_painel_obras_parcelas.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Arquivo de migration não encontrado: ' . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo '<p class="info">📄 Arquivo carregado: ' . basename($sqlFile) . '</p>';
    
    // Separa as queries
    $queries = explode(';', $sql);
    $queries = array_filter(array_map('trim', $queries));
    
    echo '<p class="info">📊 Total de queries: ' . count($queries) . '</p><hr>';
    
    $success = 0;
    $errors = 0;
    $skipped = 0;
    
    foreach ($queries as $i => $query) {
        if (empty($query) || strlen($query) < 10) {
            $skipped++;
            continue;
        }
        
        // Extrai o nome da operação
        preg_match('/^(CREATE TABLE|ALTER TABLE|INSERT INTO)\s+(?:IF NOT EXISTS\s+)?([^\s\(]+)/i', $query, $matches);
        $operation = $matches[1] ?? 'QUERY';
        $table = $matches[2] ?? 'desconhecido';
        
        echo '<div style="margin:10px 0;padding:10px;background:#2a2a2a;border-left:3px solid #555">';
        echo '<strong>' . ($i + 1) . '. ' . $operation . ' ' . htmlspecialchars($table) . '</strong><br>';
        
        try {
            Database::query($query);
            echo '<span class="ok">✓ Executado com sucesso</span>';
            $success++;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            
            // Ignora erros de "já existe" ou "coluna duplicada"
            if (
                stripos($msg, 'already exists') !== false ||
                stripos($msg, 'Duplicate column') !== false ||
                stripos($msg, 'Duplicate entry') !== false
            ) {
                echo '<span class="info">⚠ Já existe (ignorado)</span>';
                $skipped++;
            } else {
                echo '<span class="error">✗ ERRO: ' . htmlspecialchars($msg) . '</span>';
                $errors++;
            }
        }
        
        echo '</div>';
    }
    
    echo '<hr><h2>📋 Resumo:</h2>';
    echo '<p class="ok">✓ Sucesso: ' . $success . '</p>';
    echo '<p class="info">⚠ Ignorado: ' . $skipped . '</p>';
    echo '<p class="error">✗ Erros: ' . $errors . '</p>';
    
    if ($errors === 0) {
        echo '<h3 class="ok">🎉 Migração concluída com sucesso!</h3>';
        echo '<p>Você pode agora acessar <a href="/admin/painel-obras/lancamentos" style="color:#00aaff">/admin/painel-obras/lancamentos</a></p>';
        echo '<p style="color:#ffaa00">⚠️ <strong>IMPORTANTE:</strong> Delete este arquivo (migrate-lancamentos.php) por segurança!</p>';
    } else {
        echo '<h3 class="error">⚠️ Houve erros na migração</h3>';
        echo '<p>Verifique os erros acima e tente corrigir.</p>';
    }
    
} catch (\Throwable $e) {
    echo '<p class="error">❌ ERRO FATAL: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre class="error">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</body></html>';
