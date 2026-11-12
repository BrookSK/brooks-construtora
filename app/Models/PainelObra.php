<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PainelObra extends Model
{
    protected static string $table = 'painel_obras';

    public static function allAtivas(): array
    {
        return Database::fetchAll(
            "SELECT * FROM painel_obras WHERE situacao = 'Ativa' ORDER BY codigo ASC"
        );
    }

    public static function allWithFilter(?string $situacao = null, string $search = ''): array
    {
        $where = '1=1';
        $params = [];
        if ($situacao) { $where .= ' AND situacao = ?'; $params[] = $situacao; }
        if ($search)   { $where .= ' AND (nome LIKE ? OR codigo LIKE ? OR cliente LIKE ?)'; $s = "%{$search}%"; $params[] = $s; $params[] = $s; $params[] = $s; }
        return Database::fetchAll("SELECT * FROM painel_obras WHERE {$where} ORDER BY codigo ASC", $params);
    }

    /** Contratado efetivo = valor_contratado + soma dos aditivos até a data informada */
    public static function contratadoEfetivo(int $id, ?string $ate = null): float
    {
        $obra = self::find($id);
        if (!$obra) return 0.0;
        $base = (float) $obra['valor_contratado'];
        $ate  = $ate ?: date('Y-m-d');
        $soma = (float) Database::fetch(
            "SELECT COALESCE(SUM(valor),0) AS s FROM painel_obras_aditivos WHERE obra_id = ? AND data <= ?",
            [$id, $ate]
        )['s'];
        return $base + $soma;
    }

    /** Versão vigente do orçamento (maior versão com data_vigencia <= hoje) */
    public static function versaoVigente(int $id, ?string $ref = null): ?int
    {
        $ref = $ref ?: date('Y-m-d');
        $row = Database::fetch(
            "SELECT MAX(versao) AS v FROM painel_obras_orcamento WHERE obra_id = ? AND data_vigencia <= ?",
            [$id, $ref]
        );
        return ($row && $row['v'] !== null) ? (int) $row['v'] : null;
    }

    /** Orçado total da versão vigente */
    public static function orcadoTotal(int $id, ?string $ref = null): float
    {
        $v = self::versaoVigente($id, $ref);
        if ($v === null) return 0.0;
        $row = Database::fetch(
            "SELECT COALESCE(SUM(valor_orcado),0) AS s FROM painel_obras_orcamento WHERE obra_id = ? AND versao = ?",
            [$id, $v]
        );
        return (float) ($row['s'] ?? 0);
    }

    /** Orçado por categoria da versão vigente */
    public static function orcadoPorCategoria(int $id, ?string $ref = null): array
    {
        $v = self::versaoVigente($id, $ref);
        if ($v === null) return [];
        return Database::fetchAll(
            "SELECT categoria_obra, valor_orcado FROM painel_obras_orcamento WHERE obra_id = ? AND versao = ? ORDER BY categoria_obra",
            [$id, $v]
        );
    }

    /** Histórico de versões do orçamento */
    public static function historicoOrcamento(int $id): array
    {
        return Database::fetchAll(
            "SELECT versao, data_vigencia, motivo_revisao, created_by, created_at,
                    (SELECT COALESCE(SUM(valor_orcado),0) FROM painel_obras_orcamento sub WHERE sub.obra_id = ? AND sub.versao = o.versao) AS total
             FROM painel_obras_orcamento o
             WHERE o.obra_id = ?
             GROUP BY versao, data_vigencia, motivo_revisao, created_by, created_at
             ORDER BY versao DESC",
            [$id, $id]
        );
    }
}
