<?php

namespace App\Services;

use App\Models\Magazine;
use App\Models\Setting;

/**
 * Gera o PDF da revista usando a API do Browserless (um Chrome real na nuvem).
 *
 * Por que Browserless: o PDF é renderizado por um Chrome de verdade no
 * servidor da Browserless — capa com imagem de fundo, gradientes, sobreposição
 * e tudo mais saem FIÉIS e IDÊNTICOS, independentemente do dispositivo/navegador
 * de quem gera ou de quem depois visualiza. Isso elimina a divergência do
 * Safari/iPhone, que era a raiz do problema.
 *
 * Fluxo: monta a URL de preview isolada da revista (com token, abre sem login)
 * → chama POST /pdf do Browserless → salva o PDF em /public/uploads/magazine_pdfs.
 *
 * Sem token configurado (Setting 'browserless_token') retorna null — o chamador
 * cai no fallback (exibir a revista em HTML).
 */
class BrowserlessPdfService
{
    /** Região padrão do endpoint. Pode ser sobrescrita por Setting. */
    private const DEFAULT_HOST = 'https://production-sfo.browserless.io';

    /**
     * Gera o PDF e devolve a URL pública relativa do arquivo (ex.:
     * /uploads/magazine_pdfs/Revista_15.pdf), ou null em caso de falha.
     */
    public static function generate(int $magazineId): ?string
    {
        try {
            $token = trim((string) Setting::get('browserless_token', ''));
            if ($token === '') {
                error_log('[BROWSERLESS] Token não configurado.');
                return null;
            }

            $magazine = Magazine::find($magazineId);
            if (!$magazine) {
                return null;
            }

            if (!function_exists('curl_init')) {
                error_log('[BROWSERLESS] cURL não disponível no servidor.');
                return null;
            }

            $previewUrl = self::buildPreviewUrl($magazineId);
            $host = trim((string) Setting::get('browserless_host', '')) ?: self::DEFAULT_HOST;
            $endpoint = rtrim($host, '/') . '/pdf?token=' . urlencode($token);

            // Corpo da requisição: abre a URL de preview e gera o PDF em A4 com
            // os fundos/gradientes (printBackground). Espera a rede ficar ociosa
            // e um tempo extra para o JS de paginação e as fontes assentarem.
            $payload = [
                'url' => $previewUrl,
                'gotoOptions' => [
                    'waitUntil' => 'networkidle2',
                    'timeout' => 60000,
                ],
                // Espera adicional após carregar, para a paginação/fontes assentarem.
                'waitForTimeout' => 3500,
                'options' => [
                    'printBackground' => true,
                    // Dimensões exatas da folha (595x842) para cada .page virar
                    // UMA página do PDF, sem sobra/linha extra que o 'format:A4'
                    // (arredondamento de pontos) às vezes causa.
                    'width' => '595px',
                    'height' => '842px',
                    'preferCSSPageSize' => false,
                    'margin' => [
                        'top' => '0',
                        'bottom' => '0',
                        'left' => '0',
                        'right' => '0',
                    ],
                ],
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Cache-Control: no-cache',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 20,
            ]);

            $body = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($body === false || $curlError !== '') {
                error_log('[BROWSERLESS] Falha cURL: ' . $curlError);
                return null;
            }

            // A resposta de sucesso é o binário do PDF (application/pdf). Em erro,
            // vem JSON/texto com a mensagem — não é um PDF.
            $isPdf = ($httpCode === 200)
                && (stripos($contentType, 'application/pdf') !== false
                    || substr($body, 0, 4) === '%PDF');

            if (!$isPdf) {
                error_log('[BROWSERLESS] Resposta não-PDF. code=' . $httpCode
                    . ' type=' . $contentType . ' body=' . substr($body, 0, 300));
                return null;
            }

            // Salva o arquivo.
            $dir = ROOT_PATH . '/public/uploads/magazine_pdfs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (!is_dir($dir) || !is_writable($dir)) {
                error_log('[BROWSERLESS] Diretório não gravável: ' . $dir);
                return null;
            }

            // Nome estável por revista (sobrescreve o anterior a cada geração).
            $filename = 'Revista_' . $magazineId . '.pdf';
            $absolute = $dir . '/' . $filename;

            if (file_put_contents($absolute, $body) === false) {
                error_log('[BROWSERLESS] Falha ao salvar o PDF em ' . $absolute);
                return null;
            }

            return '/uploads/magazine_pdfs/' . $filename;
        } catch (\Throwable $e) {
            error_log('[BROWSERLESS] Exceção: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * URL pública (absoluta) da revista salva, ou null se o arquivo não existe.
     */
    public static function existingPdfUrl(int $magazineId): ?string
    {
        $rel = '/uploads/magazine_pdfs/Revista_' . $magazineId . '.pdf';
        return is_file(ROOT_PATH . '/public' . $rel) ? $rel : null;
    }

    /**
     * Monta a URL de preview isolada da revista, com token de acesso sem login.
     */
    private static function buildPreviewUrl(int $magazineId): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        $token = Magazine::ensurePreviewToken($magazineId);
        // &pdf=1 ativa o "modo PDF" na view: remove a barra de navegação, o
        // fundo cinza, o padding e as sombras entre páginas — só as folhas da
        // revista, coladas, sem margem. Assim o PDF não fica com página vazia
        // no topo nem faixas laterais.
        return $scheme . '://' . $host . '/revista/preview/' . $magazineId
            . '?preview=' . urlencode($token) . '&pdf=1';
    }
}
