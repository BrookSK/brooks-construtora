<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ContractAiLog extends Model
{
    protected static string $table = 'contract_ai_logs';

    private const MAX_FIELD = 60000; // trava tamanho dos campos LONGTEXT

    /**
     * Registra uma chamada à IA. Nunca lança exceção — diagnóstico não pode
     * derrubar o fluxo principal.
     */
    public static function record(array $data): int
    {
        try {
            foreach (['request_payload', 'response_body'] as $k) {
                if (isset($data[$k]) && strlen((string)$data[$k]) > self::MAX_FIELD) {
                    $data[$k] = substr((string)$data[$k], 0, self::MAX_FIELD) . "\n…[truncado]";
                }
            }
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
            return self::create($data);
        } catch (\Throwable $e) {
            error_log('[ContractAiLog] ' . $e->getMessage());
            return 0;
        }
    }

    public static function recent(int $limit = 100, string $filter = ''): array
    {
        $where = '1=1';
        $params = [];
        if ($filter === 'errors') {
            $where = 'success = 0';
        } elseif (in_array($filter, ['extract', 'generate', 'regenerate'], true)) {
            $where = 'operation = ?';
            $params[] = $filter;
        }
        $params[] = $limit;
        return Database::fetchAll(
            "SELECT l.*, u.name AS user_name
             FROM contract_ai_logs l
             LEFT JOIN users u ON l.created_by = u.id
             WHERE {$where}
             ORDER BY l.id DESC
             LIMIT ?",
            $params
        );
    }

    public static function stats(): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(success = 1) AS ok,
                SUM(success = 0) AS fail,
                AVG(duration_ms) AS avg_ms,
                MAX(created_at) AS last_at
             FROM contract_ai_logs"
        );
        return $row ?: ['total' => 0, 'ok' => 0, 'fail' => 0, 'avg_ms' => 0, 'last_at' => null];
    }

    public static function clear(): void
    {
        Database::query("DELETE FROM contract_ai_logs");
    }
}
