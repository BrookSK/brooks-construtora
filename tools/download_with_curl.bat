@echo off
echo === Baixando assets do WordPress ===
echo.

set BASE=https://www.brooksconstrutora.com.br/wp-content/uploads
set OUT=d:\Projects\GitHub\brooks-construtora\public\assets\images\wp

:: Cria diretorios
mkdir "%OUT%\2024\11" 2>NUL
mkdir "%OUT%\2023\01" 2>NUL

echo [1/50] Logo principal...
curl -s -o "%OUT%\2024\11\logo-brooks-1400x396.webp" "%BASE%/2024/11/logo-brooks-1400x396.webp"

echo [2/50] Logo branca...
curl -s -o "%OUT%\2024\11\logo-brooks-1-800x227.webp" "%BASE%/2024/11/logo-brooks-1-800x227.webp"

echo [3/50] Projeto Rocha Andrade...
curl -s -o "%OUT%\2024\11\IMG_2477-1-jpg.webp" "%BASE%/2024/11/IMG_2477-1-jpg.webp"

echo [4/50] Norah Carneiro scaled...
curl -s -o "%OUT%\2024\11\NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-scaled.webp" "%BASE%/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-scaled.webp"

echo [5/50] Norah Carneiro 1...
curl -s -o "%OUT%\2024\11\NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp" "%BASE%/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp"

echo [6/50] Bergamo...
curl -s -o "%OUT%\2024\11\bergamo-jpg.webp" "%BASE%/2024/11/bergamo-jpg.webp"

echo [7/50] Palacio Bandeirantes...
curl -s -o "%OUT%\2024\11\palacio-bandeirantes-jpg.webp" "%BASE%/2024/11/palacio-bandeirantes-jpg.webp"

echo [8/50] Escritorio Itaim...
curl -s -o "%OUT%\2024\11\escritorio-itaim-jpeg.webp" "%BASE%/2024/11/escritorio-itaim-jpeg.webp"

echo [9/50] Mansao Alphaville...
curl -s -o "%OUT%\2024\11\mansao-alphaville-jpeg.webp" "%BASE%/2024/11/mansao-alphaville-jpeg.webp"

echo [10/50] Bergamo 2...
curl -s -o "%OUT%\2024\11\bergamo2-jpg.webp" "%BASE%/2024/11/bergamo2-jpg.webp"

echo [11/50] WhatsApp icon...
curl -s -o "%OUT%\2023\01\whatsapp.png" "%BASE%/2023/01/whatsapp.png"

echo [12/50] Diferencial 1...
curl -s -o "%OUT%\2023\01\png_20230107_215416_0000-1.png" "%BASE%/2023/01/png_20230107_215416_0000-1.png"

echo [13/50] Diferencial 2...
curl -s -o "%OUT%\2023\01\png_20230108_092659_0000-2.png" "%BASE%/2023/01/png_20230108_092659_0000-2.png"

echo [14/50] Diferencial 3...
curl -s -o "%OUT%\2023\01\png_20230107_221615_0000-1.png" "%BASE%/2023/01/png_20230107_221615_0000-1.png"

echo [15/50] Diferencial 4...
curl -s -o "%OUT%\2023\01\png_20230108_091744_0000-1.png" "%BASE%/2023/01/png_20230108_091744_0000-1.png"

echo [16/50] Diferencial 5...
curl -s -o "%OUT%\2023\01\png_20230108_093143_0000-1.png" "%BASE%/2023/01/png_20230108_093143_0000-1.png"

echo [17/50] Diferencial 6...
curl -s -o "%OUT%\2023\01\png_20230108_091554_0000-1.png" "%BASE%/2023/01/png_20230108_091554_0000-1.png"

echo [18/50] Icone PDF...
curl -s -o "%OUT%\2023\01\icone-pdf-1.png" "%BASE%/2023/01/icone-pdf-1.png"

echo [19/50] Fundo parallax...
curl -s -o "%OUT%\2023\01\fundo-3.jpg" "%BASE%/2023/01/fundo-3.jpg"

echo [20/50] Fundo mobile...
curl -s -o "%OUT%\2023\01\fundo-1.jpg" "%BASE%/2023/01/fundo-1.jpg"

echo [21/50] GUR Bergamo...
curl -s -o "%OUT%\2023\01\GUR1123-HDR-2-scaled.jpg" "%BASE%/2023/01/GUR1123-HDR-2-scaled.jpg"

echo [22/50] Favicon 32...
curl -s -o "%OUT%\2023\01\cropped-favicon-1-32x32.png" "%BASE%/2023/01/cropped-favicon-1-32x32.png"

echo [23/50] Favicon 192...
curl -s -o "%OUT%\2023\01\cropped-favicon-1-192x192.png" "%BASE%/2023/01/cropped-favicon-1-192x192.png"

echo [24/50] Favicon 180...
curl -s -o "%OUT%\2023\01\cropped-favicon-1-180x180.png" "%BASE%/2023/01/cropped-favicon-1-180x180.png"

echo Baixando galeria de projetos...
for /L %%i in (1,1,8) do (
    curl -s -o "%OUT%\2023\01\projeto%%i-1-400x400.jpeg" "%BASE%/2023/01/projeto%%i-1-400x400.jpeg"
    curl -s -o "%OUT%\2023\01\projeto%%i-1.jpeg" "%BASE%/2023/01/projeto%%i-1.jpeg"
)

echo Baixando avaliacoes...
for /L %%i in (1,1,9) do (
    curl -s -o "%OUT%\2023\01\avaliacao00%%i-1-227x400.jpg" "%BASE%/2023/01/avaliacao00%%i-1-227x400.jpg"
    curl -s -o "%OUT%\2023\01\avaliacao00%%i-1.jpg" "%BASE%/2023/01/avaliacao00%%i-1.jpg"
)
for /L %%i in (10,1,20) do (
    curl -s -o "%OUT%\2023\01\avaliacao0%%i-1-227x400.jpg" "%BASE%/2023/01/avaliacao0%%i-1-227x400.jpg"
    curl -s -o "%OUT%\2023\01\avaliacao0%%i-1.jpg" "%BASE%/2023/01/avaliacao0%%i-1.jpg"
)

echo.
echo Baixando CSS do Flatsome...
set THEME=https://www.brooksconstrutora.com.br/wp-content/themes/flatsome
set FOUT=d:\Projects\GitHub\brooks-construtora\public\assets\flatsome

mkdir "%FOUT%\assets\css\icons" 2>NUL

curl -s -o "%FOUT%\assets\css\flatsome.css" "%THEME%/assets/css/flatsome.css?ver=3.16.4"
curl -s -o "%FOUT%\assets\css\icons\fl-icons.woff2" "%THEME%/assets/css/icons/fl-icons.woff2?v=3.16.4"
curl -s -o "%FOUT%\assets\css\icons\fl-icons.woff" "%THEME%/assets/css/icons/fl-icons.woff?v=3.16.4"
curl -s -o "%FOUT%\assets\css\icons\fl-icons.ttf" "%THEME%/assets/css/icons/fl-icons.ttf?v=3.16.4"

echo.
echo === Download concluido ===
