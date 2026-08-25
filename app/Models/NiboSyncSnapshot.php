<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Snapshots das sincronizações do Dashboard Financeiro (somente leitura).
 * Cada registro guarda o conjunto de dados lido do Nibo num instante.
 */
class NiboSyncSnapshot extends Model
{
    protected static string $table = 'nibo_sync_snapshots';

    /**
     * Salva um novo snapshot. Retorna o ID.
     */
    public static function store(array $data, ?string $createdBy = null): int
    {
        return self::create([
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $createdBy,
            'ok' => !empty($data['ok']) ? 1 : 0,
            'totals_json' => json_encode($data['totals'] ?? [], JSON_UNESCAPED_UNICODE),
            'payload_json' => json_encode([
                'generated_at' => $data['generated_at'] ?? date('Y-m-d H:i:s'),
                'masters' => $data['masters'] ?? [],
                'filters' => $data['filters'] ?? [],
                'accounts' => $data['accounts'] ?? [],
                'payables' => $data['payables'] ?? [],
                'receivables' => $data['receivables'] ?? [],
                'totals' => $data['totals'] ?? [],
                'errors' => $data['errors'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Último snapshot salvo (para exibição instantânea).
     */
    public static function latest(): ?array
    {
        return Database::fetch(
            "SELECT * FROM nibo_sync_snapshots ORDER BY created_at DESC, id DESC LIMIT 1"
        );
    }

    /**
     * Histórico recente (data/hora + resumo), sem o payload pesado.
     */
    public static function history(int $limit = 20): array
    {
        $limit = (int) $limit;
        return Database::fetchAll(
            "SELECT id, created_at, created_by, ok, totals_json
             FROM nibo_sync_snapshots ORDER BY created_at DESC, id DESC LIMIT {$limit}"
        );
    }

    /**
     * Decodifica o payload de um snapshot.
     */
    public static function decodePayload(array $row): array
    {
        $p = json_decode($row['payload_json'] ?? '', true);
        return is_array($p) ? $p : [];
    }
}
