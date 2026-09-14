<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Lista de materiais pré-definida (template).
 *
 * Uma lista agrupa materiais reutilizáveis (ex: "Início de Obra",
 * "Elétrica", "Hidráulica") que podem ser aplicados de uma vez na
 * criação de um pedido, com materiais, especificações e quantidades
 * padrão já selecionadas.
 */
class MaterialTemplate extends Model
{
    protected static string $table = 'material_templates';

    /**
     * Listas ativas com a contagem de itens ativos.
     */
    public static function allActive(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM material_template_items i
                     WHERE i.template_id = t.id AND i.active = 1) AS item_count
             FROM material_templates t
             WHERE t.active = 1
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Todas as listas (ativas e inativas) com contagem de itens.
     */
    public static function allWithCount(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM material_template_items i
                     WHERE i.template_id = t.id AND i.active = 1) AS item_count
             FROM material_templates t
             ORDER BY {$orderBy}"
        );
    }
}
