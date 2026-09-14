<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Item de uma lista de materiais pré-definida.
 *
 * Guarda um snapshot dos dados do material (nome, especificação,
 * classificação, unidade) além do vínculo opcional ao cadastro
 * (material_id) e da quantidade padrão pré-selecionada.
 */
class MaterialTemplateItem extends Model
{
    protected static string $table = 'material_template_items';

    /**
     * Itens ativos de uma lista, com dados atualizados do material
     * cadastrado quando ainda houver vínculo (material_id).
     */
    public static function forTemplate(int $templateId, bool $onlyActive = true): array
    {
        $where = $onlyActive ? 'AND i.active = 1' : '';
        return Database::fetchAll(
            "SELECT i.*,
                    m.active AS material_active,
                    mc.name AS category_name,
                    mu.abbreviation AS unit_abbr,
                    mu.name AS unit_name
             FROM material_template_items i
             LEFT JOIN materials m ON i.material_id = m.id
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE i.template_id = ? {$where}
             ORDER BY i.sort_order ASC, i.material_name ASC",
            [$templateId]
        );
    }

    /**
     * Remove (hard delete) todos os itens de uma lista.
     */
    public static function deleteByTemplate(int $templateId): int
    {
        return Database::delete('material_template_items', 'template_id = ?', [$templateId]);
    }
}
