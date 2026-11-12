<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PainelCategoriasDepara extends Model
{
    protected static string $table = 'painel_categorias_depara';

    public static function mapCompleto(): array
    {
        $rows = Database::fetchAll("SELECT * FROM painel_categorias_depara ORDER BY categoria_nibo_nome ASC");
        $map = [];
        foreach ($rows as $r) {
            $map[$r['categoria_nibo_id']] = $r;
        }
        return $map;
    }

    /** Categorias ainda não mapeadas (categoria_obra IS NULL e ignorar = 0) */
    public static function naoMapeadas(): array
    {
        return Database::fetchAll(
            "SELECT * FROM painel_categorias_depara WHERE categoria_obra IS NULL AND ignorar_no_painel = 0 ORDER BY categoria_nibo_nome"
        );
    }

    public static function upsert(string $niboId, string $niboNome, ?string $catObra, bool $ignorar, int $userId): void
    {
        $exists = Database::fetch("SELECT id FROM painel_categorias_depara WHERE categoria_nibo_id = ?", [$niboId]);
        if ($exists) {
            Database::query(
                "UPDATE painel_categorias_depara SET categoria_nibo_nome=?, categoria_obra=?, ignorar_no_painel=?, updated_at=NOW() WHERE categoria_nibo_id=?",
                [$niboNome, $catObra, $ignorar ? 1 : 0, $niboId]
            );
        } else {
            Database::query(
                "INSERT INTO painel_categorias_depara (categoria_nibo_id, categoria_nibo_nome, categoria_obra, ignorar_no_painel, created_by) VALUES (?,?,?,?,?)",
                [$niboId, $niboNome, $catObra, $ignorar ? 1 : 0, $userId]
            );
        }
    }

    /**
     * Busca de-para por ID ou nome da categoria Nibo
     */
    public static function findByNiboCategory(?string $catNiboId, string $catNiboNome): ?array
    {
        // Tenta por ID primeiro
        if ($catNiboId) {
            $row = Database::fetch("SELECT * FROM painel_categorias_depara WHERE categoria_nibo_id = ?", [$catNiboId]);
            if ($row) {
                return $row;
            }
        }
        
        // Tenta por nome
        $row = Database::fetch("SELECT * FROM painel_categorias_depara WHERE categoria_nibo_nome = ?", [$catNiboNome]);
        
        return $row ?: null;
    }
}
