<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\NiboService;
use App\Models\NiboSyncSnapshot;

/**
 * Dashboard Financeiro (Fluxo de Caixa) — SOMENTE LEITURA.
 * Suporta múltiplas empresas do Nibo (ex.: Brooks e Vétriks), cada uma com
 * seu token. A visão "completo" consolida todas. Nunca escreve no Nibo.
 */
class FinanceController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
        if (!Auth::hasPermission('orders')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Resolve a empresa a partir do input. Retorna uma chave válida
     * (brooks/vetriks) ou 'completo' para a visão consolidada.
     */
    private function resolveCompany(string $default = 'brooks'): string
    {
        $c = (string) $this->input('company', $default);
        if ($c === 'completo' || NiboService::isValidCompany($c)) return $c;
        return $default;
    }

    /**
     * Página do dashboard. Carrega o último snapshot (exibição instantânea).
     */
    public function index(): void
    {
        $company = $this->resolveCompany('completo');

        // Status do token de cada empresa (para a UI mostrar avisos)
        $tokens = [];
        foreach (NiboService::companies() as $key => $label) {
            $tokens[$key] = NiboService::token($key) !== '';
        }

        $this->view('admin.finance.index', [
            'companies' => NiboService::companies(),
            'tokens' => $tokens,
            'currentCompany' => $company,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Financeiro',
            'currentPage' => 'finance',
        ]);
    }

    /**
     * Salva o token de uma empresa (via POST).
     */
    public function saveToken(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        $company = (string) $this->input('company', '');
        if (!NiboService::isValidCompany($company)) {
            $this->json(['ok' => false, 'error' => 'Empresa inválida.'], 422);
            return;
        }
        $token = trim((string) $this->input('token', ''));
        NiboService::saveToken($company, $token);
        self::log("Token salvo para {$company} (" . ($token === '' ? 'vazio' : 'definido') . ')');
        $this->json(['ok' => true, 'company' => $company, 'has_token' => $token !== '']);
    }

    /**
     * Retorna o último snapshot como JSON. Para 'completo', consolida as
     * empresas cadastradas em tempo de leitura.
     */
    public function data(): void
    {
        $company = $this->resolveCompany('completo');

        if ($company === 'completo') {
            $merged = $this->consolidated();
            if ($merged === null) {
                $this->json(['ok' => true, 'has_data' => false]);
                return;
            }
            $this->json([
                'ok' => true,
                'has_data' => true,
                'synced_at' => $merged['generated_at'] ?? null,
                'data' => $merged,
            ]);
            return;
        }

        $latest = NiboSyncSnapshot::latest($company);
        if (!$latest) {
            $this->json(['ok' => true, 'has_data' => false]);
            return;
        }
        $payload = NiboSyncSnapshot::decodePayload($latest);
        $this->json([
            'ok' => true,
            'has_data' => true,
            'synced_at' => $latest['created_at'],
            'data' => $payload,
        ]);
    }

    /**
     * Consolida os últimos snapshots de todas as empresas numa estrutura só.
     * Marca cada lançamento/conta com a empresa de origem.
     */
    private function consolidated(): ?array
    {
        $payables = $receivables = $accounts = [];
        $balance = 0.0;
        $ccMap = [];
        $contactMap = [];
        $latestAt = null;
        $found = false;

        foreach (array_keys(NiboService::companies()) as $key) {
            $row = NiboSyncSnapshot::latest($key);
            if (!$row) continue;
            $found = true;
            $p = NiboSyncSnapshot::decodePayload($row);
            $label = NiboService::companies()[$key];

            foreach (($p['payables'] ?? []) as $x) { $x['company'] = $key; $x['company_label'] = $label; $payables[] = $x; }
            foreach (($p['receivables'] ?? []) as $x) { $x['company'] = $key; $x['company_label'] = $label; $receivables[] = $x; }
            foreach (($p['accounts'] ?? []) as $a) { $a['company'] = $key; $a['company_label'] = $label; $accounts[] = $a; }
            $balance += (float) ($p['totals']['balance'] ?? 0);

            foreach (($p['filters']['costcenters'] ?? []) as $cc) { if (!empty($cc['id'])) $ccMap[$cc['id']] = $cc['name']; }
            foreach (($p['filters']['contacts'] ?? []) as $ct) { if (!empty($ct['id'])) $contactMap[$ct['id']] = $ct['name']; }

            if ($latestAt === null || ($row['created_at'] ?? '') > $latestAt) $latestAt = $row['created_at'] ?? null;
        }

        if (!$found) return null;

        $toList = function (array $m) {
            $out = [];
            foreach ($m as $id => $name) $out[] = ['id' => $id, 'name' => $name];
            usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            return $out;
        };

        return [
            'generated_at' => $latestAt,
            'masters' => [],
            'filters' => ['costcenters' => $toList($ccMap), 'contacts' => $toList($contactMap)],
            'accounts' => $accounts,
            'payables' => $payables,
            'receivables' => $receivables,
            'totals' => [
                'balance' => $balance,
                'payables' => count($payables),
                'receivables' => count($receivables),
            ],
            'errors' => [],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // SYNC EM ETAPAS (evita timeout do proxy). Orquestrado pelo navegador:
    // syncStart → syncPage (várias) → syncFinish. Cada chamada é curta.
    // Sempre associada a UMA empresa (não se sincroniza 'completo').
    // ═══════════════════════════════════════════════════════════════════

    public function syncStart(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        $company = $this->resolveCompany('brooks');
        if ($company === 'completo') {
            $this->json(['ok' => false, 'error' => 'Selecione uma empresa específica para atualizar.'], 422);
            return;
        }
        if (NiboService::token($company) === '') {
            $this->json(['ok' => false, 'error' => 'Token do Nibo não configurado para esta empresa. Cadastre o token nesta aba.'], 422);
            return;
        }
        @set_time_limit(60);

        $from = $this->input('from', '2025-12-01');
        try {
            $bal = NiboService::accountsBalance(null, $company);
        } catch (\Throwable $e) {
            self::log("syncStart[{$company}] erro saldo: " . $e->getMessage());
            $bal = ['accounts' => [], 'balance' => 0];
        }

        $_SESSION['finance_sync'] = [
            'company' => $company,
            'from' => $from,
            'accounts' => $bal['accounts'],
            'balance' => $bal['balance'],
            'payables' => [],
            'receivables' => [],
            'started_at' => date('Y-m-d H:i:s'),
        ];
        self::log("syncStart[{$company}] from={$from} contas=" . count($bal['accounts']) . " saldo={$bal['balance']}");
        $this->json(['ok' => true, 'company' => $company, 'accounts' => count($bal['accounts']), 'balance' => $bal['balance']]);
    }

    public function syncPage(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        if (empty($_SESSION['finance_sync'])) {
            $this->json(['ok' => false, 'error' => 'Sessão de sincronização não iniciada.'], 409);
            return;
        }
        @set_time_limit(60);

        $company = $_SESSION['finance_sync']['company'] ?? 'brooks';
        $type = $this->input('type', 'payable') === 'receivable' ? 'receivable' : 'payable';
        $skip = max(0, (int) $this->input('skip', 0));
        $pageSize = 100;
        $from = $_SESSION['finance_sync']['from'] ?? '2025-12-01';

        try {
            $items = NiboService::schedulePage($type, $skip, $from, null, $pageSize, $company);
        } catch (\Throwable $e) {
            self::log("syncPage[{$company}] {$type} skip={$skip} ERRO: " . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage(), 'type' => $type, 'skip' => $skip], 502);
            return;
        }

        $bucket = $type === 'receivable' ? 'receivables' : 'payables';
        foreach ($items as $it) $_SESSION['finance_sync'][$bucket][] = $it;

        $done = count($items) < $pageSize;
        $total = count($_SESSION['finance_sync'][$bucket]);
        $this->json([
            'ok' => true,
            'type' => $type,
            'received' => count($items),
            'accumulated' => $total,
            'next_skip' => $skip + $pageSize,
            'done' => $done,
        ]);
    }

    public function syncFinish(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        if (empty($_SESSION['finance_sync'])) {
            $this->json(['ok' => false, 'error' => 'Sessão de sincronização não iniciada.'], 409);
            return;
        }
        @set_time_limit(120);

        $acc = $_SESSION['finance_sync'];
        $company = $acc['company'] ?? 'brooks';
        $payables = $acc['payables'] ?? [];
        $receivables = $acc['receivables'] ?? [];
        $accounts = $acc['accounts'] ?? [];
        $balance = $acc['balance'] ?? 0;

        // ── Rastreabilidade de mudanças de data de vencimento ──────────
        // Compara com o snapshot anterior da mesma empresa. Preserva a data
        // original (original_due_date) e guarda o histórico de alterações.
        $prevRow = NiboSyncSnapshot::latest($company);
        $prevById = [];
        if ($prevRow) {
            $prevPayload = NiboSyncSnapshot::decodePayload($prevRow);
            foreach (array_merge($prevPayload['payables'] ?? [], $prevPayload['receivables'] ?? []) as $px) {
                if (!empty($px['id'])) $prevById[(string) $px['id']] = $px;
            }
        }
        $nowStamp = date('Y-m-d H:i:s');
        $applyTracking = function (array &$list) use ($prevById, $nowStamp) {
            foreach ($list as &$x) {
                $id = isset($x['id']) ? (string) $x['id'] : '';
                $prev = $id !== '' ? ($prevById[$id] ?? null) : null;

                // Data original: herda do anterior; se não existir, é a atual
                $x['original_due_date'] = $prev['original_due_date'] ?? ($prev['due_date'] ?? $x['due_date']);
                $x['date_history'] = $prev['date_history'] ?? [];

                // Detecta alteração da data de vencimento entre sincronizações
                if ($prev && !empty($prev['due_date']) && !empty($x['due_date'])
                    && substr($prev['due_date'], 0, 10) !== substr($x['due_date'], 0, 10)) {
                    $x['date_history'][] = [
                        'from' => substr($prev['due_date'], 0, 10),
                        'to' => substr($x['due_date'], 0, 10),
                        'at' => $nowStamp,
                    ];
                }
                $x['date_changed'] = !empty($x['date_history']);
                // Data anterior imediata (para exibir "alterado para")
                $x['previous_due_date'] = ($prev['due_date'] ?? null);
            }
            unset($x);
        };
        $applyTracking($payables);
        $applyTracking($receivables);

        $ccMap = [];
        $contactMap = [];
        foreach (array_merge($payables, $receivables) as $x) {
            if (!empty($x['cost_center_id']) && !empty($x['cost_center']) && $x['cost_center'] !== '—') {
                $ccMap[(string) $x['cost_center_id']] = $x['cost_center'];
            }
            if (!empty($x['contact_id']) && !empty($x['contact_name']) && $x['contact_name'] !== '—') {
                $contactMap[(string) $x['contact_id']] = $x['contact_name'];
            }
        }
        $toList = function (array $m) {
            $out = [];
            foreach ($m as $id => $name) $out[] = ['id' => $id, 'name' => $name];
            usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            return $out;
        };

        $result = [
            'ok' => true,
            'generated_at' => date('Y-m-d H:i:s'),
            'masters' => [],
            'filters' => ['costcenters' => $toList($ccMap), 'contacts' => $toList($contactMap)],
            'accounts' => $accounts,
            'payables' => $payables,
            'receivables' => $receivables,
            'totals' => [
                'balance' => $balance,
                'payables' => count($payables),
                'receivables' => count($receivables),
            ],
            'errors' => [],
        ];

        $createdBy = Auth::user()['name'] ?? 'Sistema';
        try {
            NiboSyncSnapshot::store($result, $createdBy, $company);
        } catch (\Throwable $e) {
            self::log("syncFinish[{$company}] erro ao salvar: " . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Falha ao salvar os dados: ' . $e->getMessage()], 500);
            return;
        }

        self::log(sprintf('syncFinish[%s] OK — payables=%d receivables=%d contas=%d', $company, count($payables), count($receivables), count($accounts)));
        unset($_SESSION['finance_sync']);

        $this->json(['ok' => true, 'company' => $company, 'synced_at' => $result['generated_at'], 'data' => $result]);
    }

    /**
     * Histórico de sincronizações (data/hora + resumo).
     */
    public function history(): void
    {
        $company = $this->resolveCompany('completo');
        $rows = NiboSyncSnapshot::history(30, $company === 'completo' ? null : $company);
        foreach ($rows as &$r) {
            $r['totals'] = json_decode($r['totals_json'] ?? '', true) ?: [];
            unset($r['totals_json']);
        }
        $this->json(['ok' => true, 'history' => $rows]);
    }

    private static function logFile(): string
    {
        $dir = ROOT_PATH . '/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . '/finance_sync.log';
    }

    private static function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents(self::logFile(), $line, FILE_APPEND | LOCK_EX);
        error_log('[finance] ' . $message);
    }

    public function logs(): void
    {
        $file = self::logFile();
        if (!is_file($file)) {
            $this->json(['ok' => true, 'lines' => [], 'message' => 'Nenhum log ainda.']);
            return;
        }
        $content = @file_get_contents($file);
        $lines = $content !== false ? explode(PHP_EOL, trim($content)) : [];
        $lines = array_slice($lines, -200);
        $this->json(['ok' => true, 'lines' => $lines]);
    }

    public function clearLogs(): void
    {
        @file_put_contents(self::logFile(), '');
        $this->json(['ok' => true]);
    }
}
