<?php
$file = __DIR__ . '/../app/Views/site/home/index.php';
$content = file_get_contents($file);

// Slider links - replace by section context
$sliderMap = [
    'section_slide_1' => '/projeto/projeto-rocha-andrade',
    'section_slide_2' => '/projeto/projeto-norah-carneiro',
    'section_slide_3' => '/projeto/projeto-joia-bergamo-reforma-rsvp',
    'section_slide_4' => '/projeto/reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes',
    'section_slide_5' => '/projeto/reforma-corporativa-de-escritorio-no-itaim-bibi',
    'section_slide_6' => '/projeto/reforma-completa-de-mansao-no-alphaville',
    'section_slide_7' => '/projeto/projeto-joia-bergamo-2',
];

foreach ($sliderMap as $sectionId => $url) {
    // Find the section and replace the first /projetos link inside it
    $pattern = '/(id="' . $sectionId . '"[\s\S]*?)href="\/projetos"/';
    $content = preg_replace($pattern, '$1href="' . $url . '"', $content, 1);
}

// Grid project links - by image context
$gridMap = [
    'IMG_2477-1-jpg.webp' => '/projeto/projeto-rocha-andrade',
    'NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp' => '/projeto/projeto-norah-carneiro',
    'GUR1123-HDR-2-scaled.jpg' => '/projeto/projeto-joia-bergamo-reforma-rsvp',
    'palacio-bandeirantes-jpg.webp' => '/projeto/reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes',
    'escritorio-itaim-jpeg.webp' => '/projeto/reforma-corporativa-de-escritorio-no-itaim-bibi',
    'mansao-alphaville-jpeg.webp' => '/projeto/reforma-completa-de-mansao-no-alphaville',
    'bergamo2-jpg.webp' => '/projeto/projeto-joia-bergamo-2',
];

foreach ($gridMap as $image => $url) {
    // Find the <a href="/projetos"> that is immediately before an img containing this image
    $pattern = '/(<a href=")\/projetos(">\s*<div class="image-zoom[\s\S]*?' . preg_quote($image, '/') . ')/';
    $content = preg_replace($pattern, '$1' . $url . '$2', $content, 1);
}

file_put_contents($file, $content);
$remaining = substr_count($content, 'href="/projetos"');
echo "Done. Remaining /projetos links: {$remaining}\n";
