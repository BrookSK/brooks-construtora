<?php
/**
 * Gera imagens placeholder binárias válidas (sem GD)
 * Cria PNGs de 1x1 pixel com cor sólida para cada arquivo necessário.
 * Execute: php tools/generate_binary_placeholders.php
 */

$basePath = __DIR__ . '/../public/assets';

// PNG mínimo de 1x1 pixel (67 bytes) - cor cinza escuro #3a3b4e
// Gerado manualmente: PNG header + IHDR + IDAT + IEND
function createMinimalPng(int $r, int $g, int $b): string {
    // PNG Signature
    $sig = "\x89PNG\r\n\x1a\n";
    
    // IHDR chunk: 1x1 pixel, 8-bit RGB
    $ihdr_data = pack('NNCCCC', 1, 1, 8, 2, 0, 0, 0); // width=1, height=1, bitdepth=8, colortype=2(RGB), compression=0, filter=0, interlace=0
    $ihdr = pack('N', 13) . 'IHDR' . $ihdr_data . pack('N', crc32('IHDR' . $ihdr_data));
    
    // IDAT chunk: zlib compressed pixel data (filter byte 0 + RGB)
    $raw = "\x00" . chr($r) . chr($g) . chr($b); // filter=None + 1 RGB pixel
    $compressed = gzcompress($raw);
    $idat = pack('N', strlen($compressed)) . 'IDAT' . $compressed . pack('N', crc32('IDAT' . $compressed));
    
    // IEND chunk
    $iend = pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));
    
    return $sig . $ihdr . $idat . $iend;
}

// JPEG mínimo: usa um JPEG válido com cor sólida
function createMinimalJpeg(int $r, int $g, int $b): string {
    // Minimal valid JPEG (1x1 pixel)
    // SOI + APP0 + DQT + SOF0 + DHT + SOS + scan data + EOI
    // Isso é complexo, então vou usar um truque: salvar como PNG e renomear funciona em browsers
    // Mas melhor criar um JPEG real mínimo
    // Usar a abordagem de bytes hardcoded para 1x1 JPEG cinza
    $hex = 'ffd8ffe000104a46494600010100000100010000ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432ffdb0043010909090c0b0c180d0d1832211c213232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232ffc00011080001000103012200021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00';
    // Simplificar: Pixel data for specific color
    $hex .= 'fbbc8e0000ffd9'; // scan data + EOI (cinza genérico)
    
    return hex2bin($hex);
}

// WebP mínimo: RIFF header + VP8 bitstream para 1x1
function createMinimalWebp(int $r, int $g, int $b): string {
    // VP8 bitstream mínimo para 1x1 pixel
    // É mais complexo, então usamos PNG como fallback (browsers aceitam)
    return createMinimalPng($r, $g, $b);
}

$colors = [
    'dark' => [58, 59, 78],       // #3a3b4e
    'green' => [26, 71, 42],      // #1a472a
    'blue' => [68, 96, 132],      // #446084
    'dark2' => [44, 62, 80],      // #2c3e50
    'dark3' => [52, 73, 94],      // #34495e
    'black' => [26, 26, 46],      // #1a1a2e
    'whatsapp' => [37, 211, 102], // #25d366
    'red' => [231, 76, 60],       // #e74c3c
    'light' => [240, 240, 240],   // #f0f0f0
];

$fileColors = [
    'images/wp/2024/11/logo-brooks-1400x396.webp' => 'dark',
    'images/wp/2024/11/logo-brooks-1-800x227.webp' => 'dark',
    'images/wp/2024/11/IMG_2477-1-jpg.webp' => 'dark2',
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-scaled.webp' => 'dark3',
    'images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp' => 'dark3',
    'images/wp/2024/11/bergamo-jpg.webp' => 'green',
    'images/wp/2024/11/palacio-bandeirantes-jpg.webp' => 'dark2',
    'images/wp/2024/11/escritorio-itaim-jpeg.webp' => 'dark3',
    'images/wp/2024/11/mansao-alphaville-jpeg.webp' => 'green',
    'images/wp/2024/11/bergamo2-jpg.webp' => 'dark2',
    'images/wp/2023/01/whatsapp.png' => 'whatsapp',
    'images/wp/2023/01/png_20230107_215416_0000-1.png' => 'blue',
    'images/wp/2023/01/png_20230108_092659_0000-2.png' => 'blue',
    'images/wp/2023/01/png_20230107_221615_0000-1.png' => 'blue',
    'images/wp/2023/01/png_20230108_091744_0000-1.png' => 'blue',
    'images/wp/2023/01/png_20230108_093143_0000-1.png' => 'blue',
    'images/wp/2023/01/png_20230108_091554_0000-1.png' => 'blue',
    'images/wp/2023/01/icone-pdf-1.png' => 'red',
    'images/wp/2023/01/fundo-3.jpg' => 'black',
    'images/wp/2023/01/fundo-1.jpg' => 'black',
    'images/wp/2023/01/GUR1123-HDR-2-scaled.jpg' => 'green',
    'images/wp/2023/01/cropped-favicon-1-32x32.png' => 'dark',
    'images/wp/2023/01/cropped-favicon-1-192x192.png' => 'dark',
    'images/wp/2023/01/cropped-favicon-1-180x180.png' => 'dark',
];

// Galeria
for ($i = 1; $i <= 8; $i++) {
    $fileColors["images/wp/2023/01/projeto{$i}-1-400x400.jpeg"] = 'dark';
    $fileColors["images/wp/2023/01/projeto{$i}-1.jpeg"] = 'dark';
}
// Avaliações
for ($i = 1; $i <= 20; $i++) {
    $num = str_pad($i, 3, '0', STR_PAD_LEFT);
    $fileColors["images/wp/2023/01/avaliacao{$num}-1-227x400.jpg"] = 'light';
    $fileColors["images/wp/2023/01/avaliacao{$num}-1.jpg"] = 'light';
}

echo "Gerando imagens placeholder binárias...\n\n";
$created = 0;

foreach ($fileColors as $relPath => $colorKey) {
    $fullPath = $basePath . '/' . $relPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    [$r, $g, $b] = $colors[$colorKey];
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'webp':
        case 'png':
            $data = createMinimalPng($r, $g, $b);
            break;
        case 'jpg':
        case 'jpeg':
            // JPEG é complexo sem GD, usar PNG (browsers aceitam PNG com extensão .jpg na maioria dos casos)
            // Alternativa: criar arquivo com header JPEG válido
            $data = createMinimalPng($r, $g, $b);
            break;
        default:
            $data = createMinimalPng($r, $g, $b);
    }
    
    file_put_contents($fullPath, $data);
    $created++;
}

echo "Criados: {$created} placeholders binários\n";
echo "\nPronto! As imagens agora são PNGs válidos de 1x1 pixel.\n";
echo "Os navegadores vão renderizá-las como blocos de cor.\n";
echo "Substitua pelos arquivos reais do backup do WordPress.\n";
