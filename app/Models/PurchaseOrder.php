<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrder extends Model
{
    protected static string $table = 'purchase_orders';

    /**
     * Verifica se a tabela construction_sites e a coluna construction_site_id existem
     */
    private static ?bool $hasConstructionSites = null;

    private static function hasConstructionSites(): bool
    {
        if (self::$hasConstructionSites === null) {
            try {
                $result = Database::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1");
                self::$hasConstructionSites = !empty($result);
            } catch (\Exception $e) {
                self::$hasConstructionSites = false;
            }
        }
        return self::$hasConstructionSites;
    }

    /**
     * Gera o próximo código de pedido (PED-000001)
     */
    public static function generateCode(): string
    {
        $last = Database::fetch("SELECT code FROM purchase_orders ORDER BY id DESC LIMIT 1");
        if ($last) {
            $number = (int) substr($last['code'], 4) + 1;
        } else {
            $number = 1;
        }
        return 'PED-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Gera um token seguro
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Busca pedido por token de cotação (com dados da obra)
     */
    public static function findByQuoteToken(string $token): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, cs.name as construction_site_name, cs.code as construction_site_code, 
                        cs.address as construction_site_address, cs.city as construction_site_city, 
                        cs.state as construction_site_state, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.quote_token = ?",
                [$token]
            );
        }
        return Database::fetch("SELECT * FROM purchase_orders WHERE quote_token = ?", [$token]);
    }

    /**
     * Busca pedido por token de aprovação (com dados da obra)
     */
    public static function findByApprovalToken(string $token): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, cs.name as construction_site_name, cs.code as construction_site_code,
                        cs.address as construction_site_address, cs.city as construction_site_city,
                        cs.state as construction_site_state, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.approval_token = ?",
                [$token]
            );
        }
        return Database::fetch("SELECT * FROM purchase_orders WHERE approval_token = ?", [$token]);
    }

    /**
     * Lista todos com fornecedor e obra
     */
    public static function allWithSupplier(string $orderBy = 'po.created_at DESC'): array
    {
        if (self::hasConstructionSites()) {
            return Database::fetchAll(
                "SELECT po.*, s.name as supplier_name,
                 cs.name as construction_site_name, cs.code as construction_site_code,
                 COALESCE(
                    (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                    NULLIF(po.total_estimated, 0),
                    (SELECT SUM(poi2.total_price) FROM purchase_order_items poi2 WHERE poi2.order_id = po.id AND poi2.source_type IS NOT NULL AND poi2.source_type != 'purchase' AND poi2.total_price > 0),
                    0
                 ) as display_total,
                 (SELECT COALESCE(SUM(pop.amount), 0) FROM purchase_order_payments pop WHERE pop.order_id = po.id) as nf_total,
                 (SELECT GROUP_CONCAT(poi.material_name SEPARATOR ' | ') FROM purchase_order_items poi WHERE poi.order_id = po.id) as items_names
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 ORDER BY {$orderBy}"
            );
        }
        return Database::fetchAll(
            "SELECT po.*, s.name as supplier_name,
             COALESCE(
                (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                NULLIF(po.total_estimated, 0),
                (SELECT SUM(poi2.total_price) FROM purchase_order_items poi2 WHERE poi2.order_id = po.id AND poi2.source_type IS NOT NULL AND poi2.source_type != 'purchase' AND poi2.total_price > 0),
                0
             ) as display_total,
             (SELECT COALESCE(SUM(pop.amount), 0) FROM purchase_order_payments pop WHERE pop.order_id = po.id) as nf_total,
             (SELECT GROUP_CONCAT(poi.material_name SEPARATOR ' | ') FROM purchase_order_items poi WHERE poi.order_id = po.id) as items_names
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Busca pedido completo com fornecedor e obra
     */
    public static function findFull(int $id): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone, s.cnpj as supplier_cnpj,
                        cs.name as construction_site_name, cs.code as construction_site_code, cs.address as construction_site_address,
                        cs.city as construction_site_city, cs.state as construction_site_state,
                        cs.responsible_name as construction_site_responsible, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.id = ?",
                [$id]
            );
        }
        return Database::fetch(
            "SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone, s.cnpj as supplier_cnpj
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             WHERE po.id = ?",
            [$id]
        );
    }

    /**
     * Conta pedidos por status
     */
    public static function countByStatus(string $status): int
    {
        return self::count("status = ?", [$status]);
    }

    /**
     * Verifica se a coluna informada existe na tabela purchase_orders.
     * Mantém compatibilidade com bancos que ainda não rodaram a migration
     * de integração (origin / weekly_request_id).
     */
    private static array $columnCache = [];

    public static function hasColumn(string $column): bool
    {
        if (!array_key_exists($column, self::$columnCache)) {
            try {
                $result = Database::fetch(
                    "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = ? LIMIT 1",
                    [$column]
                );
                self::$columnCache[$column] = !empty($result);
            } catch (\Exception $e) {
                self::$columnCache[$column] = false;
            }
        }
        return self::$columnCache[$column];
    }

    /**
     * Busca um pedido já vinculado a uma solicitação semanal (idempotência).
     */
    public static function findByWeeklyRequest(int $weeklyRequestId): ?array
    {
        if (!self::hasColumn('weekly_request_id')) return null;
        return Database::fetch(
            "SELECT * FROM purchase_orders WHERE weekly_request_id = ? ORDER BY id DESC LIMIT 1",
            [$weeklyRequestId]
        );
    }

    /**
     * PONTO ÚNICO DE CRIAÇÃO DE PEDIDO (fonte única da verdade).
     *
     * Cria um pedido de material real no sistema existente + seus itens,
     * seguindo o mesmo fluxo de cotação. É usado tanto pelo fluxo manual
     * (Novo Pedido) quanto pela Lista Semanal, evitando sistema paralelo.
     *
     * @param array $data  order_type, description, urgency, deadline,
     *                     construction_site_id, created_by, created_by_name,
     *                     origin, weekly_request_id
     * @param array $items material_name, specification, classification, unit,
     *                     quantity, material_id
     * @return array{id:int, code:string, quote_token:string, approval_token:string}
     */
    public static function createWithItems(array $data, array $items): array
    {
        $code = self::generateCode();
        $quoteToken = self::generateToken();
        $approvalToken = self::generateToken();

        $orderType = $data['order_type'] ?? 'material';
        if (!in_array($orderType, ['material', 'service'])) $orderType = 'material';

        $urgency = $data['urgency'] ?? 'medium';
        if (!in_array($urgency, ['low', 'medium', 'high', 'critical'])) $urgency = 'medium';

        $orderData = [
            'code' => $code,
            'order_type' => $orderType,
            'supplier_id' => null,
            'status' => 'pending_quote',
            'description' => $data['description'] ?? '',
            'urgency' => $urgency,
            'deadline' => !empty($data['deadline']) ? $data['deadline'] : null,
            'created_by' => $data['created_by'] ?? null,
            'created_by_name' => $data['created_by_name'] ?? 'Sistema',
            'quote_token' => $quoteToken,
            'approval_token' => $approvalToken,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Origem do pedido (manual | weekly_list) — só se a coluna existir
        if (self::hasColumn('origin')) {
            $orderData['origin'] = $data['origin'] ?? 'manual';
        }
        if (self::hasColumn('weekly_request_id') && !empty($data['weekly_request_id'])) {
            $orderData['weekly_request_id'] = (int) $data['weekly_request_id'];
        }

        // Obra (se a coluna existir e informada)
        if (!empty($data['construction_site_id']) && self::hasColumn('construction_site_id')) {
            $orderData['construction_site_id'] = (int) $data['construction_site_id'];
        }

        $orderId = self::create($orderData);

        // Itens do pedido (fluxo padrão de cotação: source_type = null)
        foreach ($items as $item) {
            if (empty(trim($item['material_name'] ?? ''))) continue;
            \App\Models\PurchaseOrderItem::create([
                'order_id' => $orderId,
                'material_id' => !empty($item['material_id']) ? (int) $item['material_id'] : null,
                'material_name' => trim($item['material_name']),
                'specification' => $item['specification'] ?? '',
                'classification' => $item['classification'] ?? '',
                'unit' => $item['unit'] ?? '',
                'quantity' => (float) ($item['quantity'] ?? 1),
                'source_type' => null,
                'stock_from_site_id' => null,
                'stock_movement_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'id' => $orderId,
            'code' => $code,
            'quote_token' => $quoteToken,
            'approval_token' => $approvalToken,
        ];
    }
}
