<?php

namespace App\Services;

use App\Core\Database;
use App\Models\PainelCategoriasDepara;

/**
 * Serviço de categorias do Nibo — somente leitura (zero writes no Nibo).
 * Mantém um cache local na tabela painel_categorias_nibo_cache com TTL configurável.
 * Sempre inclui categorias excluídas (/tree?IncludeDeletedCategory=true) para
 * que lançamentos antigos continuem exibindo o nome correto.
 */
class NiboCategoriesService
{
    private const DEFAULT_TTL_HOURS = 6;
    private const TABLE = 'painel_categorias_nibo_cache';

    // ─── Cache em memória (dentro do request) ────────────────────────
    private static array $memCache = [];

    /**
     * Retorna o mapa completo: categoryId → dados.
     * Usa cache do banco; se expirado ou forçado, re-sincroniza com o Nibo.
     *
     * @return array<string, array{nome:string, tipo:string|null, grupo_nome:string|null,
     *                             subgrupo_nome:string|null, reference_code:string|null,
     *                             excluida:bool, caminho:string}>
     */
    public static function mapa(string $company = 'brooks', bool $forcar = false): array
    {
        $cacheKey = "cat_{$company}";
        if (!$forcar && isset(self::$memCache[$cacheKey])) {
            return self::$memCache[$cacheKey];
        }

        // Verifica validade do cache no banco
        if (!$forcar && self::cacheValido($company)) {
            $rows = Database::fetchAll(
                "SELECT * FROM " . self::TABLE . " WHERE company = ?",
                [$company]
            );
            self::$memCache[$cacheKey] = self::rowsToMap($rows);
            return self::$memCache[$cacheKey];
        }

        // Re-sincroniza
        try {
            $map = self::sincronizar($company);
            self::$memCache[$cacheKey] = $map;
            return $map;
        } catch (\Throwable $e) {
            error_log('[NiboCategoriesService] Falha sync ' . $company . ': ' . $e->getMessage());
            // Retorna o que tiver no cache mesmo expirado
            $rows = Database::fetchAll("SELECT * FROM " . self::TABLE . " WHERE company = ?", [$company]);
            return self::rowsToMap($rows);
        }
    }

    /**
     * Busca uma categoria por ID. Se não estiver no cache tenta a API diretamente.
     */
    public static function porId(string $id, string $company = 'brooks'): ?array
    {
        $map = self::mapa($company);
        if (isset($map[$id])) return $map[$id];

        // Fallback: busca direta
        try {
            $token = NiboService::token($company);
            $res   = NiboService::request('GET', "/schedules/categories/{$id}", [], null, $token);
            if ($res['ok'] && is_array($res['response'])) {
                $entry = self::parseEntry($res['response']);
                self::upsertCache($company, $id, $entry);
                self::$memCache = []; // invalida memória
                return $entry;
            }
        } catch (\Throwable $e) {
            error_log('[NiboCategoriesService] porId(' . $id . '): ' . $e->getMessage());
        }
        return null;
    }

    // ─── Sincronização ────────────────────────────────────────────────

    private static function sincronizar(string $company): array
    {
        $token = NiboService::token($company);
        if ($token === '') {
            throw new \RuntimeException("Token não configurado para {$company}");
        }

        // /tree com deletadas para manter nomes de categorias excluídas
        $res = NiboService::request('GET', '/schedules/categories/tree', [
            'IncludeDeletedCategory' => 'true',
        ], null, $token);

        $entries = [];
        if ($res['ok'] && is_array($res['response'])) {
            $tree = $res['response'];
            // Pode vir em { items: [...] } ou diretamente como array
            $groups = is_array($tree) && isset($tree['items']) ? $tree['items'] : (array) $tree;
            self::walkTree($groups, $entries);
        } else {
            // Fallback: /categories paginado
            $entries = self::syncViaList($token);
        }

        // Persiste no banco
        Database::query("DELETE FROM " . self::TABLE . " WHERE company = ?", [$company]);
        foreach ($entries as $catId => $entry) {
            self::upsertCache($company, (string) $catId, $entry);
        }

        // Garante que categorias do de-para existam no cache (upsert de nome)
        self::ensureDeParaNames($entries);

        return $entries;
    }

    /** Percorre a árvore do /tree recursivamente */
    private static function walkTree(array $groups, array &$entries, string $grupoNome = '', string $grupoId = ''): void
    {
        foreach ($groups as $group) {
            $gId   = (string) ($group['id'] ?? '');
            $gNome = (string) ($group['name'] ?? '');
            $gRef  = (string) ($group['referenceCode'] ?? '');

            foreach ($group['children'] ?? [] as $child) {
                $cId       = (string) ($child['id'] ?? '');
                $cNome     = (string) ($child['name'] ?? '');
                $excluida  = !empty($child['isDeleted']);
                $isSubgroup = !empty($child['isSubgroup']);

                if ($isSubgroup) {
                    // Subgrupo: percorre filhos com contexto
                    foreach ($child['children'] ?? [] as $leaf) {
                        $lId  = (string) ($leaf['id'] ?? '');
                        $lNome = (string) ($leaf['name'] ?? '');
                        $entries[$lId] = [
                            'nome'          => $lNome,
                            'tipo'          => $leaf['type'] ?? null,
                            'grupo_id'      => $gId,
                            'grupo_nome'    => $gNome,
                            'subgrupo_id'   => $cId,
                            'subgrupo_nome' => $cNome,
                            'reference_code' => $gRef,
                            'excluida'      => !empty($leaf['isDeleted']),
                            'caminho'       => trim("{$gNome} > {$cNome} > {$lNome}", ' >'),
                        ];
                    }
                } else {
                    $entries[$cId] = [
                        'nome'          => $cNome,
                        'tipo'          => $child['type'] ?? null,
                        'grupo_id'      => $gId,
                        'grupo_nome'    => $gNome,
                        'subgrupo_id'   => null,
                        'subgrupo_nome' => null,
                        'reference_code' => $gRef,
                        'excluida'      => $excluida,
                        'caminho'       => trim("{$gNome} > {$cNome}", ' >'),
                    ];
                }
            }
        }
    }

    /** Fallback: lista paginada /v1/categories */
    private static function syncViaList(string $token): array
    {
        $entries = [];
        try {
            $items = NiboService::getAllPaged('/categories', 'name', [], $token);
            foreach ($items as $item) {
                $id = (string) ($item['id'] ?? $item['categoryId'] ?? '');
                if ($id === '') continue;
                $entries[$id] = self::parseEntry($item);
            }
        } catch (\Throwable $e) {
            error_log('[NiboCategoriesService] syncViaList: ' . $e->getMessage());
        }
        return $entries;
    }

    private static function parseEntry(array $item): array
    {
        $gNome = $item['group']['name'] ?? null;
        $gId   = $item['group']['id']   ?? null;
        $gRef  = $item['group']['referenceCode'] ?? null;
        $nome  = $item['name'] ?? $item['Name'] ?? '';
        $sub   = $item['subgroupName'] ?? null;
        $subId = $item['subgroupId']   ?? null;
        $caminho = implode(' > ', array_filter([$gNome, $sub, $nome]));
        return [
            'nome'           => $nome,
            'tipo'           => $item['type'] ?? null,
            'grupo_id'       => $gId,
            'grupo_nome'     => $gNome,
            'subgrupo_id'    => $subId,
            'subgrupo_nome'  => $sub,
            'reference_code' => $gRef,
            'excluida'       => !empty($item['isDeleted']),
            'caminho'        => $caminho ?: $nome,
        ];
    }

    // ─── Cache helpers ────────────────────────────────────────────────

    private static function cacheValido(string $company): bool
    {
        $ttl = self::DEFAULT_TTL_HOURS;
        $row = Database::fetch(
            "SELECT MAX(synced_at) AS last FROM " . self::TABLE . " WHERE company = ?",
            [$company]
        );
        if (!$row || !$row['last']) return false;
        $diff = (time() - strtotime($row['last'])) / 3600;
        return $diff < $ttl;
    }

    private static function upsertCache(string $company, string $catId, array $entry): void
    {
        Database::query(
            "INSERT INTO " . self::TABLE . " (company, categoria_id, nome, tipo, grupo_id, grupo_nome,
             subgrupo_id, subgrupo_nome, reference_code, excluida, caminho, synced_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE nome=VALUES(nome), tipo=VALUES(tipo), grupo_id=VALUES(grupo_id),
             grupo_nome=VALUES(grupo_nome), subgrupo_id=VALUES(subgrupo_id), subgrupo_nome=VALUES(subgrupo_nome),
             reference_code=VALUES(reference_code), excluida=VALUES(excluida), caminho=VALUES(caminho),
             synced_at=NOW()",
            [
                $company, $catId,
                $entry['nome'] ?? '',
                $entry['tipo'] ?? null,
                $entry['grupo_id'] ?? null,
                $entry['grupo_nome'] ?? null,
                $entry['subgrupo_id'] ?? null,
                $entry['subgrupo_nome'] ?? null,
                $entry['reference_code'] ?? null,
                ($entry['excluida'] ?? false) ? 1 : 0,
                $entry['caminho'] ?? null,
            ]
        );
    }

    private static function rowsToMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[$r['categoria_id']] = [
                'nome'           => $r['nome'],
                'tipo'           => $r['tipo'],
                'grupo_id'       => $r['grupo_id'],
                'grupo_nome'     => $r['grupo_nome'],
                'subgrupo_id'    => $r['subgrupo_id'],
                'subgrupo_nome'  => $r['subgrupo_nome'],
                'reference_code' => $r['reference_code'],
                'excluida'       => (bool) $r['excluida'],
                'caminho'        => $r['caminho'],
            ];
        }
        return $map;
    }

    /** Garante que todas as categorias do de-para têm entrada no cache (nome) */
    private static function ensureDeParaNames(array $entries): void
    {
        $depara = PainelCategoriasDepara::mapCompleto();
        foreach ($depara as $niboId => $row) {
            if (isset($entries[$niboId])) {
                // Atualiza o nome no de-para se mudou
                if ($row['categoria_nibo_nome'] !== ($entries[$niboId]['nome'] ?? '')) {
                    Database::query(
                        "UPDATE painel_categorias_depara SET categoria_nibo_nome=? WHERE categoria_nibo_id=?",
                        [$entries[$niboId]['nome'], $niboId]
                    );
                }
            }
        }
    }
}
