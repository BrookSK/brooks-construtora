<?php

namespace App\Services;

class LogService
{
    private static string $logDir = '';

    /**
     * Inicializa o diretório de logs
     */
    private static function init(): void
    {
        if (empty(self::$logDir)) {
            self::$logDir = ROOT_PATH . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }
        }
    }

    /**
     * Escreve uma entrada no log
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        self::init();

        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $file = self::$logDir . "/app-{$date}.log";

        $entry = "[{$time}] [{$level}] {$message}";
        if (!empty($context)) {
            $entry .= " | " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $entry .= "\n";

        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log de informação
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /**
     * Log de erro
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Log de debug
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    /**
     * Log de warning
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * Log de request HTTP (para acompanhar navegação)
     */
    public static function request(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user = $_SESSION['user_name'] ?? 'guest';
        $role = $_SESSION['user_role'] ?? 'none';

        self::write('REQUEST', "{$method} {$uri}", [
            'ip' => $ip,
            'user' => $user,
            'role' => $role,
        ]);
    }

    /**
     * Log de queries SQL (para debug)
     */
    public static function query(string $sql, array $params = []): void
    {
        self::write('SQL', $sql, ['params' => $params]);
    }

    /**
     * Log de ação do usuário
     */
    public static function action(string $action, array $data = []): void
    {
        $user = $_SESSION['user_name'] ?? 'guest';
        self::write('ACTION', "[{$user}] {$action}", $data);
    }

    /**
     * Lê as últimas N linhas do log do dia
     */
    public static function tail(int $lines = 50, ?string $date = null): string
    {
        self::init();

        $date = $date ?: date('Y-m-d');
        $file = self::$logDir . "/app-{$date}.log";

        if (!file_exists($file)) {
            return "Nenhum log encontrado para {$date}";
        }

        $allLines = file($file, FILE_IGNORE_NEW_LINES);
        $totalLines = count($allLines);
        $start = max(0, $totalLines - $lines);

        return implode("\n", array_slice($allLines, $start));
    }

    /**
     * Lista arquivos de log disponíveis
     */
    public static function listFiles(): array
    {
        self::init();

        $files = glob(self::$logDir . '/app-*.log');
        $result = [];
        foreach ($files as $f) {
            $result[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'date' => str_replace(['app-', '.log'], '', basename($f)),
            ];
        }
        rsort($result);
        return $result;
    }
}
