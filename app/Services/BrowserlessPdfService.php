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
     * Teto mensal padrão em UNIDADES do Browserless.
     *
     * IMPORTANTE: o Browserless cobra por TEMPO de navegador, não por geração.
     * Cada 30 segundos de sessão aberta = 1 unidade (arredondado pra cima). O
     * plano gratuito dá 1.000 unidades/mês. Deixamos o teto BEM abaixo (700)
     * para nunca estourar. Ajustável pela Setting 'browserless_monthly_limit'.
     */
    private const DEFAULT_MONTHLY_LIMIT = 700;

    /**
     * Estimativa CONSERVADORA de unidades por chamada. Cada chamada abre um
     * navegador que fica aberto alguns segundos (carrega a revista + espera +
     * captura). Como 1 unidade = 30s e o mínimo é sempre 1, contamos 3 unidades
     * por chamada para ter folga (é melhor superestimar e bloquear um pouco
     * antes do que subestimar e estourar o plano). Só vale quando a API real
     * de uso não responde — quando responde, usamos o número exato da conta.
     */
    private const UNITS_PER_CALL = 3;

    /**
     * Consulta o uso REAL na API do Browserless (fonte da verdade). Retorna o
     * número de unidades consumidas no ciclo, ou null se não conseguir ler.
     */
    /**
     * Consulta o uso REAL na API da conta Browserless.
     * Endpoint: GET https://api.browserless.io/v1/account/usage?token=...
     * Resposta:
     *   {"plan":{...},
     *    "units":{"included":1000,"used":13,"remaining":987},
     *    "billingPeriod":{"start":..., "end":...}}
     *
     * Retorna ['used'=>int, 'included'=>int] ou null se não conseguir ler.
     */
    private static function fetchRemoteUsage(string $token): ?array
    {
        if ($token === '' || !function_exists('curl_init')) return null;
        try {
            $ch = curl_init('https://api.browserless.io/v1/account/usage?token=' . urlencode($token));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code !== 200 || !$resp) return null;

            $data = json_decode((string) $resp, true);
            if (!is_array($data) || !isset($data['units']['used'])) return null;

            return [
                'used' => (int) ceil((float) $data['units']['used']),
                'included' => isset($data['units']['included']) ? (int) $data['units']['included'] : 0,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Situação do uso no mês (em UNIDADES). Prioriza o número real da API do
     * Browserless; se não conseguir, usa a estimativa local (conservadora).
     * Retorna ['allowed','used','limit','remaining','month','source'].
     */
    public static function usageStatus(): array
    {
        $limit = (int) Setting::get('browserless_monthly_limit', (string) self::DEFAULT_MONTHLY_LIMIT);
        if ($limit <= 0) $limit = self::DEFAULT_MONTHLY_LIMIT;

        $month = date('Y-m');
        $storedMonth = (string) Setting::get('browserless_usage_month', '');
        $localUsed = (int) Setting::get('browserless_usage_count', '0');
        if ($storedMonth !== $month) {
            $localUsed = 0; // virou o mês → zera a estimativa local
        }

        // Tenta o número REAL da conta (fonte da verdade).
        $token = trim((string) Setting::get('browserless_token', ''));
        $remote = self::fetchRemoteUsage($token);
        $source = 'local';
        $used = $localUsed;
        if ($remote !== null) {
            $used = $remote['used'];
            $source = 'api';
            // O limite efetivo é o MENOR entre o seu teto configurado e o
            // incluído no plano (ex.: 1000 do free) — o que vier primeiro trava.
            if (!empty($remote['included']) && $remote['included'] < $limit) {
                $limit = $remote['included'];
            }
        }

        $remaining = max(0, $limit - $used);
        return [
            'allowed' => $used < $limit,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'month' => $month,
            'source' => $source,
        ];
    }

    /**
     * Soma unidades (estimadas) ao contador local do mês. Usado após cada
     * chamada ao Browserless, como fallback quando a API de uso não responde.
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
        Setting::set('browserless_usage_count', (string) ($used + self::UNITS_PER_CALL));
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

            $magazine = Magazine::find($magazineId);
            if (!$magazine) {
                return null;
            }

            if (!function_exists('curl_init')) {
                error_log('[BROWSERLESS] cURL não disponível no servidor.');
                return null;
            }

            $dir = ROOT_PATH . '/public/uploads/magazine_pdfs';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            if (!is_dir($dir) || !is_writable($dir)) {
                error_log('[BROWSERLESS] Diretório não gravável: ' . $dir);
                return null;
            }

            $previewUrl = self::buildPreviewUrl($magazineId);
            $host = trim((string) Setting::get('browserless_host', '')) ?: self::DEFAULT_HOST;
            $endpoint = rtrim($host, '/') . '/function?token=' . urlencode($token);

            // UMA ÚNICA chamada faz TUDO: abre a revista uma vez, gera o PDF e
            // captura a imagem de cada página. Antes eram 2 chamadas (2
            // carregamentos), o que estourava o timeout. waitUntil 'load' +
            // espera curta reduzem o tempo total.
            $jsCode = <<<JS
export default async ({ page }) => {
  await page.setViewport({ width: 595, height: 842, deviceScaleFactor: 2 });
  await page.goto("{$previewUrl}", { waitUntil: "load", timeout: 45000 });
  try { await page.evaluateHandle('document.fonts.ready'); } catch (e) {}
  await new Promise(r => setTimeout(r, 2000));
  const pdf = await page.pdf({
    printBackground: true,
    width: "595px",
    height: "842px",
    margin: { top: "0", bottom: "0", left: "0", right: "0" }
  });
  const handles = await page.\$\$(".preview .page");
  const images = [];
  for (const h of handles) {
    try { images.push(await h.screenshot({ type: "png", encoding: "base64" })); }
    catch (e) {}
  }
  return {
    data: { pdf: pdf.toString("base64"), images: images },
    type: "application/json"
  };
};
JS;

            [$body, $httpCode, $contentType] = self::postToBrowserlessCode($endpoint, $jsCode);

            if ($httpCode !== 200 || $body === false) {
                error_log('[BROWSERLESS] /function falhou. code=' . $httpCode . ' body=' . substr((string) $body, 0, 300));
                return null;
            }

            $json = json_decode((string) $body, true);
            $pdfB64 = $json['data']['pdf'] ?? ($json['pdf'] ?? null);
            $images = $json['data']['images'] ?? ($json['images'] ?? []);

            if (empty($pdfB64)) {
                error_log('[BROWSERLESS] Resposta sem PDF. body=' . substr((string) $body, 0, 300));
                return null;
            }

            $pdfBin = base64_decode((string) $pdfB64, true);
            if ($pdfBin === false || substr($pdfBin, 0, 4) !== '%PDF') {
                error_log('[BROWSERLESS] PDF inválido no retorno.');
                return null;
            }

            // Salva o PDF (nome estável — sobrescreve o anterior).
            $filename = 'Revista_' . $magazineId . '.pdf';
            if (file_put_contents($dir . '/' . $filename, $pdfBin) === false) {
                error_log('[BROWSERLESS] Falha ao salvar o PDF.');
                return null;
            }

            // Salva as imagens das páginas (para o visualizador do site).
            foreach (glob($dir . '/Revista_' . $magazineId . '_p*.png') ?: [] as $old) {
                @unlink($old);
            }
            if (is_array($images)) {
                $n = 0;
                foreach ($images as $b64) {
                    $bin = base64_decode((string) $b64, true);
                    if ($bin === false || $bin === '') continue;
                    $n++;
                    file_put_contents($dir . '/Revista_' . $magazineId . '_p' . str_pad((string) $n, 2, '0', STR_PAD_LEFT) . '.png', $bin);
                }
            }

            // Uma chamada consumida no plano (PDF + imagens juntos).
            self::registerUsage();

            return '/uploads/magazine_pdfs/' . $filename;
        } catch (\Throwable $e) {
            error_log('[BROWSERLESS] Exceção: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * POST de código JS (application/javascript) ao Browserless /function.
     * Retorna [body, httpCode, contentType].
     */
    private static function postToBrowserlessCode(string $endpoint, string $jsCode): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/javascript'],
            CURLOPT_POSTFIELDS => $jsCode,
            CURLOPT_TIMEOUT => 150,
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
     * Marca o status da geração (processing|done|failed) num arquivo, para o
     * front-end acompanhar por polling (a geração roda em background).
     */
    public static function setStatus(int $magazineId, string $status): void
    {
        $dir = ROOT_PATH . '/public/uploads/magazine_pdfs';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        @file_put_contents($dir . '/Revista_' . $magazineId . '.status', $status . '|' . time());
    }

    /**
     * Lê o status atual da geração: 'processing', 'done', 'failed' ou 'none'.
     */
    public static function getStatus(int $magazineId): string
    {
        $file = ROOT_PATH . '/public/uploads/magazine_pdfs/Revista_' . $magazineId . '.status';
        if (!is_file($file)) return 'none';
        $raw = (string) @file_get_contents($file);
        $parts = explode('|', $raw);
        $status = $parts[0] ?? 'none';
        $ts = (int) ($parts[1] ?? 0);
        // Se ficou "processing" há mais de 5 min, considera travado (failed).
        if ($status === 'processing' && (time() - $ts) > 300) {
            return 'failed';
        }
        return $status ?: 'none';
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
