[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$basePath = "d:\Projects\GitHub\brooks-construtora"
$cleanDir = "$basePath\htmls\clean"
$viewDir = "$basePath\app\Views\site\projects"

# Ensure the output directory exists
if (!(Test-Path $viewDir)) {
    New-Item -ItemType Directory -Path $viewDir -Force
}

$projects = @(
    @{ file = "projeto-rocha-andrade.html"; slug = "projeto-rocha-andrade" },
    @{ file = "projeto-norah-carneiro.html"; slug = "projeto-norah-carneiro" },
    @{ file = "projeto-joia-bergamo-2.html"; slug = "projeto-joia-bergamo-2" },
    @{ file = "projeto-joia-bergamo-reforma-rsvp.html"; slug = "projeto-joia-bergamo-reforma-rsvp" },
    @{ file = "reforma-completa-de-mansao-no-alphaville.html"; slug = "reforma-completa-de-mansao-no-alphaville" },
    @{ file = "reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes.html"; slug = "reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes" },
    @{ file = "reforma-corporativa-de-escritorio-no-itaim-bibi.html"; slug = "reforma-corporativa-de-escritorio-no-itaim-bibi" }
)

foreach ($project in $projects) {
    $filePath = "$cleanDir\$($project.file)"
    
    if (!(Test-Path $filePath)) {
        Write-Host "SKIP: $filePath not found"
        continue
    }
    
    Write-Host "Processing: $($project.file)..."
    
    $content = Get-Content $filePath -Raw -Encoding UTF8
    
    # Extract title from <title> tag
    $titleMatch = [regex]::Match($content, '<title>(.+?)</title>')
    $pageTitle = ""
    if ($titleMatch.Success) {
        $pageTitle = $titleMatch.Groups[1].Value
        # Remove " – Brooks Construtora" or similar suffixes
        $pageTitle = $pageTitle -replace '\s*&#8211;\s*Brooks Construtora$', ''
        $pageTitle = $pageTitle -replace '\s*&ndash;\s*Brooks Construtora$', ''
        # Decode HTML entities for proper display
        $pageTitle = [System.Net.WebUtility]::HtmlDecode($pageTitle)
    }
    
    # Extract content between <div id="content" and </main>
    $contentStart = $content.IndexOf('<div id="content"')
    $mainEnd = $content.IndexOf('</main>')
    
    if ($contentStart -eq -1 -or $mainEnd -eq -1) {
        Write-Host "  ERROR: Could not find content boundaries in $($project.file)"
        Write-Host "  contentStart: $contentStart, mainEnd: $mainEnd"
        continue
    }
    
    $mainContent = $content.Substring($contentStart, $mainEnd - $contentStart)
    
    # Replace WordPress upload URLs with local paths
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/wp-content/uploads/', '/assets/images/wp/'
    
    # Replace WordPress internal project links
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/projeto-([^/"]+)/', '/projeto/projeto-$1'
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/reforma-([^/"]+)/', '/projeto/reforma-$1'
    
    # Replace other internal links
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/contato/', '/contato'
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/sobre/', '/sobre'
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/projetos/', '/projetos'
    $mainContent = $mainContent -replace 'https://www\.brooksconstrutora\.com\.br/?(["\s])', '/$1'
    
    # Remove WordPress admin bar markup
    $mainContent = $mainContent -replace '(?s)<div id="wpadminbar".*?</div>\s*</div>\s*</div>', ''
    
    # Remove wp-json links
    $mainContent = $mainContent -replace '(?s)<link rel="https://api\.w\.org/.*?/>', ''
    
    # Build the PHP view file
    $phpContent = @"
<?php `$pageTitle = '$($pageTitle -replace "'", "\'")'; `$currentPage = 'projetos'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

$mainContent

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
"@
    
    $outputFile = "$viewDir\$($project.slug).php"
    [System.IO.File]::WriteAllText($outputFile, $phpContent, [System.Text.Encoding]::UTF8)
    
    Write-Host "  Created: $outputFile"
    Write-Host "  Title: $pageTitle"
}

Write-Host "`nAll project pages processed!"
