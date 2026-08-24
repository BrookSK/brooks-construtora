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
        // Responsáveis pela lista semanal = pin_users vinculados a alguma obra
        // na fase 'weekly' (definido na tela de edição da obra).
        // NÃO usa mais o flag is_weekly_manager: o vínculo é sempre por obra.
        $managers = Database::fetchAll(
            "SELECT DISTINCT pu.id, pu.name, pu.phone, pu.email
             FROM pin_users pu
             JOIN construction_site_approvers csa ON csa.pin_user_id = pu.id
             JOIN construction_sites cs ON cs.id = csa.construction_site_id
             WHERE pu.active = 1 AND csa.phase = 'weekly' AND cs.status = 'active'
             ORDER BY pu.name ASC"
        );
        $created = 0;

        foreach ($managers as $manager) {
            // Cada responsável recebe um link por OBRA em que é responsável pela lista semanal.
            $sites = self::sitesForManager((int) $manager['id']);

            // Sem obra vinculada na fase 'weekly' → não cria nada para este responsável.
            if (empty($sites)) {
                continue;
            }

            foreach ($sites as $site) {
                $siteId = (int) $site['id'];

                // Idempotência por (responsável, ciclo, obra)
                $existing = Database::fetch(
                    "SELECT id FROM weekly_material_requests WHERE manager_id = ? AND week_start = ? AND construction_site_id = ?",
                    [$manager['id'], $weekStart, $siteId]
                );
                if ($existing) continue;

                $reqId = self::create([
                    'manager_id' => $manager['id'],
                    'construction_site_id' => $siteId,
                    'week_start' => $weekStart,
                    // Prazo de resposta = início do ciclo (deve responder antes)
                    'response_deadline' => $weekStart,
                    'token' => self::generateToken(),
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $siteLabel = $site['name'] ? " (obra: {$site['name']})" : '';
                \App\Models\WeeklyMaterialLog::record(
                    \App\Models\WeeklyMaterialLog::ACTION_LINK_GENERATED,
                    $reqId,
                    "Solicitação e link gerados para {$manager['name']}{$siteLabel}",
                    $weekStart
                );
                $created++;
            }
        }

        \App\Models\WeeklyMaterialLog::record(
            \App\Models\WeeklyMaterialLog::ACTION_WEEK_CREATED,
            null,
            "Ciclo gerado: {$created} solicitação(ões) criada(s)",
            $weekStart
        );

        return $created;
    }

    /**
     * Apaga um ciclo inteiro (todas as solicitações daquele week_start) e
     * seus itens/logs associados. Usado no gerenciamento de ciclos (testes).
     * Retorna a quantidade de solicitações removidas.
     */
    public static function deleteCycle(string $weekStart): int
    {
        $rows = Database::fetchAll(
            "SELECT id FROM weekly_material_requests WHERE week_start = ?",
            [$weekStart]
        );
        $ids = array_map(fn($r) => (int) $r['id'], $rows);

        foreach ($ids as $rid) {
            Database::delete('weekly_material_request_items', 'request_id = ?', [$rid]);
        }

        // Logs do ciclo
        try {
            Database::delete('weekly_material_logs', 'week_start = ?', [$weekStart]);
        } catch (\Throwable $e) {}

        $count = Database::delete(self::$table, 'week_start = ?', [$weekStart]);
        return (int) $count;
    }

    /**
     * Retorna a obra padrão de um responsável se ele estiver vinculado a
     * exatamente uma obra (via construction_site_approvers). Caso contrário null.
     */
    public static function defaultSiteForManager(int $managerId): ?int
    {
        try {
            $rows = Database::fetchAll(
                "SELECT DISTINCT construction_site_id FROM construction_site_approvers WHERE pin_user_id = ? AND phase = 'weekly'",
                [$managerId]
            );
            if (count($rows) === 1) {
                return (int) $rows[0]['construction_site_id'];
            }
        } catch (\Exception $e) {}
        return null;
    }

    /**
     * Obras vinculadas a um responsável para a LISTA SEMANAL.
     * Filtra pela fase 'weekly' (definida na tela de edição da obra).
     * Assim o gerente só recebe a lista das obras em que foi marcado
     * especificamente como responsável pela lista semanal.
     */
    public static function sitesForManager(int $managerId): array
    {
        try {
            return Database::fetchAll(
                "SELECT cs.id, cs.name, cs.code
                 FROM construction_site_approvers csa
                 JOIN construction_sites cs ON csa.construction_site_id = cs.id
                 WHERE csa.pin_user_id = ? AND csa.phase = 'weekly' AND cs.status = 'active'
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
     * Prazo efetivo de resposta de uma solicitação. Usa response_deadline
     * quando existir; caso contrário considera o início da semana (o
     * responsável deveria responder antes de a semana começar).
     */
    public static function effectiveDeadline(array $req): ?string
    {
        if (!empty($req['response_deadline'])) return $req['response_deadline'];
        return $req['week_start'] ?? null;
    }

    /**
     * Classificação de PONTUALIDADE do preenchimento (monitoramento).
     * Retorna: ['on_time'|'late'|'not_filled'|'pending', label, classe-css].
     *
     *  - on_time:    preencheu dentro do prazo de resposta
     *  - late:       preencheu, porém após o prazo (em dia depois do atraso)
     *  - not_filled: prazo expirou e não preencheu (status overdue)
     *  - pending:    ainda dentro do prazo, sem resposta
     */
    public static function punctuality(array $req): array
    {
        $status = $req['status'] ?? 'pending';

        if ($status === 'filled') {
            $deadline = self::effectiveDeadline($req);
            $filledAt = $req['filled_at'] ?? null;
            if ($deadline && $filledAt) {
                $filledDay = strtotime(date('Y-m-d', strtotime($filledAt)));
                $deadlineDay = strtotime(date('Y-m-d', strtotime($deadline)));
                if ($filledDay !== false && $deadlineDay !== false && $filledDay > $deadlineDay) {
                    return ['late', 'Preencheu com atraso', 'bg-warning text-dark'];
                }
            }
            return ['on_time', 'Em dia', 'bg-success'];
        }

        if ($status === 'overdue') {
            return ['not_filled', 'Não preencheu', 'bg-danger'];
        }

        return ['pending', 'Pendente', 'bg-secondary'];
    }

    /**
     * Relatório de pontualidade por responsável dentro de um intervalo de
     * semanas (monitoramento gerencial). Contabiliza em dia, atrasados que
     * preencheram e não preenchidos.
     *
     * @param string   $start     data inicial (week_start >=)
     * @param string   $end       data final (week_start <=)
     * @param int|null $managerId filtra um responsável específico
     */
    public static function getPunctualityReport(string $start, string $end, ?int $managerId = null): array
    {
        $where = "wmr.week_start BETWEEN ? AND ?";
        $params = [$start, $end];
        if ($managerId) {
            $where .= " AND wmr.manager_id = ?";
            $params[] = $managerId;
        }

        $rows = Database::fetchAll(
            "SELECT wmr.manager_id, pu.name as manager_name,
                    wmr.status, wmr.week_start, wmr.response_deadline, wmr.filled_at, wmr.notified_at
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             WHERE {$where}",
            $params
        );

        $byManager = [];
        foreach ($rows as $r) {
            $mid = (int) $r['manager_id'];
            if (!isset($byManager[$mid])) {
                $byManager[$mid] = [
                    'manager_id' => $mid,
                    'manager_name' => $r['manager_name'],
                    'total' => 0,
                    'on_time' => 0,
                    'late' => 0,
                    'not_filled' => 0,
                    'pending' => 0,
                ];
            }
            $byManager[$mid]['total']++;
            [$key] = self::punctuality($r);
            $byManager[$mid][$key]++;
        }

        // Taxa de pontualidade = em dia / (respondidos)
        foreach ($byManager as &$m) {
            $responded = $m['on_time'] + $m['late'];
            $m['response_rate'] = $m['total'] > 0 ? round($responded / $m['total'] * 100) : 0;
            $m['punctual_rate'] = $responded > 0 ? round($m['on_time'] / $responded * 100) : 0;
        }
        unset($m);

        // Ordena por mais atrasos/não preenchimentos primeiro (foco gerencial)
        usort($byManager, function ($a, $b) {
            $pa = $a['late'] + $a['not_filled'];
            $pb = $b['late'] + $b['not_filled'];
            if ($pa === $pb) return strcmp($a['manager_name'], $b['manager_name']);
            return $pb <=> $pa;
        });

        return array_values($byManager);
    }

    /**
     * Totais gerais de pontualidade num intervalo (para cards de resumo).
     */
    public static function getPunctualityTotals(string $start, string $end, ?int $managerId = null): array
    {
        $report = self::getPunctualityReport($start, $end, $managerId);
        $totals = ['total' => 0, 'on_time' => 0, 'late' => 0, 'not_filled' => 0, 'pending' => 0];
        foreach ($report as $m) {
            $totals['total'] += $m['total'];
            $totals['on_time'] += $m['on_time'];
            $totals['late'] += $m['late'];
            $totals['not_filled'] += $m['not_filled'];
            $totals['pending'] += $m['pending'];
        }
        return $totals;
    }

    /**
     * Lista detalhada de solicitações num intervalo, com pontualidade,
     * para rastreamento (monitoramento). Aceita filtros de responsável.
     */
    public static function getMonitoringList(string $start, string $end, ?int $managerId = null): array
    {
        $where = "wmr.week_start BETWEEN ? AND ?";
        $params = [$start, $end];
        if ($managerId) {
            $where .= " AND wmr.manager_id = ?";
            $params[] = $managerId;
        }

        return Database::fetchAll(
            "SELECT wmr.*, pu.name as manager_name,
                    cs.name as construction_site_name, cs.code as construction_site_code,
                    po.code as order_code, po.status as order_status, po.id as po_id
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             LEFT JOIN construction_sites cs ON wmr.construction_site_id = cs.id
             LEFT JOIN purchase_orders po ON wmr.order_id = po.id
             WHERE {$where}
             ORDER BY wmr.week_start DESC, pu.name ASC",
            $params
        );
    }

    /**
     * Lista de responsáveis (pin_users marcados como gerentes semanais)
     * para popular seletores de filtro.
     */
    public static function managersForFilter(): array
    {
        return Database::fetchAll(
            "SELECT id, name FROM pin_users WHERE is_weekly_manager = 1 ORDER BY name ASC"
        );
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
     * Intervalo (em dias) do ciclo de envio. Segue a antecedência mínima
     * configurada (ex.: 15 dias). Assim, se a previsão é para 15 dias, o
     * envio ocorre a cada 15 dias — e não semanalmente.
     */
    public static function cycleIntervalDays(): int
    {
        $interval = (int) \App\Models\Setting::get('weekly_cycle_interval_days', '');
        if ($interval <= 0) {
            // fallback: usa a antecedência mínima configurada (padrão 15)
            $interval = (int) \App\Models\Setting::get('weekly_min_advance_days', '15');
        }
        return max(1, $interval);
    }

    /**
     * Início do ciclo mais recente já gerado (a maior week_start existente).
     */
    public static function latestCycleStart(): ?string
    {
        $row = Database::fetch("SELECT MAX(week_start) as ws FROM weekly_material_requests");
        return $row && !empty($row['ws']) ? $row['ws'] : null;
    }

    /**
     * Número sequencial do ciclo (1º, 2º, 3º...) considerando todos os
     * week_start distintos já criados, em ordem cronológica.
     */
    public static function cycleNumber(string $weekStart): int
    {
        $row = Database::fetch(
            "SELECT COUNT(DISTINCT week_start) AS n FROM weekly_material_requests WHERE week_start <= ?",
            [$weekStart]
        );
        return max(1, (int) ($row['n'] ?? 1));
    }

    /**
     * Rótulo amigável do ciclo. Ex.:
     *  "2º ciclo — 3ª semana de Agosto (23/08/2026 a 06/09/2026, 15 dias)"
     */
    public static function cycleLabel(string $weekStart): array
    {
        $interval = self::cycleIntervalDays();
        $startTs = strtotime($weekStart);
        $endTs = strtotime(self::cycleEnd($weekStart));
        $number = self::cycleNumber($weekStart);

        $meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                  7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
        $mesNome = $meses[(int) date('n', $startTs)] ?? '';
        // Semana do mês baseada no dia (1-7 = 1ª, 8-14 = 2ª, ...)
        $semanaDoMes = (int) ceil(((int) date('j', $startTs)) / 7);
        $ordSemana = ['1ª','2ª','3ª','4ª','5ª'][$semanaDoMes - 1] ?? ($semanaDoMes . 'ª');

        return [
            'number' => $number,
            'interval' => $interval,
            'start' => date('d/m/Y', $startTs),
            'end' => date('d/m/Y', $endTs),
            'week_of_month' => $ordSemana . ' semana de ' . $mesNome,
            'text' => "{$number}º ciclo — {$ordSemana} semana de {$mesNome}"
                . " ({$interval} " . ($interval === 1 ? 'dia' : 'dias') . "): "
                . date('d/m/Y', $startTs) . " a " . date('d/m/Y', $endTs),
        ];
    }

    /**
     * Início do PRÓXIMO ciclo, respeitando o intervalo configurado.
     * Se já existe um ciclo, soma o intervalo a ele; senão, começa hoje.
     */
    public static function nextCycleStart(): string
    {
        $interval = self::cycleIntervalDays();
        $latest = self::latestCycleStart();
        if ($latest) {
            $next = strtotime($latest . ' +' . $interval . ' days');
            // Se o próximo cálculo ainda está no passado, avança até o futuro/hoje
            $today = strtotime(date('Y-m-d'));
            while ($next < $today) {
                $next = strtotime(date('Y-m-d', $next) . ' +' . $interval . ' days');
            }
            return date('Y-m-d', $next);
        }
        return date('Y-m-d');
    }

    /**
     * Data final (fim) de um ciclo, dado o início.
     */
    public static function cycleEnd(string $cycleStart): string
    {
        $interval = self::cycleIntervalDays();
        return date('Y-m-d', strtotime($cycleStart . ' +' . ($interval - 1) . ' days'));
    }

    /**
     * Antecedência mínima (em dias) da data de necessidade ("preciso até"),
     * contada a partir da data em que a pessoa preenche (hoje).
     */
    public static function minNeedDays(): int
    {
        $z = (int) \App\Models\Setting::get('weekly_min_need_days', '');
        if ($z <= 0) {
            $z = (int) \App\Models\Setting::get('weekly_min_advance_days', '5');
        }
        return max(1, $z);
    }

    /**
     * Data de necessidade padrão/mínima (pré-preenchida) para uma solicitação:
     * hoje + antecedência mínima da necessidade (Z).
     */
    public static function defaultNeededDate(): string
    {
        return date('Y-m-d', strtotime('+' . self::minNeedDays() . ' days'));
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
     * Verifica se a coluna needed_date existe em weekly_material_request_items
     * (compat. com bancos sem a migration 035).
     */
    private static ?bool $itemsNeededDateCol = null;
    public static function itemsHaveNeededDateColumn(): bool
    {
        if (self::$itemsNeededDateCol === null) {
            try {
                $r = Database::fetch(
                    "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'weekly_material_request_items' AND COLUMN_NAME = 'needed_date' LIMIT 1"
                );
                self::$itemsNeededDateCol = !empty($r);
            } catch (\Exception $e) {
                self::$itemsNeededDateCol = false;
            }
        }
        return self::$itemsNeededDateCol;
    }

    /**
     * Salvar itens de uma lista
     */
    public static function saveItems(int $requestId, array $items): void
    {
        // Limpar itens existentes
        Database::delete('weekly_material_request_items', 'request_id = ?', [$requestId]);

        $hasNeededDate = self::itemsHaveNeededDateColumn();

        // Inserir novos
        $count = 0;
        foreach ($items as $item) {
            if (empty(trim($item['material_name'] ?? ''))) continue;
            $data = [
                'request_id' => $requestId,
                'material_name' => trim($item['material_name']),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit' => trim($item['unit'] ?? ''),
                'notes' => trim($item['notes'] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if ($hasNeededDate) {
                $d = trim($item['needed_date'] ?? '');
                $data['needed_date'] = $d !== '' ? $d : null;
            }
            Database::insert('weekly_material_request_items', $data);
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

        // Coluna de data por item (migration 035). Se existir, priorizamos
        // a data específica do item; senão, caímos na data da solicitação.
        $hasItemDate = \App\Models\PurchaseOrder::orderItemsHaveNeededDate();
        $itemDateSelect = $hasItemDate ? "poi.needed_date as item_needed_date," : "NULL as item_needed_date,";
        $effectiveDate = $hasItemDate ? "COALESCE(poi.needed_date, wmr.needed_date)" : "wmr.needed_date";

        // Ordenação (PARTE 27) — usa a data efetiva (item ou solicitação)
        switch ($filters['sort'] ?? 'urgency_date') {
            case 'date':       // Data da necessidade: mais próximos primeiro
                $orderBy = "{$effectiveDate} ASC, wmr.filled_at ASC";
                break;
            case 'urgency':    // Urgência: crítico → baixo
                $orderBy = "FIELD(wmr.urgency,'critical','high','medium','low')";
                break;
            case 'urgency_date': // Urgência, depois necessidade (item), depois solicitação
            default:
                $orderBy = "FIELD(wmr.urgency,'critical','high','medium','low'), {$effectiveDate} ASC, wmr.filled_at ASC";
                break;
        }

        return Database::fetchAll(
            "SELECT poi.material_name, poi.specification, poi.classification, poi.unit, poi.quantity,
                    {$itemDateSelect}
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
