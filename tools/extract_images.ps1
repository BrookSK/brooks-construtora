$basePath = "d:\Projects\GitHub\brooks-construtora"
$cleanDir = "$basePath\htmls\clean"

$projectFiles = @(
    "projeto-rocha-andrade.html",
    "projeto-norah-carneiro.html",
    "projeto-joia-bergamo-2.html",
    "projeto-joia-bergamo-reforma-rsvp.html",
    "reforma-completa-de-mansao-no-alphaville.html",
    "reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes.html",
    "reforma-corporativa-de-escritorio-no-itaim-bibi.html"
)

$allUrls = @()

foreach ($file in $projectFiles) {
    $filePath = "$cleanDir\$file"
    if (!(Test-Path $filePath)) { continue }
    
    $content = Get-Content $filePath -Raw -Encoding UTF8
    
    # Find all /wp-content/uploads/ references
    $matches = [regex]::Matches($content, 'https://www\.brooksconstrutora\.com\.br/wp-content/uploads/([^\s"''<>)]+)')
    
    foreach ($match in $matches) {
        $url = $match.Value
        if ($url -notin $allUrls) {
            $allUrls += $url
        }
    }
}

# Sort and output unique URLs
$allUrls = $allUrls | Sort-Object -Unique

Write-Host "Found $($allUrls.Count) unique image URLs"

# Collect unique directories
$dirs = @()
$downloads = @()

foreach ($url in $allUrls) {
    $relativePath = $url -replace 'https://www\.brooksconstrutora\.com\.br/wp-content/uploads/', ''
    $localDir = "assets/images/wp/" + ($relativePath -replace '/[^/]+$', '')
    $localFile = "assets/images/wp/" + $relativePath
    
    if ($localDir -notin $dirs) {
        $dirs += $localDir
    }
    $downloads += @{ url = $url; file = $localFile }
}

# Generate batch file content
$batContent = "@echo off`r`nREM Download project images from Brooks Construtora WordPress site`r`nREM Generated automatically - run from the project root directory`r`nREM Total images: $($allUrls.Count)`r`n`r`necho Downloading project images ($($allUrls.Count) files)...`r`necho.`r`n`r`nREM Create directory structure`r`n"

foreach ($dir in ($dirs | Sort-Object)) {
    $winDir = $dir -replace '/', '\'
    $batContent += "if not exist `"$winDir`" mkdir `"$winDir`"`r`n"
}

$batContent += "`r`nREM Download images (skip if already exists)`r`n"

foreach ($dl in $downloads) {
    $winFile = $dl.file -replace '/', '\'
    $batContent += "if not exist `"$winFile`" curl -s -o `"$winFile`" `"$($dl.url)`"`r`n"
}

$batContent += "`r`necho.`r`necho Download complete!`r`npause`r`n"

$outputPath = "$basePath\tools\download_project_images.bat"
[System.IO.File]::WriteAllText($outputPath, $batContent, [System.Text.Encoding]::ASCII)

Write-Host "Batch file written to: $outputPath"
Write-Host "Directories: $($dirs.Count)"
