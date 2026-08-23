<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class WeeklyMaterialRequest extends Model
{
    protected static string $table = 'weekly_material_requests';

    /**
     * Gerar token único
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Buscar por token
     */
    public static function findByToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT wmr.*, pu.name as manager_name, pu.phone as manager_phone, pu.email as manager_email
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             WHERE wmr.token = ?",
            [$token]
        );
    }

    /**
     * Buscar registros de uma semana específica (com dados da obra e do pedido gerado)
     */
    public static function getByWeek(string $weekStart): array
    {
        return Database::fetchAll(
            "SELECT wmr.*, pu.name as manager_name, pu.phone as manager_phone, pu.email as manager_email,
                    cs.name as construction_site_name, cs.code as construction_site_code,
                    po.code as order_code, po.status as order_status, po.created_at as order_created_at
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             LEFT JOIN construction_sites cs ON wmr.construction_site_id = cs.id
             LEFT JOIN purchase_orders po ON wmr.order_id = po.id
             WHERE wmr.week_start = ?
             ORDER BY pu.name ASC",
            [$weekStart]
        );
    }

    /**
     * Buscar todas as semanas disponíveis (para listagem)
     */
    public static function getWeeks(): array
    {
        return Database::fetchAll(
            "SELECT week_start, 
                    COUNT(*) as total_managers,
                    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                    SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) as orders_count,
                    COALESCE(SUM(items_count), 0) as items_total
             FROM weekly_material_requests
             GROUP BY week_start
             ORDER BY week_start DESC"
        );
    }

    /**
     * Criar registros da semana para todos os gerentes ativos (pin_users com is_weekly_manager=1)
     */
    public static function createWeekRecords(string $weekStart): int
    {
        $managers = Database::fetchAll(
            "SELECT id, name, phone, email FROM pin_users WHERE active = 1 AND is_weekly_manager = 1 ORDER BY name ASC"
        );
        $created = 0;

        foreach ($managers as $manager) {
            // Verificar se já existe (usando manager_id = pin_user.id)
            $existing = Database::fetch(
                "SELECT id FROM weekly_material_requests WHERE manager_id = ? AND week_start = ?",
                [$manager['id'], $weekStart]
            );

            if (!$existing) {
                // Pré-seleciona a obra do responsável quando houver apenas uma vinculada
                $siteId = self::defaultSiteForManager((int) $manager['id']);
                $reqId = self::create([
                    'manager_id' => $manager['id'],
                    'construction_site_id' => $siteId,
                    'week_start' => $weekStart,
                    'token' => self::generateToken(),
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                \App\Models\WeeklyMaterialLog::record(
                    \App\Models\WeeklyMaterialLog::ACTION_LINK_GENERATED,
                    $reqId,
                    "Solicitação e link gerados para {$manager['name']}",
                    $weekStart
                );
                $created++;
            }
        }

        \App\Models\WeeklyMaterialLog::record(
            \App\Models\WeeklyMaterialLog::ACTION_WEEK_CREATED,
            null,
            "Semana gerada: {$created} solicitação(ões) criada(s)",
            $weekStart
        );

        return $created;
    }

    /**
     * Retorna a obra padrão de um responsável se ele estiver vinculado a
     * exatamente uma obra (via construction_site_approvers). Caso contrário null.
     */
    public static function defaultSiteForManager(int $managerId): ?int
    {
        try {
            $rows = Database::fetchAll(
                "SELECT DISTINCT construction_site_id FROM construction_site_approvers WHERE pin_user_id = ?",
                [$managerId]
            );
            if (count($rows) === 1) {
                return (int) $rows[0]['construction_site_id'];
            }
        } catch (\Exception $e) {}
        return null;
    }

    /**
     * Obras vinculadas a um responsável (para o seletor do formulário).
     */
    public static function sitesForManager(int $managerId): array
    {
        try {
            return Database::fetchAll(
                "SELECT cs.id, cs.name, cs.code
                 FROM construction_site_approvers csa
                 JOIN construction_sites cs ON csa.construction_site_id = cs.id
                 WHERE csa.pin_user_id = ? AND cs.status = 'active'
                 GROUP BY cs.id, cs.name, cs.code
                 ORDER BY cs.name ASC",
                [$managerId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Marcar como preenchido.
     *
     * REGRA CRÍTICA (PARTE 20): só marcar como 'filled' quando o backend
     * confirmar que o Pedido foi criado e existe um order_id válido.
     * Por isso este método exige o $orderId.
     */
    public static function markFilled(int $id, int $orderId, ?string $notes = null, ?string $audioFilename = null): void
    {
        $data = [
            'status' => 'filled',
            'order_id' => $orderId,
            'filled_at' => date('Y-m-d H:i:s'),
        ];
        if ($notes !== null) $data['notes'] = $notes;
        if ($audioFilename !== null) $data['audio_filename'] = $audioFilename;

        self::updateById($id, $data);
    }

    /**
     * Registra a abertura do formulário (primeira visualização).
     */
    public static function markOpened(int $id): void
    {
        $req = self::find($id);
        if ($req && empty($req['opened_at'])) {
            self::updateById($id, ['opened_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Calcula a antecedência (em dias) entre a data da solicitação e a data
     * em que o material é necessário (REGRA DOS 15 DIAS - PARTE 8).
     */
    public static function calcAntecedence(?string $neededDate, ?string $requestedAt = null): ?int
    {
        if (empty($neededDate)) return null;
        $base = $requestedAt ? strtotime($requestedAt) : time();
        $base = strtotime(date('Y-m-d', $base));
        $need = strtotime(date('Y-m-d', strtotime($neededDate)));
        if ($need === false || $base === false) return null;
        return (int) floor(($need - $base) / 86400);
    }

    /**
     * A solicitação está dentro do prazo recomendado (>= 15 dias)?
     */
    public static function isWithinLeadTime(?int $antecedence): bool
    {
        return $antecedence !== null && $antecedence >= 15;
    }

    /**
     * Marcar pendentes antigos como overdue
     */
    public static function markOverdue(string $weekStart): int
    {
        return Database::update(
            self::$table,
            ['status' => 'overdue'],
            "week_start = ? AND status = 'pending'",
            [$weekStart]
        );
    }

    /**
     * Marca como 'overdue' (Atrasado) todas as solicitações pendentes de
     * semanas anteriores à semana informada, cujo link já havia sido enviado.
     * (PARTE 20 — atraso automático)
     */
    public static function markOverduePastWeeks(string $weekStart): int
    {
        return Database::update(
            self::$table,
            ['status' => 'overdue'],
            "week_start < ? AND status = 'pending' AND notified_at IS NOT NULL",
            [$weekStart]
        );
    }

    /**
     * Calcular segunda-feira da semana atual
     */
    public static function currentWeekStart(): string
    {
        $today = new \DateTime();
        $dayOfWeek = (int) $today->format('N'); // 1=Seg, 7=Dom
        $monday = clone $today;
        $monday->modify('-' . ($dayOfWeek - 1) . ' days');
        return $monday->format('Y-m-d');
    }

    /**
     * Calcular segunda-feira da próxima semana
     */
    public static function nextWeekStart(): string
    {
        $today = new \DateTime();
        $dayOfWeek = (int) $today->format('N');
        $nextMonday = clone $today;
        $nextMonday->modify('+' . (8 - $dayOfWeek) . ' days');
        return $nextMonday->format('Y-m-d');
    }

    /**
     * Itens de uma lista
     */
    public static function getItems(int $requestId): array
    {
        return Database::fetchAll(
            "SELECT * FROM weekly_material_request_items WHERE request_id = ? ORDER BY id ASC",
            [$requestId]
        );
    }

    /**
     * Salvar itens de uma lista
     */
    public static function saveItems(int $requestId, array $items): void
    {
        // Limpar itens existentes
        Database::delete('weekly_material_request_items', 'request_id = ?', [$requestId]);

        // Inserir novos
        $count = 0;
        foreach ($items as $item) {
            if (empty(trim($item['material_name'] ?? ''))) continue;
            Database::insert('weekly_material_request_items', [
                'request_id' => $requestId,
                'material_name' => trim($item['material_name']),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit' => trim($item['unit'] ?? ''),
                'notes' => trim($item['notes'] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        // Atualizar contagem de itens (controle gerencial)
        self::updateById($requestId, ['items_count' => $count]);
    }

    // ─── Dashboard gerencial ──────────────────────────────────────────────

    /**
     * Totais consolidados de uma semana para os cards do dashboard.
     */
    public static function getWeekStats(string $weekStart): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN notified_at IS NOT NULL THEN 1 ELSE 0 END) as links_sent,
                SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                COALESCE(SUM(items_count), 0) as items_total,
                SUM(CASE WHEN urgency IN ('high','critical') AND status = 'filled' THEN 1 ELSE 0 END) as critical_count
             FROM weekly_material_requests
             WHERE week_start = ?",
            [$weekStart]
        );
        return $row ?: [
            'total' => 0, 'links_sent' => 0, 'filled' => 0, 'pending' => 0,
            'overdue' => 0, 'items_total' => 0, 'critical_count' => 0,
        ];
    }

    /**
     * Controle por responsável (PARTE 23): lista com status, último pedido,
     * próxima necessidade e taxa dos últimos ciclos.
     */
    public static function getManagerControl(string $weekStart): array
    {
        return Database::fetchAll(
            "SELECT wmr.*, pu.name as manager_name, pu.phone as manager_phone, pu.email as manager_email,
                    cs.name as construction_site_name, cs.code as construction_site_code,
                    po.code as order_code, po.status as order_status, po.id as po_id,
                    po.created_at as order_created_at
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             LEFT JOIN construction_sites cs ON wmr.construction_site_id = cs.id
             LEFT JOIN purchase_orders po ON wmr.order_id = po.id
             WHERE wmr.week_start = ?
             ORDER BY pu.name ASC",
            [$weekStart]
        );
    }

    /**
     * Últimos ciclos (semanas) de um responsável, para mini-histórico.
     */
    public static function getRecentCyclesForManager(int $managerId, int $limit = 4): array
    {
        $limit = (int) $limit;
        return Database::fetchAll(
            "SELECT week_start, status FROM weekly_material_requests
             WHERE manager_id = ?
             ORDER BY week_start DESC
             LIMIT {$limit}",
            [$managerId]
        );
    }

    /**
     * Detalhe agregado de um responsável (PARTE 25).
     */
    public static function getManagerSummary(int $managerId): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as total_responses,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as total_overdue,
                COALESCE(SUM(items_count), 0) as total_items
             FROM weekly_material_requests
             WHERE manager_id = ? AND notified_at IS NOT NULL",
            [$managerId]
        );
        return $row ?: ['total_sent' => 0, 'total_responses' => 0, 'total_overdue' => 0, 'total_items' => 0];
    }

    /**
     * Semanas/pedidos de um responsável (histórico detalhado).
     */
    public static function getManagerRequests(int $managerId, int $limit = 20): array
    {
        $limit = (int) $limit;
        return Database::fetchAll(
            "SELECT wmr.*, cs.name as construction_site_name,
                    po.code as order_code, po.status as order_status, po.id as po_id
             FROM weekly_material_requests wmr
             LEFT JOIN construction_sites cs ON wmr.construction_site_id = cs.id
             LEFT JOIN purchase_orders po ON wmr.order_id = po.id
             WHERE wmr.manager_id = ?
             ORDER BY wmr.week_start DESC
             LIMIT {$limit}",
            [$managerId]
        );
    }

    /**
     * Lista consolidada de compras (PARTE 26): itens dos PEDIDOS gerados
     * pela Lista Semanal. Usa os itens do pedido como fonte oficial.
     */
    public static function getConsolidatedPurchaseItems(string $weekStart, array $filters = []): array
    {
        $where = "wmr.week_start = ? AND wmr.order_id IS NOT NULL";
        $params = [$weekStart];

        if (!empty($filters['construction_site_id'])) {
            $where .= " AND wmr.construction_site_id = ?";
            $params[] = (int) $filters['construction_site_id'];
        }
        if (!empty($filters['manager_id'])) {
            $where .= " AND wmr.manager_id = ?";
            $params[] = (int) $filters['manager_id'];
        }
        if (!empty($filters['urgency'])) {
            $where .= " AND wmr.urgency = ?";
            $params[] = $filters['urgency'];
        }
        if (!empty($filters['order_status'])) {
            $where .= " AND po.status = ?";
            $params[] = $filters['order_status'];
        }

        // Ordenação (PARTE 27)
        switch ($filters['sort'] ?? 'urgency_date') {
            case 'date':       // Data da solicitação: mais antigos primeiro
                $orderBy = "wmr.filled_at ASC";
                break;
            case 'urgency':    // Urgência: crítico → baixo
                $orderBy = "FIELD(wmr.urgency,'critical','high','medium','low')";
                break;
            case 'urgency_date': // Urgência, depois necessidade, depois solicitação
            default:
                $orderBy = "FIELD(wmr.urgency,'critical','high','medium','low'), wmr.needed_date ASC, wmr.filled_at ASC";
                break;
        }

        return Database::fetchAll(
            "SELECT poi.material_name, poi.specification, poi.classification, poi.unit, poi.quantity,
                    wmr.id as request_id, wmr.urgency, wmr.needed_date, wmr.filled_at,
                    wmr.urgency_reason_no_advance, wmr.urgency_reason_site_occurrence, wmr.urgency_description,
                    cs.name as construction_site_name, cs.code as construction_site_code,
                    pu.name as manager_name,
                    po.code as order_code, po.status as order_status, po.id as po_id
             FROM weekly_material_requests wmr
             JOIN purchase_orders po ON wmr.order_id = po.id
             JOIN purchase_order_items poi ON poi.order_id = po.id
             JOIN pin_users pu ON wmr.manager_id = pu.id
             LEFT JOIN construction_sites cs ON wmr.construction_site_id = cs.id
             WHERE {$where}
             ORDER BY {$orderBy}",
            $params
        );
    }
}
