<?php
/**
 * Script para baixar assets do site WordPress antigo
 * Execute: php tools/download_assets.php
 * 
 * Baixa imagens, vídeos e outros assets referenciados nos HTMLs limpos
 * e organiza nas pastas corretas do novo projeto.
 */

$cleanDir = __DIR__ . '/../htmls/clean';
$baseOutputDir = __DIR__ . '/../public/assets/images/wp';
$flatsomeDir = __DIR__ . '/../public/assets/flatsome';

// Cria diretórios necessários
$dirs = [$baseOutputDir, $flatsomeDir . '/assets/css/icons'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Lê todos os HTMLs limpos
$htmlFiles = glob($cleanDir . '/*.html');
$uploadUrls = [];
$themeUrls = [];

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    
    // URLs de uploads (imagens, vídeos, PDFs)
    preg_match_all('#https?://www\.brooksconstrutora\.com\.br/wp-content/uploads/([^\s"\'<>]+)#i', $content, $m1);
    if (!empty($m1[0])) {
        foreach ($m1[0] as $i => $url) {
            $uploadUrls[$url] = $m1[1][$i]; // caminho relativo
        }
    }
    
    // URLs do tema Flatsome (fontes de ícones, etc)
    preg_match_all('#https?://www\.brooksconstrutora\.com\.br/wp-content/themes/flatsome/([^\s"\'<>]+\.(woff2?|ttf|eot|svg|png|jpg))#i', $content, $m2);
    if (!empty($m2[0])) {
        foreach ($m2[0] as $i => $url) {
            $themeUrls[$url] = $m2[1][$i]; // caminho relativo
        }
    }
}

echo "============================================\n";
echo "  DOWNLOAD DE ASSETS - BROOKS CONSTRUTORA\n";
echo "============================================\n\n";
echo "Uploads encontrados: " . count($uploadUrls) . "\n";
echo "Assets do tema encontrados: " . count($themeUrls) . "\n\n";

$downloaded = 0;
$errors = 0;
$skipped = 0;

// Baixa uploads
echo "--- UPLOADS ---\n";
foreach ($uploadUrls as $url => $relativePath) {
    $localPath = $baseOutputDir . '/' . $relativePath;
    $localDir = dirname($localPath);
    
    if (!is_dir($localDir)) mkdir($localDir, 0755, true);
    
    if (file_exists($localPath)) {
        $skipped++;
        continue;
    }
    
    echo "  Baixando: " . basename($relativePath) . " ... ";
    
    $ctx = stream_context_create([
        'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0']
    ]);
    
    $data = @file_get_contents($url, false, $ctx);
    
    if ($data !== false && strlen($data) > 0) {
        file_put_contents($localPath, $data);
        echo "OK (" . round(strlen($data) / 1024) . " KB)\n";
        $downloaded++;
    } else {
        echo "ERRO\n";
        $errors++;
    }
    
    usleep(300000); // 0.3s entre requests
}

// Baixa assets do tema
echo "\n--- TEMA FLATSOME ---\n";
foreach ($themeUrls as $url => $relativePath) {
    $localPath = $flatsomeDir . '/' . $relativePath;
    $localDir = dirname($localPath);
    
    if (!is_dir($localDir)) mkdir($localDir, 0755, true);
    
    if (file_exists($localPath)) {
        $skipped++;
        continue;
    }
    
    echo "  Baixando: " . basename($relativePath) . " ... ";
    
    $ctx = stream_context_create([
        'http' => ['timeout' => 30, 'user_agent' => 'Mozilla/5.0']
    ]);
    
    $data = @file_get_contents($url, false, $ctx);
    
    if ($data !== false && strlen($data) > 0) {
        file_put_contents($localPath, $data);
        echo "OK (" . round(strlen($data) / 1024) . " KB)\n";
        $downloaded++;
    } else {
        echo "ERRO\n";
        $errors++;
    }
    
    usleep(300000);
}

echo "\n============================================\n";
echo "  RESULTADO\n";
echo "============================================\n";
echo "  Baixados: {$downloaded}\n";
echo "  Já existiam: {$skipped}\n";
echo "  Erros: {$errors}\n";
echo "============================================\n";
echo "\nPastas de destino:\n";
echo "  Imagens/uploads: {$baseOutputDir}/\n";
echo "  Tema Flatsome: {$flatsomeDir}/\n";
echo "\nNOTA: Se o site WordPress antigo estiver offline, você precisará\n";
echo "copiar os arquivos manualmente do backup do servidor.\n";
echo "\nEstruturas necessárias:\n";
echo "  /public/assets/images/wp/2023/01/ -> imagens de 2023\n";
echo "  /public/assets/images/wp/2024/11/ -> imagens de 2024\n";
echo "  /public/assets/flatsome/assets/css/icons/ -> fontes fl-icons\n";
echo "  /public/assets/videos/ -> vídeos (IMG_96791.mp4)\n";
echo "  /public/assets/docs/ -> documentos (portfolio.pdf)\n";
