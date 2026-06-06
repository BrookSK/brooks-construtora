<?php
/**
 * Gera imagens placeholder para desenvolvimento
 * Execute: php tools/generate_placeholders.php
 * 
 * Cria imagens SVG placeholder nos locais esperados para que o site
 * funcione visualmente durante o desenvolvimento. Substitua pelos
 * arquivos reais do backup do WordPress.
 */

$basePath = __DIR__ . '/../public/assets';

// Lista de todos os arquivos necessários com suas dimensões
$files = [
    // Logos
    'images/wp/2024/11/logo-brooks-1400x396.webp' => ['svg', 1400, 396, 'BROOKS CONSTRUTORA', '#3a3b4e'],
    'images/wp/2024/11/logo-brooks-1-800x227.webp' => ['svg', 800, 227, 'BROOKS CONSTRUTORA', '#3a3b4e'],
    
    // Projetos - banners slider
    'images/wp/2024/11/IMG_2477-1-jpg.webp' => ['svg', 1920, 1080, 'PROJETO ROCHA ANDRADE', '#2c3e50'],
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-scaled.webp' => ['svg', 1920, 1080, 'PROJETO NORAH CARNEIRO', '#34495e'],
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp' => ['svg', 1920, 1080, 'PROJETO NORAH CARNEIRO', '#34495e'],
    'images/wp/2024/11/bergamo-jpg.webp' => ['svg', 1920, 1080, 'PROJETO JÓIA BERGAMO', '#1a472a'],
    'images/wp/2024/11/palacio-bandeirantes-jpg.webp' => ['svg', 1920, 1080, 'REFORMA CORPORATIVA PALÁCIO', '#2c3e50'],
    'images/wp/2024/11/escritorio-itaim-jpeg.webp' => ['svg', 1920, 1080, 'REFORMA ESCRITÓRIO ITAIM', '#34495e'],
    'images/wp/2024/11/mansao-alphaville-jpeg.webp' => ['svg', 1920, 1080, 'MANSÃO ALPHAVILLE', '#1a472a'],
    'images/wp/2024/11/bergamo2-jpg.webp' => ['svg', 1920, 1080, 'PROJETO JÓIA BERGAMO 2', '#2c3e50'],
    
    // WhatsApp icon
    'images/wp/2023/01/whatsapp.png' => ['svg', 35, 35, 'WA', '#25d366'],
    
    // Diferenciais
    'images/wp/2023/01/png_20230107_215416_0000-1.png' => ['svg', 600, 400, 'ATENDIMENTO PERSONALIZADO', '#446084'],
    'images/wp/2023/01/png_20230108_092659_0000-2.png' => ['svg', 600, 400, 'PRAZO E QUALIDADE', '#446084'],
    'images/wp/2023/01/png_20230107_221615_0000-1.png' => ['svg', 600, 400, 'SOLIDEZ E ÉTICA', '#446084'],
    'images/wp/2023/01/png_20230108_091744_0000-1.png' => ['svg', 600, 400, 'GESTÃO PROFISSIONAL', '#446084'],
    'images/wp/2023/01/png_20230108_093143_0000-1.png' => ['svg', 600, 400, 'VALOR JUSTO', '#446084'],
    'images/wp/2023/01/png_20230108_091554_0000-1.png' => ['svg', 600, 400, 'CRONOGRAMAS E RELATÓRIOS', '#446084'],
    
    // PDF icon
    'images/wp/2023/01/icone-pdf-1.png' => ['svg', 453, 497, 'PDF', '#e74c3c'],
    
    // Fundo parallax
    'images/wp/2023/01/fundo-3.jpg' => ['svg', 1920, 1080, 'BACKGROUND', '#1a1a2e'],
    'images/wp/2023/01/fundo-1.jpg' => ['svg', 1920, 1080, 'BACKGROUND MOBILE', '#1a1a2e'],
    
    // Galeria de projetos
    'images/wp/2023/01/projeto1-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 1', '#3a3b4e'],
    'images/wp/2023/01/projeto2-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 2', '#446084'],
    'images/wp/2023/01/projeto3-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 3', '#3a3b4e'],
    'images/wp/2023/01/projeto4-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 4', '#446084'],
    'images/wp/2023/01/projeto5-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 5', '#3a3b4e'],
    'images/wp/2023/01/projeto6-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 6', '#446084'],
    'images/wp/2023/01/projeto7-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 7', '#3a3b4e'],
    'images/wp/2023/01/projeto8-1-400x400.jpeg' => ['svg', 400, 400, 'PROJETO 8', '#446084'],
    
    // Galeria full
    'images/wp/2023/01/projeto1-1.jpeg' => ['svg', 800, 800, 'PROJETO 1', '#3a3b4e'],
    'images/wp/2023/01/projeto2-1.jpeg' => ['svg', 800, 800, 'PROJETO 2', '#446084'],
    'images/wp/2023/01/projeto3-1.jpeg' => ['svg', 800, 800, 'PROJETO 3', '#3a3b4e'],
    'images/wp/2023/01/projeto4-1.jpeg' => ['svg', 800, 800, 'PROJETO 4', '#446084'],
    'images/wp/2023/01/projeto5-1.jpeg' => ['svg', 800, 800, 'PROJETO 5', '#3a3b4e'],
    'images/wp/2023/01/projeto6-1.jpeg' => ['svg', 800, 800, 'PROJETO 6', '#446084'],
    'images/wp/2023/01/projeto7-1.jpeg' => ['svg', 800, 800, 'PROJETO 7', '#3a3b4e'],
    'images/wp/2023/01/projeto8-1.jpeg' => ['svg', 800, 800, 'PROJETO 8', '#446084'],
    
    // Favicons
    'images/wp/2023/01/cropped-favicon-1-32x32.png' => ['svg', 32, 32, 'B', '#3a3b4e'],
    'images/wp/2023/01/cropped-favicon-1-192x192.png' => ['svg', 192, 192, 'B', '#3a3b4e'],
    'images/wp/2023/01/cropped-favicon-1-180x180.png' => ['svg', 180, 180, 'B', '#3a3b4e'],

    // Joia Bergamo projeto
    'images/wp/2023/01/GUR1123-HDR-2-scaled.jpg' => ['svg', 1920, 1080, 'JÓIA BERGAMO', '#1a472a'],
];

// Avaliações
for ($i = 1; $i <= 20; $i++) {
    $num = str_pad($i, 3, '0', STR_PAD_LEFT);
    $files["images/wp/2023/01/avaliacao{$num}-1-227x400.jpg"] = ['svg', 227, 400, "AVALIAÇÃO {$i}", '#f5f5f5'];
    $files["images/wp/2023/01/avaliacao{$num}-1.jpg"] = ['svg', 454, 800, "AVALIAÇÃO {$i}", '#f5f5f5'];
}

function generateSvg(int $width, int $height, string $text, string $bgColor): string {
    $textColor = ($bgColor === '#f5f5f5') ? '#333' : '#fff';
    $fontSize = min($width, $height) * 0.08;
    $fontSize = max(10, min(40, $fontSize));
    
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="{$width}" height="{$height}" fill="{$bgColor}"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="{$fontSize}" font-weight="bold" fill="{$textColor}">{$text}</text>
  <text x="50%" y="65%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="{$fontSize}%" fill="{$textColor}" opacity="0.5">{$width}x{$height}</text>
</svg>
SVG;
}

echo "Gerando placeholders...\n\n";
$created = 0;
$skipped = 0;

foreach ($files as $path => $config) {
    [$type, $width, $height, $text, $bgColor] = $config;
    $fullPath = $basePath . '/' . $path;
    $dir = dirname($fullPath);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_exists($fullPath) && filesize($fullPath) > 100) {
        $skipped++;
        continue;
    }
    
    $svg = generateSvg($width, $height, $text, $bgColor);
    
    // Para arquivos .webp, .jpg, .jpeg, .png - salva como SVG mesmo (navegadores renderizam)
    // Em produção substituir pelos arquivos reais
    file_put_contents($fullPath, $svg);
    $created++;
}

// Criar fl-icons placeholder
$iconsDir = $basePath . '/flatsome/assets/css/icons';
if (!is_dir($iconsDir)) mkdir($iconsDir, 0755, true);

// Criar fonte vazia para fl-icons (evita erro 404)
$emptyFont = '';
$fontFiles = ['fl-icons.woff2', 'fl-icons.woff', 'fl-icons.ttf'];
foreach ($fontFiles as $fontFile) {
    $fontPath = $iconsDir . '/' . $fontFile;
    if (!file_exists($fontPath)) {
        file_put_contents($fontPath, $emptyFont);
        $created++;
    }
}

// Criar video placeholder
$videoDir = $basePath . '/videos';
if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);
$videoPath = $videoDir . '/IMG_96791.mp4';
if (!file_exists($videoPath) || filesize($videoPath) === 0) {
    // Cria um arquivo vazio - o player simplesmente não mostra nada
    file_put_contents($videoPath, '');
    $created++;
}

// Criar docs placeholder
$docsDir = $basePath . '/docs';
if (!is_dir($docsDir)) mkdir($docsDir, 0755, true);
$pdfPath = $docsDir . '/portfolio.pdf';
if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
    file_put_contents($pdfPath, '');
    $created++;
}

echo "Criados: {$created}\n";
echo "Já existiam: {$skipped}\n";
echo "\nPronto! Agora o site vai renderizar com placeholders.\n";
echo "Substitua os arquivos SVG pelos reais quando tiver o backup do WordPress.\n";
