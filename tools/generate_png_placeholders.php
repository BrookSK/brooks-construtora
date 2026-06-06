<?php
/**
 * Gera imagens PNG placeholder reais para que os navegadores as renderizem corretamente.
 * Requer extensão GD do PHP.
 * Execute: php tools/generate_png_placeholders.php
 */

if (!function_exists('imagecreatetruecolor')) {
    die("Extensão GD não disponível. Instale php-gd.\n");
}

$basePath = __DIR__ . '/../public/assets';

$files = [
    // Logos
    'images/wp/2024/11/logo-brooks-1400x396.webp' => [1400, 396, 'BROOKS CONSTRUTORA', '#3a3b4e', '#ffffff'],
    'images/wp/2024/11/logo-brooks-1-800x227.webp' => [800, 227, 'BROOKS CONSTRUTORA', '#3a3b4e', '#ffffff'],
    
    // Projetos
    'images/wp/2024/11/IMG_2477-1-jpg.webp' => [1920, 1080, 'PROJETO ROCHA ANDRADE', '#2c3e50', '#ffffff'],
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-scaled.webp' => [1920, 1080, 'NORAH CARNEIRO', '#34495e', '#ffffff'],
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp' => [800, 600, 'NORAH CARNEIRO', '#34495e', '#ffffff'],
    'images/wp/2024/11/bergamo-jpg.webp' => [1920, 1080, 'JOIA BERGAMO', '#1a472a', '#ffffff'],
    'images/wp/2024/11/palacio-bandeirantes-jpg.webp' => [1920, 1080, 'PALACIO BANDEIRANTES', '#2c3e50', '#ffffff'],
    'images/wp/2024/11/escritorio-itaim-jpeg.webp' => [1920, 1080, 'ESCRITORIO ITAIM', '#34495e', '#ffffff'],
    'images/wp/2024/11/mansao-alphaville-jpeg.webp' => [1920, 1080, 'MANSAO ALPHAVILLE', '#1a472a', '#ffffff'],
    'images/wp/2024/11/bergamo2-jpg.webp' => [1920, 1080, 'JOIA BERGAMO 2', '#2c3e50', '#ffffff'],
    
    // WhatsApp
    'images/wp/2023/01/whatsapp.png' => [35, 35, 'WA', '#25d366', '#ffffff'],
    
    // Diferenciais
    'images/wp/2023/01/png_20230107_215416_0000-1.png' => [600, 400, 'ATENDIMENTO', '#446084', '#ffffff'],
    'images/wp/2023/01/png_20230108_092659_0000-2.png' => [600, 400, 'QUALIDADE', '#446084', '#ffffff'],
    'images/wp/2023/01/png_20230107_221615_0000-1.png' => [600, 400, 'ETICA', '#446084', '#ffffff'],
    'images/wp/2023/01/png_20230108_091744_0000-1.png' => [600, 400, 'GESTAO', '#446084', '#ffffff'],
    'images/wp/2023/01/png_20230108_093143_0000-1.png' => [600, 400, 'VALOR', '#446084', '#ffffff'],
    'images/wp/2023/01/png_20230108_091554_0000-1.png' => [600, 400, 'CRONOGRAMA', '#446084', '#ffffff'],
    
    // Outros
    'images/wp/2023/01/icone-pdf-1.png' => [453, 497, 'PDF', '#e74c3c', '#ffffff'],
    'images/wp/2023/01/fundo-3.jpg' => [1920, 1080, '', '#1a1a2e', '#ffffff'],
    'images/wp/2023/01/fundo-1.jpg' => [1920, 1080, '', '#1a1a2e', '#ffffff'],
    'images/wp/2023/01/GUR1123-HDR-2-scaled.jpg' => [1920, 1080, 'JOIA BERGAMO', '#1a472a', '#ffffff'],
    
    // Favicons
    'images/wp/2023/01/cropped-favicon-1-32x32.png' => [32, 32, 'B', '#3a3b4e', '#ffffff'],
    'images/wp/2023/01/cropped-favicon-1-192x192.png' => [192, 192, 'B', '#3a3b4e', '#ffffff'],
    'images/wp/2023/01/cropped-favicon-1-180x180.png' => [180, 180, 'B', '#3a3b4e', '#ffffff'],
];

// Galeria
for ($i = 1; $i <= 8; $i++) {
    $files["images/wp/2023/01/projeto{$i}-1-400x400.jpeg"] = [400, 400, "PROJETO {$i}", '#3a3b4e', '#ffffff'];
    $files["images/wp/2023/01/projeto{$i}-1.jpeg"] = [800, 800, "PROJETO {$i}", '#3a3b4e', '#ffffff'];
}

// Avaliações
for ($i = 1; $i <= 20; $i++) {
    $num = str_pad($i, 3, '0', STR_PAD_LEFT);
    $files["images/wp/2023/01/avaliacao{$num}-1-227x400.jpg"] = [227, 400, "AVALIACAO {$i}", '#f0f0f0', '#333333'];
    $files["images/wp/2023/01/avaliacao{$num}-1.jpg"] = [454, 800, "AVALIACAO {$i}", '#f0f0f0', '#333333'];
}

function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function createPlaceholder(string $path, int $w, int $h, string $text, string $bgHex, string $fgHex): void {
    // Limita dimensões para economia de memória/disco
    $maxW = min($w, 800);
    $maxH = min($h, 600);
    if ($w > 800) {
        $ratio = $maxW / $w;
        $maxH = (int)($h * $ratio);
    }
    
    $img = imagecreatetruecolor($maxW, $maxH);
    
    [$br, $bg, $bb] = hexToRgb($bgHex);
    [$fr, $fg, $fb] = hexToRgb($fgHex);
    
    $bgColor = imagecolorallocate($img, $br, $bg, $bb);
    $fgColor = imagecolorallocate($img, $fr, $fg, $fb);
    
    imagefill($img, 0, 0, $bgColor);
    
    // Desenha texto centralizado
    if (!empty($text)) {
        $fontSize = 4; // GD built-in font (1-5)
        if ($maxW > 400) $fontSize = 5;
        
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        $x = ($maxW - $textWidth) / 2;
        $y = ($maxH - $textHeight) / 2;
        
        imagestring($img, $fontSize, (int)$x, (int)$y, $text, $fgColor);
    }
    
    // Determina formato de saída pela extensão
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    switch ($ext) {
        case 'webp':
            if (function_exists('imagewebp')) {
                imagewebp($img, $path, 80);
            } else {
                imagepng($img, $path);
            }
            break;
        case 'jpg':
        case 'jpeg':
            imagejpeg($img, $path, 85);
            break;
        case 'png':
        default:
            imagepng($img, $path);
            break;
    }
    
    imagedestroy($img);
}

echo "Gerando placeholders PNG/WEBP/JPG reais...\n\n";
$created = 0;

foreach ($files as $relPath => $config) {
    [$w, $h, $text, $bgColor, $fgColor] = $config;
    $fullPath = $basePath . '/' . $relPath;
    
    createPlaceholder($fullPath, $w, $h, $text, $bgColor, $fgColor);
    $created++;
}

echo "Criados: {$created} imagens reais\n";
echo "\nPronto! Os navegadores agora vão renderizar as imagens corretamente.\n";
echo "Substitua pelos arquivos reais do backup do WordPress quando disponíveis.\n";
