<?php
/**
 * Script para baixar assets do site WordPress antigo
 * Execute: php tools/download_assets.php
 * 
 * Este script extrai URLs de imagens dos HTMLs salvos e tenta baixá-las
 * para a pasta public/assets/images/wp/
 */

$htmlDir = __DIR__ . '/../htmls';
$outputDir = __DIR__ . '/../public/assets/images/wp';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$htmlFiles = glob($htmlDir . '/*.html');
$urls = [];

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    
    // Extrai URLs de imagens do WordPress
    preg_match_all('/https:\/\/www\.brooksconstrutora\.com\.br\/wp-content\/uploads\/[^"\'<>\s]+\.(jpg|jpeg|png|gif|webp|svg)/i', $content, $matches);
    
    if (!empty($matches[0])) {
        $urls = array_merge($urls, $matches[0]);
    }
    
    // Extrai URLs de logos/temas
    preg_match_all('/https:\/\/www\.brooksconstrutora\.com\.br\/wp-content\/themes\/[^"\'<>\s]+\.(jpg|jpeg|png|gif|webp|svg)/i', $content, $matches2);
    
    if (!empty($matches2[0])) {
        $urls = array_merge($urls, $matches2[0]);
    }
}

$urls = array_unique($urls);
echo "Encontradas " . count($urls) . " URLs de imagens.\n\n";

$downloaded = 0;
$errors = 0;

foreach ($urls as $url) {
    // Limpa a URL (remove HTML entities)
    $url = html_entity_decode($url);
    $url = preg_replace('/&amp;/', '&', $url);
    
    // Cria o caminho local mantendo a estrutura
    $path = parse_url($url, PHP_URL_PATH);
    $localPath = $outputDir . '/' . basename($path);
    
    if (file_exists($localPath)) {
        echo "[SKIP] Já existe: " . basename($path) . "\n";
        continue;
    }
    
    echo "[DOWN] Baixando: " . basename($path) . " ... ";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $imageContent = @file_get_contents($url, false, $context);
    
    if ($imageContent !== false) {
        file_put_contents($localPath, $imageContent);
        echo "OK (" . round(strlen($imageContent) / 1024) . " KB)\n";
        $downloaded++;
    } else {
        echo "ERRO\n";
        $errors++;
    }
    
    // Evita muitas requisições simultâneas
    usleep(500000); // 0.5s
}

echo "\n============================\n";
echo "Download concluído!\n";
echo "Baixados: {$downloaded}\n";
echo "Erros: {$errors}\n";
echo "Total URLs: " . count($urls) . "\n";
echo "\nImagens salvas em: {$outputDir}\n";
