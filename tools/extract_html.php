<?php
/**
 * Extract clean HTML from Chrome's "View Source" saved HTML files.
 * 
 * Chrome's view-source wraps each line in <td class="line-content"> elements
 * and adds syntax highlighting spans (html-tag, html-attribute-name, etc.)
 * and <a> tags wrapping URLs. This script strips all that wrapper markup
 * and reconstructs the original HTML source.
 */

// File mapping: source filename => clean output filename
$files = [
    'view-source_https___www.brooksconstrutora.com.br.html' => 'home.html',
    'view-source_https___www.brooksconstrutora.com.br_projeto-joia-bergamo-2_.html' => 'projeto-joia-bergamo-2.html',
    'view-source_https___www.brooksconstrutora.com.br_projeto-joia-bergamo-reforma-rsvp_.html' => 'projeto-joia-bergamo-reforma-rsvp.html',
    'view-source_https___www.brooksconstrutora.com.br_projeto-norah-carneiro_.html' => 'projeto-norah-carneiro.html',
    'view-source_https___www.brooksconstrutora.com.br_projeto-rocha-andrade_.html' => 'projeto-rocha-andrade.html',
    'view-source_https___www.brooksconstrutora.com.br_reforma-completa-de-mansao-no-alphaville_.html' => 'reforma-completa-de-mansao-no-alphaville.html',
    'view-source_https___www.brooksconstrutora.com.br_reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes_.html' => 'reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes.html',
    'view-source_https___www.brooksconstrutora.com.br_reforma-corporativa-de-escritorio-no-itaim-bibi_.html' => 'reforma-corporativa-de-escritorio-no-itaim-bibi.html',
];

$inputDir = __DIR__ . '/../htmls/';
$outputDir = __DIR__ . '/../htmls/clean/';

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created output directory: $outputDir\n";
}

foreach ($files as $sourceFile => $outputFile) {
    $inputPath = $inputDir . $sourceFile;
    $outputPath = $outputDir . $outputFile;

    if (!file_exists($inputPath)) {
        echo "WARNING: Source file not found: $inputPath\n";
        continue;
    }

    echo "Processing: $sourceFile\n";

    $html = file_get_contents($inputPath);

    // Extract all <td class="line-content"> cells
    // Pattern matches the content between <td class="line-content"> and </td>
    preg_match_all('/<td class="line-content">(.*?)<\/td>/s', $html, $matches);

    if (empty($matches[1])) {
        echo "  ERROR: No line-content cells found in $sourceFile\n";
        continue;
    }

    $lines = [];
    foreach ($matches[1] as $lineContent) {
        // Handle empty lines (Chrome uses <br> for empty lines)
        if ($lineContent === '<br>' || $lineContent === '<br/>') {
            $lines[] = '';
            continue;
        }

        // Remove trailing <br> if present
        $lineContent = preg_replace('/<br\s*\/?>$/', '', $lineContent);

        // Step 1: Remove <a> tags but keep their text content
        // Chrome wraps URLs in <a class="html-attribute-value html-resource-link" ...>URL</a>
        $lineContent = preg_replace('/<a[^>]*class="[^"]*html-(?:attribute-value|resource-link)[^"]*"[^>]*>(.*?)<\/a>/s', '$1', $lineContent);

        // Step 2: Remove all remaining <a> tags (keep content)
        $lineContent = preg_replace('/<a[^>]*>(.*?)<\/a>/s', '$1', $lineContent);

        // Step 3: Remove all span tags (html-tag, html-attribute-name, html-attribute-value, html-doctype, html-comment, etc.)
        // Keep the text content inside them
        $lineContent = preg_replace('/<span[^>]*>/s', '', $lineContent);
        $lineContent = str_replace('</span>', '', $lineContent);

        // Step 4: Decode HTML entities to get original characters
        // &lt; -> <, &gt; -> >, &amp; -> &, &quot; -> ", etc.
        $lineContent = html_entity_decode($lineContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines[] = $lineContent;
    }

    // Join lines with newline
    $cleanHtml = implode("\n", $lines);

    // Write the clean HTML
    file_put_contents($outputPath, $cleanHtml);

    $lineCount = count($lines);
    $fileSize = strlen($cleanHtml);
    echo "  -> Saved: $outputFile ($lineCount lines, " . number_format($fileSize) . " bytes)\n";
}

echo "\nDone! All files processed.\n";
