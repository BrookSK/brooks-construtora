<?php

namespace App\Services;

/**
 * Logger detalhado para cotações de pedidos de compra.
 * Registra dados do frontend e backend em arquivo JSON para diagnóstico.
 * Cada cotação gera um arquivo em /storage/logs/quotes/
 */
class QuoteLogger
{
    private static string $logDir = '';

    private static function getLogDir(): string
    {
        if (empty(self::$logDir)) {
            self::$logDir = ROOT_PATH . '/storage/logs/quotes';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    /**
     * Gera o caminho do arquivo de log para uma cotação específica
     */
    private static function getLogPath(int $orderId, string $token): string
    {
        $date = date('Y-m-d_H-i-s');
        return self::getLogDir() . "/quote_{$orderId}_{$date}_{$token}.json";
    }

    /**
     * Registra log do frontend (recebido via AJAX antes do submit)
     * Salva num arquivo temporário que será mesclado com o log do backend
     */
    public static function logFrontend(string $token, array $data): string
    {
        $logId = uniqid('qlog_', true);
        $logData = [
            'log_id' => $logId,
            'timestamp' => date('Y-m-d H:i:s'),
            'phase' => 'frontend',
            'token' => $token,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'data' => $data,
        ];

        $tempPath = self::getLogDir() . "/temp_{$token}_{$logId}.json";
        file_put_contents($tempPath, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $logId;
    }

    /**
     * Registra log completo do backend (submitQuote)
     * Mescla com o log do frontend se existir
     */
    public static function logBackend(int $orderId, string $token, array $backendData): void
    {
        // Buscar log temporário do frontend
        $frontendData = null;
        $pattern = self::getLogDir() . "/temp_{$token}_*.json";
        $tempFiles = glob($pattern);

        if (!empty($tempFiles)) {
            // Pegar o mais recente
            usort($tempFiles, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $tempContent = file_get_contents($tempFiles[0]);
            $frontendData = json_decode($tempContent, true);

            // Limpar arquivos temporários deste token
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        $logData = [
            'order_id' => $orderId,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'frontend' => $frontendData,
            'backend' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $backendData,
            ],
        ];

        $logPath = self::getLogPath($orderId, substr($token, 0, 8));
        file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Lista todos os logs disponíveis (mais recentes primeiro)
     */
    public static function listLogs(int $limit = 50, int $offset = 0): array
    {
        $dir = self::getLogDir();
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/quote_*.json');
        if (empty($files)) return [];

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $total = count($files);
        $files = array_slice($files, $offset, $limit);

        $logs = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                $data['_filename'] = basename($file);
                $data['_filesize'] = filesize($file);
                $logs[] = $data;
            }
        }

        return ['logs' => $logs, 'total' => $total];
    }

    /**
     * Busca log de um pedido específico
     */
    public static function getLogByOrder(int $orderId): array
    {
        $dir = self::getLogDir();
        if (!is_dir($dir)) return [];

        $files = glob($dir . "/quote_{$orderId}_*.json");
        if (empty($files)) return [];

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $logs = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                $data['_filename'] = basename($file);
                $logs[] = $data;
            }
        }

        return $logs;
    }

    /**
     * Lê um log específico pelo nome do arquivo
     */
    public static function getLog(string $filename): ?array
    {
        // Sanitizar filename para prevenir directory traversal
        $filename = basename($filename);
        $path = self::getLogDir() . '/' . $filename;

        if (!file_exists($path)) return null;

        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /**
     * Limpa logs antigos (mais de X dias)
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $dir = self::getLogDir();
        if (!is_dir($dir)) return 0;

        $files = glob($dir . '/quote_*.json');
        $cutoff = time() - ($daysToKeep * 86400);
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
