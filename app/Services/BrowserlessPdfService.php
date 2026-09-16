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
     * Teto mensal padrão de gerações (margem de segurança do plano gratuito do
     * Browserless, que costuma ser ~1.000 unidades/mês). Fica bem abaixo para
     * nunca estourar. Pode ser ajustado pela Setting 'browserless_monthly_limit'.
     */
    private const DEFAULT_MONTHLY_LIMIT = 800;

    /**
     * Verifica se ainda há cota no mês. Retorna ['allowed'=>bool, 'used'=>int,
     * 'limit'=>int, 'remaining'=>int]. O contador é reiniciado a cada mês.
     */
    public static function usageStatus(): array
    {
        $limit = (int) Setting::get('browserless_monthly_limit', (string) self::DEFAULT_MONTHLY_LIMIT);
        if ($limit <= 0) $limit = self::DEFAULT_MONTHLY_LIMIT;

        $month = date('Y-m');
        $storedMonth = (string) Setting::get('browserless_usage_month', '');
        $used = (int) Setting::get('browserless_usage_count', '0');

        // Virou o mês → zera o contador.
        if ($storedMonth !== $month) {
            $used = 0;
        }

        $remaining = max(0, $limit - $used);
        return [
            'allowed' => $used < $limit,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'month' => $month,
        ];
    }

    /**
     * Registra uma geração bem-sucedida no contador do mês.
     */
    private static function registerUsage(): void
    {
        $month = date('Y-m');
        $storedMonth = (string) Setting::get('browserless_usage_month', '');
        $used = (int) Setting::get('browserless_usage_count', '0');
        if ($storedMonth !== $month) {
            $used = 0;
        }
        Setting::set('browserless_usage_month', $month);
        Setting::set('browserless_usage_count', (string) ($used + 1));
    }

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

            // Trava de segurança: não gera se o limite mensal foi atingido
            // (protege contra estourar o plano gratuito).
            $usage = self::usageStatus();
            if (!$usage['allowed']) {
                error_log('[BROWSERLESS] Limite mensal atingido: ' . $usage['used'] . '/' . $usage['limit']);
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

            // Só conta quando deu certo (uma chamada consumida no plano).
            self::registerUsage();

            // Gera as IMAGENS das páginas (uma por folha), renderizadas pelo
            // Chrome do Browserless — ficam pixel a pixel iguais ao PDF, com
            // sombras/gradientes/efeitos. O visualizador do site exibe essas
            // imagens (em vez do PDF.js, que não suporta sombras/ShadingType 1
            // e deixava a capa rosa). Best-effort: se falhar, o PDF já está salvo.
            try {
                self::generatePageImages($magazineId, $token, $host);
            } catch (\Throwable $e) {
                error_log('[BROWSERLESS] Falha ao gerar imagens das páginas: ' . $e->getMessage());
            }

            return '/uploads/magazine_pdfs/' . $filename;
        } catch (\Throwable $e) {
            error_log('[BROWSERLESS] Exceção: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Captura UMA imagem de página inteira (fullPage) da revista via /screenshot.
     * É uma única chamada — rápido e sem risco de timeout. A imagem contém
     * TODAS as páginas empilhadas (a revista rola no visualizador). Renderizada
     * pelo Chrome, mantém sombras/gradientes/efeitos, idêntica ao PDF.
     */
    private static function generatePageImages(int $magazineId, string $token, string $host): void
    {
        $dir = ROOT_PATH . '/public/uploads/magazine_pdfs';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        if (!is_dir($dir) || !is_writable($dir)) return;

        // Remove imagens antigas desta revista.
        foreach (glob($dir . '/Revista_' . $magazineId . '_p*.png') ?: [] as $old) {
            @unlink($old);
        }

        if (!self::usageStatus()['allowed']) return;

        $previewUrl = self::buildPreviewUrl($magazineId);
        $endpoint = rtrim($host, '/') . '/screenshot?token=' . urlencode($token);

        $payload = [
            'url' => $previewUrl,
            'gotoOptions' => ['waitUntil' => 'networkidle2', 'timeout' => 50000],
            'waitForTimeout' => 3000,
            'bestAttempt' => true,
            'options' => [
                'type' => 'png',
                'fullPage' => true,
            ],
            // Largura fixa da folha para a imagem sair no tamanho da revista.
            'viewport' => ['width' => 595, 'height' => 842, 'deviceScaleFactor' => 2],
        ];

        [$body, $httpCode, $contentType] = self::postToBrowserless($endpoint, $payload);
        $isImage = ($httpCode === 200)
            && (stripos($contentType, 'image/') !== false || substr((string) $body, 0, 8) === "\x89PNG\r\n\x1a\n");

        if (!$isImage) {
            error_log('[BROWSERLESS] Screenshot fullPage falhou. code=' . $httpCode . ' type=' . $contentType);
            return;
        }

        file_put_contents($dir . '/Revista_' . $magazineId . '_p01.png', $body);
        self::registerUsage(); // 1 chamada consumida
    }

    /**
     * Faz um POST JSON ao Browserless e devolve [body, httpCode, contentType].
     */
    private static function postToBrowserless(string $endpoint, array $payload): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Cache-Control: no-cache'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return [$body, $httpCode, $contentType];
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
     * Lista as URLs relativas das imagens de página geradas (em ordem), ou []
     * se ainda não houver. Usado pelo visualizador do site.
     */
    public static function existingPageImages(int $magazineId): array
    {
        $dir = ROOT_PATH . '/public/uploads/magazine_pdfs';
        $files = glob($dir . '/Revista_' . $magazineId . '_p[0-9][0-9].png') ?: [];
        sort($files);
        $urls = [];
        foreach ($files as $f) {
            $urls[] = '/uploads/magazine_pdfs/' . basename($f);
        }
        return $urls;
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
