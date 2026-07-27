<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class SupplierContact extends Model
{
    protected static string $table = 'supplier_contacts';

    /**
     * Lista contatos (vendedores) de um fornecedor
     */
    public static function getBySupplier(int $supplierId): array
    {
        return Database::fetchAll(
            "SELECT * FROM supplier_contacts WHERE supplier_id = ? AND active = 1 ORDER BY name ASC",
            [$supplierId]
        );
    }

    /**
     * Lista todos os contatos ativos com nome do fornecedor
     */
    public static function allWithSupplier(): array
    {
        return Database::fetchAll(
            "SELECT sc.*, s.name as supplier_name
             FROM supplier_contacts sc
             JOIN suppliers s ON sc.supplier_id = s.id
             WHERE sc.active = 1
             ORDER BY s.name ASC, sc.name ASC"
        );
    }

    /**
     * Importar vendedores de cotações anteriores
     * (reutiliza dados do purchase_order_suppliers)
     */
    public static function importFromQuotes(): int
    {
        $imported = 0;

        $vendors = Database::fetchAll(
            "SELECT DISTINCT pos.supplier_id, pos.vendor_name, pos.vendor_phone, pos.vendor_email
             FROM purchase_order_suppliers pos
             WHERE pos.vendor_name IS NOT NULL AND pos.vendor_name != ''
             ORDER BY pos.supplier_id, pos.vendor_name"
        );

        foreach ($vendors as $vendor) {
            // Verificar se já existe
            $existing = Database::fetch(
                "SELECT id FROM supplier_contacts 
                 WHERE supplier_id = ? AND (name = ? OR phone = ?)",
                [$vendor['supplier_id'], $vendor['vendor_name'], $vendor['vendor_phone']]
            );

            if (!$existing) {
                self::create([
                    'supplier_id' => $vendor['supplier_id'],
                    'name' => $vendor['vendor_name'],
                    'phone' => $vendor['vendor_phone'] ?? '',
                    'email' => $vendor['vendor_email'] ?? '',
                    'role' => 'vendedor',
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Busca por nome ou telefone
     */
    public static function search(string $term, ?int $supplierId = null): array
    {
        $where = "(sc.name LIKE ? OR sc.phone LIKE ? OR sc.email LIKE ?)";
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];

        if ($supplierId) {
            $where .= " AND sc.supplier_id = ?";
            $params[] = $supplierId;
        }

        return Database::fetchAll(
            "SELECT sc.*, s.name as supplier_name
             FROM supplier_contacts sc
             JOIN suppliers s ON sc.supplier_id = s.id
             WHERE sc.active = 1 AND {$where}
             ORDER BY sc.name ASC",
            $params
        );
    }
}
