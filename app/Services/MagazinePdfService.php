<?php
<?php

namespace App\Services;

use App\Models\Magazine;
use App\Models\Setting;

/**
 * Gera o PDF de uma revista no servidor.
 *
 * Estratégia: monta um HTML standalone (mesmo layout do site) e tenta
 * convertê-lo em PDF usando o binário `wkhtmltopdf` via exec().
 *
 * Se o binário não estiver disponível no servidor, retorna null — nesse
 * caso o chamador deve cair para o plano B (link de download no e-mail).
 */
class MagazinePdfService
{
    /**
     * Gera o PDF e devolve o caminho absoluto do arquivo, ou null se não foi possível.
     */
    public static function generate(int $magazineId): ?string
    {
        // Nunca deve lançar erro fatal: em qualquer falha retorna null
        // (o chamador cai no plano B — botão de download no e-mail).
        try {
            $magazine = Magazine::find($magazineId);
            if (!$magazine) return null;

            $pages = Magazine::getPages($magazineId);
            if (empty($pages)) return null;

            // exec() precisa estar disponível no servidor
            if (!function_exists('exec')) {
                error_log('[MAGAZINE_PDF] exec() desabilitado no servidor.');
                return null;
            }

            // Descobrir o binário do wkhtmltopdf
            $binary = self::findBinary();
            if (!$binary) {
                error_log('[MAGAZINE_PDF] wkhtmltopdf não encontrado no servidor.');
                return null;
            }

            // Montar HTML standalone
            $html = self::buildHtml($magazine, $pages);

            // Arquivos temporários
            $tmpDir = ROOT_PATH . '/public/uploads/magazine_pdfs';
            if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);
            if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
                error_log('[MAGAZINE_PDF] Diretório temporário não gravável: ' . $tmpDir);
                return null;
            }

            $htmlFile = $tmpDir . '/mag_' . $magazineId . '_' . time() . '.html';
            $pdfFile  = $tmpDir . '/Revista_Brooks_' . $magazineId . '_' . time() . '.pdf';

            file_put_contents($htmlFile, $html);

            // Executar a conversão
            $cmd = escapeshellcmd($binary)
                . ' --enable-local-file-access'
                . ' --print-media-type'
                . ' --page-size A4'
                . ' --margin-top 0 --margin-bottom 0 --margin-left 0 --margin-right 0'
                . ' ' . escapeshellarg($htmlFile)
                . ' ' . escapeshellarg($pdfFile)
                . ' 2>&1';

            @exec($cmd, $output, $returnCode);

            // Limpar HTML temporário
            @unlink($htmlFile);

            if ($returnCode !== 0 || !is_file($pdfFile) || filesize($pdfFile) === 0) {
                error_log('[MAGAZINE_PDF] Falha na conversão. code=' . $returnCode . ' out=' . implode(' ', (array) $output));
                @unlink($pdfFile);
                return null;
            }

            return $pdfFile;
        } catch (\Throwable $e) {
            error_log('[MAGAZINE_PDF] Exceção ao gerar PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procura o binário do wkhtmltopdf em locais comuns e no PATH.
     */
    private static function findBinary(): ?string
    {
        // Permite configurar o caminho manualmente
        $configured = Setting::get('wkhtmltopdf_path', '');
        if (!empty($configured) && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            '/usr/bin/wkhtmltopdf',
            '/usr/local/bin/wkhtmltopdf',
            '/opt/wkhtmltox/bin/wkhtmltopdf',
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) return $c;
        }

        // Tentar via 'which'
        $which = @shell_exec('which wkhtmltopdf 2>/dev/null');
        if ($which && trim($which) !== '' && is_file(trim($which))) {
            return trim($which);
        }

        return null;
    }

    /**
     * Monta o HTML standalone da revista (mesmo visual do site).
     */
    private static function buildHtml(array $magazine, array $pages): string
    {
        try { $magazineLogo = Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
        if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';

        // Converter caminhos relativos de imagem em absolutos (file://) para o wkhtmltopdf
        $toLocal = function (string $url): string {
            if ($url === '') return '';
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
            $path = ROOT_PATH . '/public' . $url;
            return is_file($path) ? 'file://' . $path : $url;
        };

        $magazineLogo = $toLocal($magazineLogo);
        $coverImage = !empty($magazine['cover_image']) ? $toLocal($magazine['cover_image']) : '';
        $year = date('Y');
        $siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
        $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        // CSS (mesmo do new-show.php, adaptado para impressão)
        $css = <<<CSS
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#fff}
.page{background:#fff;width:595px;height:842px;position:relative;overflow:hidden;page-break-after:always}
.pg-cover{background:#1a472a}
.pg-cover .overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.05) 35%,rgba(0,0,0,0.05) 50%,rgba(0,0,0,0.4) 70%,rgba(0,0,0,0.8) 90%,rgba(0,0,0,0.92) 100%)}
.pg-cover .content{position:relative;z-index:2;text-align:center;width:100%;height:100%;display:flex;flex-direction:column;padding:30px 40px}
.pg-cover .title{font-size:5rem;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:2px;margin-top:10px}
.pg-cover .sub-line{display:flex;align-items:center;justify-content:center;gap:15px;margin-top:5px;font-size:0.7rem;letter-spacing:5px;text-transform:uppercase;color:rgba(255,255,255,0.9)}
.pg-cover .sub-line .ln{width:40px;height:2px;background:#fff}
.pg-cover .logo{margin:auto;max-width:220px}
.pg-cover .topic{font-size:1.4rem;font-weight:800;color:#fff;font-style:italic;text-align:left;padding:15px 30px;margin-top:auto;margin-bottom:70px}
.pg-cover .foot{position:absolute;bottom:15px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.55rem;color:rgba(255,255,255,0.8)}
.pg-int{padding:30px 35px;height:842px;overflow:hidden}
.pg-int .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.pg-int .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.pg-int .logo-sm .ck{color:#2e7d32}
.pg-int .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.pg-int .pn{font-size:1rem;font-weight:300;color:#333}
.img-full{width:100%;object-fit:cover}
.img-half{width:48%;object-fit:cover}
.title-big{font-size:2.8rem;font-weight:900;color:#111;margin-bottom:15px;line-height:1.1}
.title-upper{font-size:0.7rem;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;color:#111;margin-bottom:18px;border-bottom:1px solid #ddd;padding-bottom:10px}
.subtitle{font-size:1.1rem;font-weight:400;color:#333;margin-bottom:18px}
.text{font-size:0.78rem;line-height:1.8;color:#333;margin-bottom:12px}
.text-sm{font-size:0.68rem;line-height:1.7;color:#444;margin-bottom:8px}
.caption{font-size:0.78rem;font-weight:700;color:#111;margin-top:10px}
.two-col{display:flex;gap:18px}
.two-col .col{flex:1}
.overlay-section{position:relative;width:100%;height:420px;overflow:hidden;margin-bottom:15px}
.overlay-section img{width:100%;height:100%;object-fit:cover}
.overlay-section .ov{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,50,0,0.95));padding:25px 30px 20px}
.overlay-section .ov h2{font-size:2rem;font-weight:900;color:#fff}
.overlay-section .ov p{font-size:0.6rem;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.8);margin-top:5px}
.pg-back{background:#1a3a2a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;height:842px}
.pg-back .logo{max-width:250px;margin-bottom:35px}
.pg-back .txt{color:rgba(255,255,255,0.85);font-size:0.9rem;max-width:380px;line-height:1.6}
.pg-back .bar{position:absolute;bottom:0;left:0;right:0;background:#e53935;padding:12px 25px;display:flex;justify-content:space-between;font-size:0.6rem;color:#fff}
.img-placeholder{background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;color:#2e7d32;font-size:0.6rem;text-transform:uppercase;letter-spacing:1px}
CSS;

        // Monta o HTML por concatenação de string pura (sem misturar tags PHP/HTML,
        // que causava erro de parsing "unexpected token '<'"). Robusto e previsível.
        $html  = '<!DOCTYPE html>';
        $html .= '<html lang="pt-BR"><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>';

        // Helper para renderizar parágrafos de texto
        $renderText = function ($content) use ($esc) {
            $out = '';
            foreach (explode("\n", (string) $content) as $p) {
                if (trim($p) !== '') $out .= '<p class="text">' . $esc(trim($p)) . '</p>';
            }
            return $out;
        };

        $intNum = 0;
        foreach ($pages as $page) {
            $showImages = ($page['show_images'] ?? '1') !== '0';
            $img1 = $showImages ? $toLocal($page['image_url'] ?? '') : '';
            $img2 = $showImages ? $toLocal($page['image_url_2'] ?? '') : '';
            $img3 = $showImages ? $toLocal($page['image_url_3'] ?? '') : '';
            $layout = $page['layout_type'] ?? 'internal_01';
            if (!in_array($layout, ['cover', 'subcover', 'backcover'])) $intNum++;
            $pn = str_pad((string) $intNum, 2, '0', STR_PAD_LEFT);

            $coverStyle = $coverImage
                ? ' style="background-image:url(\'' . $coverImage . '\');background-size:cover;background-position:center;"'
                : '';
            $subParts = explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — SUSTENTÁVEL');
            $sub0 = $esc($subParts[0] ?? 'CONSTRUÇÃO');
            $sub1 = $esc(trim($subParts[1] ?? 'SUSTENTÁVEL'));
            $header = '<div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn">' . $pn . '</div></div>';

            if ($layout === 'cover') {
                $html .= '<div class="page pg-cover"' . $coverStyle . '>';
                $html .= '<div class="overlay"></div><div class="content">';
                $html .= '<div class="title">' . $esc($page['title'] ?? $magazine['title']) . '</div>';
                $html .= '<div class="sub-line"><span>' . $sub0 . '</span><span class="ln"></span><span>' . $sub1 . '</span></div>';
                $html .= '<img src="' . $magazineLogo . '" class="logo" alt="Brooks">';
                $html .= '<div class="topic">' . $esc($magazine['subtitle'] ?? '') . '</div>';
                $html .= '<div class="foot"><span>&copy; ' . $year . ' BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span><span>' . $siteUrl . '</span></div>';
                $html .= '</div></div>';
            } elseif ($layout === 'subcover') {
                $html .= '<div class="page pg-cover"' . $coverStyle . '>';
                $html .= '<div class="overlay"></div><div class="content">';
                $html .= '<div style="display:flex;align-items:center;gap:10px;justify-content:center;margin-top:20px;"><span style="font-size:3.5rem;font-weight:900;color:#fff;">' . $esc($page['title'] ?? 'ECO') . '</span><img src="' . $magazineLogo . '" style="max-width:180px" alt="Brooks"></div>';
                $html .= '<div class="sub-line" style="margin-top:12px"><span>' . $sub0 . '</span><span class="ln"></span><span>' . $sub1 . '</span></div>';
                $html .= '<div style="flex:1"></div>';
                $html .= '<div class="topic">' . $esc($magazine['subtitle'] ?? '') . '</div>';
                $html .= '<div class="foot"><span>&copy; ' . $year . ' BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span><span>' . $siteUrl . '</span></div>';
                $html .= '</div></div>';
            } elseif ($layout === 'internal_02') {
                $html .= '<div class="page pg-int">' . $header;
                $html .= '<div class="two-col" style="margin-bottom:15px"><div class="col">';
                if ($img1) $html .= '<img src="' . $img1 . '" style="width:100%;height:250px;object-fit:cover" alt="">';
                $html .= '</div><div class="col">';
                if (!empty($page['title'])) $html .= '<div class="title-upper" style="margin-top:10px">' . $esc($page['title']) . '</div>';
                $html .= '<p class="text-sm">' . $esc($page['subtitle'] ?? '') . '</p></div></div>';
                $html .= '<div class="title-big">' . $esc($page['title'] ?? '') . '</div>';
                $html .= '<div class="two-col"><div class="col">' . $renderText($page['content'] ?? '') . '</div><div class="col">';
                if ($img2) $html .= '<img src="' . $img2 . '" style="width:100%;height:150px;object-fit:cover" alt="">';
                $html .= '</div></div></div>';
            } elseif ($layout === 'internal_03') {
                $html .= '<div class="page pg-int">' . $header;
                $html .= '<div class="title-big">' . $esc($page['title'] ?? '') . '</div>';
                if (!empty($page['subtitle'])) $html .= '<div class="subtitle">' . $esc($page['subtitle']) . '</div>';
                $html .= $renderText($page['content'] ?? '');
                $html .= '<div style="display:flex;gap:10px;margin-top:15px">';
                if ($img1) $html .= '<img src="' . $img1 . '" class="img-half" style="height:260px" alt="">';
                if ($img2) $html .= '<img src="' . $img2 . '" class="img-half" style="height:260px" alt="">';
                $html .= '</div>';
                if (!empty($page['caption'])) $html .= '<div class="caption">' . $esc($page['caption']) . '</div>';
                $html .= '</div>';
            } elseif ($layout === 'internal_04') {
                $html .= '<div class="page pg-int">' . $header;
                $html .= '<div class="overlay-section">';
                if ($img1) $html .= '<img src="' . $img1 . '" alt="">';
                $html .= '<div class="ov"><h2>' . $esc($page['title'] ?? '') . '</h2>';
                if (!empty($page['subtitle'])) $html .= '<p>' . $esc($page['subtitle']) . '</p>';
                $html .= '</div></div>';
                $html .= $renderText($page['content'] ?? '');
                $html .= '</div>';
            } elseif ($layout === 'backcover') {
                $html .= '<div class="page pg-back">';
                $html .= '<img src="' . $magazineLogo . '" class="logo" alt="Brooks Construtora">';
                $html .= '<div class="txt">' . nl2br($esc($page['content'] ?? 'Construção consciente do zero ao acabamento.')) . '</div>';
                $html .= '<div class="bar"><span>&copy; ' . $year . ' BROOKS CONSTRUTORA.</span><span>' . $siteUrl . '</span></div>';
                $html .= '</div>';
            } else { // internal_01 e fallback
                $html .= '<div class="page pg-int">' . $header;
                if ($img1) $html .= '<img src="' . $img1 . '" class="img-full" style="height:300px;margin-bottom:18px" alt="">';
                if (!empty($page['title'])) $html .= '<div class="title-upper">' . $esc($page['title']) . '</div>';
                $html .= '<div class="two-col"><div class="col">' . $renderText($page['content'] ?? '') . '</div><div class="col">';
                if ($img2) $html .= '<img src="' . $img2 . '" style="width:100%;height:280px;object-fit:cover" alt="">';
                $html .= '</div></div></div>';
            }
        }

        $html .= '</body></html>';

        return $html;
    }
}
