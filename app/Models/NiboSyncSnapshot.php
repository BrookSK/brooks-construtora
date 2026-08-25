<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Snapshots das sincronizações do Dashboard Financeiro (somente leitura).
 * Cada registro guarda o conjunto de dados lido do Nibo num instante,
 * associado a uma empresa (ex.: 'brooks', 'vetriks').
 */
class NiboSyncSnapshot extends Model
{
    protected static string $table = 'nibo_sync_snapshots';

    /**
     * Salva um novo snapshot para uma empresa. Retorna o ID.
     */
    public static function store(array $data, ?string $createdBy = null, string $company = 'brooks'): int
    {
        return self::create([
            'company' => $company,
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
     * Último snapshot salvo de uma empresa (para exibição instantânea).
     */
    public static function latest(string $company = 'brooks'): ?array
    {
        return Database::fetch(
            "SELECT * FROM nibo_sync_snapshots WHERE company = ? ORDER BY created_at DESC, id DESC LIMIT 1",
            [$company]
        );
    }

    /**
     * Histórico recente (data/hora + resumo), sem o payload pesado.
     */
    public static function history(int $limit = 20, ?string $company = null): array
    {
        $limit = (int) $limit;
        if ($company !== null) {
            return Database::fetchAll(
                "SELECT id, company, created_at, created_by, ok, totals_json
                 FROM nibo_sync_snapshots WHERE company = ? ORDER BY created_at DESC, id DESC LIMIT {$limit}",
                [$company]
            );
        }
        return Database::fetchAll(
            "SELECT id, company, created_at, created_by, ok, totals_json
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
