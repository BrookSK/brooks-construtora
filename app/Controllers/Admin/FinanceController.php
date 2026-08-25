<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\NiboService;
use App\Models\NiboSyncSnapshot;

/**
 * Dashboard Financeiro (Fluxo de Caixa) — SOMENTE LEITURA.
 * Consome apenas rotas GET do Nibo (via NiboService::syncAll) e exibe
 * KPIs, gráficos e tabelas. Nunca escreve nada no Nibo.
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
     * Página do dashboard. Carrega o último snapshot (exibição instantânea).
     */
    public function index(): void
    {
        $latest = NiboSyncSnapshot::latest();
        $snapshot = $latest ? NiboSyncSnapshot::decodePayload($latest) : null;

        $this->view('admin.finance.index', [
            'snapshot' => $snapshot,
            'lastSyncAt' => $latest['created_at'] ?? null,
            'hasToken' => NiboService::token() !== '',
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Financeiro',
            'currentPage' => 'finance',
        ]);
    }

    /**
     * Retorna o último snapshot como JSON (para o front carregar os dados).
     */
    public function data(): void
    {
        $latest = NiboSyncSnapshot::latest();
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

    // ═══════════════════════════════════════════════════════════════════
    // SYNC EM ETAPAS (evita timeout do proxy Apache/nginx)
    // O navegador orquestra: syncStart → syncPage (várias) → syncFinish.
    // Cada chamada é curta, então nenhuma estoura o limite do proxy.
    // O acúmulo fica na sessão.
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Inicia a sincronização: zera o acumulador e busca só o saldo (rápido).
     */
    public function syncStart(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        if (NiboService::token() === '') {
            $this->json(['ok' => false, 'error' => 'Token do Nibo não configurado.'], 422);
            return;
        }
        @set_time_limit(60);

        $from = $this->input('from', '2025-12-01');
        try {
            $bal = NiboService::accountsBalance();
        } catch (\Throwable $e) {
            self::log('syncStart erro saldo: ' . $e->getMessage());
            $bal = ['accounts' => [], 'balance' => 0];
        }

        $_SESSION['finance_sync'] = [
            'from' => $from,
            'accounts' => $bal['accounts'],
            'balance' => $bal['balance'],
            'payables' => [],
            'receivables' => [],
            'started_at' => date('Y-m-d H:i:s'),
        ];
        self::log("syncStart from={$from} contas=" . count($bal['accounts']) . " saldo={$bal['balance']}");
        $this->json(['ok' => true, 'accounts' => count($bal['accounts']), 'balance' => $bal['balance']]);
    }

    /**
     * Busca UMA página de um tipo (payable|receivable) e acumula na sessão.
     * Retorna se há mais páginas (done=false) ou não (done=true).
     */
    public function syncPage(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        if (empty($_SESSION['finance_sync'])) {
            $this->json(['ok' => false, 'error' => 'Sessão de sincronização não iniciada.'], 409);
            return;
        }
        @set_time_limit(60);

        $type = $this->input('type', 'payable') === 'receivable' ? 'receivable' : 'payable';
        $skip = max(0, (int) $this->input('skip', 0));
        $pageSize = 100;
        $from = $_SESSION['finance_sync']['from'] ?? '2025-12-01';

        try {
            $items = NiboService::schedulePage($type, $skip, $from, null, $pageSize);
        } catch (\Throwable $e) {
            self::log("syncPage {$type} skip={$skip} ERRO: " . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage(), 'type' => $type, 'skip' => $skip], 502);
            return;
        }

        $bucket = $type === 'receivable' ? 'receivables' : 'payables';
        foreach ($items as $it) $_SESSION['finance_sync'][$bucket][] = $it;

        $done = count($items) < $pageSize; // última página
        $total = count($_SESSION['finance_sync'][$bucket]);
        self::log("syncPage {$type} skip={$skip} +{$total} (done=" . ($done ? '1' : '0') . ')');
        $this->json([
            'ok' => true,
            'type' => $type,
            'received' => count($items),
            'accumulated' => $total,
            'next_skip' => $skip + $pageSize,
            'done' => $done,
        ]);
    }

    /**
     * Finaliza: consolida os filtros, calcula totais e salva o snapshot.
     */
    public function syncFinish(): void
    {
        if (!$this->isPost()) { $this->json(['ok' => false, 'error' => 'Método inválido.'], 400); return; }
        if (empty($_SESSION['finance_sync'])) {
            $this->json(['ok' => false, 'error' => 'Sessão de sincronização não iniciada.'], 409);
            return;
        }
        @set_time_limit(120);

        $acc = $_SESSION['finance_sync'];
        $payables = $acc['payables'] ?? [];
        $receivables = $acc['receivables'] ?? [];
        $accounts = $acc['accounts'] ?? [];
        $balance = $acc['balance'] ?? 0;

        // Monta filtros (centros de custo e contatos) a partir dos lançamentos
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
        $filters = [
            'costcenters' => $toList($ccMap),
            'contacts' => $toList($contactMap),
        ];

        $result = [
            'ok' => true,
            'generated_at' => date('Y-m-d H:i:s'),
            'masters' => [],
            'filters' => $filters,
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
            NiboSyncSnapshot::store($result, $createdBy);
        } catch (\Throwable $e) {
            self::log('syncFinish erro ao salvar: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Falha ao salvar os dados: ' . $e->getMessage()], 500);
            return;
        }

        self::log(sprintf('syncFinish OK — payables=%d receivables=%d contas=%d', count($payables), count($receivables), count($accounts)));
        unset($_SESSION['finance_sync']);

        $this->json([
            'ok' => true,
            'synced_at' => $result['generated_at'],
            'data' => $result,
        ]);
    }

    /**
     * Executa a sincronização (somente leitura). Antes de salvar o novo
     * snapshot, o anterior permanece intacto no histórico (preservação de
     * estado). Retorna os dados novos + um pequeno resumo do que mudou.
     */
    public function sync(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método inválido.'], 400);
            return;
        }

        if (NiboService::token() === '') {
            $this->json(['ok' => false, 'error' => 'Token do Nibo não configurado. Configure em Desenvolvimento → API Nibo.'], 422);
            return;
        }

        // A consolidação percorre milhares de lançamentos (muitas chamadas à
        // API), então liberamos tempo/memória para concluir a sincronização.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        self::log('=== INÍCIO SYNC === por ' . (Auth::user()['name'] ?? '?'));
        $t0 = microtime(true);

        // Snapshot anterior (para calcular o "que mudou")
        $prev = NiboSyncSnapshot::latest();
        $prevPayload = $prev ? NiboSyncSnapshot::decodePayload($prev) : null;

        try {
            $result = NiboService::syncAll();
        } catch (\Throwable $e) {
            $msg = $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
            self::log('EXCEÇÃO no syncAll: ' . $msg);
            self::log($e->getTraceAsString());
            // Nunca limpa a tela: devolve erro e o front mantém os dados antigos
            $this->json([
                'ok' => false,
                'error' => 'Não foi possível atualizar os dados agora. Mostrando a última versão sincronizada.',
                'detail' => $msg,
            ], 502);
            return;
        }

        $elapsed = round(microtime(true) - $t0, 1);
        self::log(sprintf(
            'syncAll concluído em %ss — payables=%d, receivables=%d, accounts=%d, balance=%s, erros=%d',
            $elapsed,
            count($result['payables'] ?? []),
            count($result['receivables'] ?? []),
            count($result['accounts'] ?? []),
            $result['totals']['balance'] ?? 0,
            count($result['errors'] ?? [])
        ));
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $err) self::log('  erro parcial: ' . $err);
        }

        // Se veio completamente vazio e com erros, não sobrescreve
        if (!$result['ok'] && empty($result['payables']) && empty($result['receivables']) && empty($result['accounts'])) {
            self::log('Sync retornou vazio com erros — snapshot NÃO sobrescrito.');
            $this->json([
                'ok' => false,
                'error' => 'A sincronização falhou (verifique o token/conexão). Mantendo a última versão.',
                'errors' => $result['errors'] ?? [],
            ], 502);
            return;
        }

        // Salva o novo snapshot (o anterior continua no histórico)
        $createdBy = Auth::user()['name'] ?? 'Sistema';
        try {
            NiboSyncSnapshot::store($result, $createdBy);
            self::log('Snapshot salvo com sucesso.');
        } catch (\Throwable $e) {
            self::log('ERRO ao salvar snapshot: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->json([
                'ok' => false,
                'error' => 'Os dados foram lidos, mas houve falha ao salvá-los. Detalhe: ' . $e->getMessage(),
            ], 500);
            return;
        }

        // Resumo do que mudou (contagens simples)
        $changes = null;
        if ($prevPayload) {
            $changes = [
                'payables_diff' => count($result['payables']) - count($prevPayload['payables'] ?? []),
                'receivables_diff' => count($result['receivables']) - count($prevPayload['receivables'] ?? []),
            ];
        }

        $this->json([
            'ok' => true,
            'synced_at' => $result['generated_at'],
            'data' => [
                'generated_at' => $result['generated_at'],
                'masters' => $result['masters'],
                'filters' => $result['filters'] ?? [],
                'accounts' => $result['accounts'],
                'payables' => $result['payables'],
                'receivables' => $result['receivables'],
                'totals' => $result['totals'],
                'errors' => $result['errors'],
            ],
            'debug' => $result['debug'] ?? null,
            'changes' => $changes,
            'partial_errors' => $result['errors'] ?? [],
        ]);
    }

    /**
     * Histórico de sincronizações (data/hora + resumo).
     */
    public function history(): void
    {
        $rows = NiboSyncSnapshot::history(30);
        foreach ($rows as &$r) {
            $r['totals'] = json_decode($r['totals_json'] ?? '', true) ?: [];
            unset($r['totals_json']);
        }
        $this->json(['ok' => true, 'history' => $rows]);
    }

    /**
     * Caminho do arquivo de log da sincronização financeira.
     */
    private static function logFile(): string
    {
        $dir = ROOT_PATH . '/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . '/finance_sync.log';
    }

    /**
     * Registra uma linha no log de sincronização (para diagnóstico).
     */
    private static function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents(self::logFile(), $line, FILE_APPEND | LOCK_EX);
        // Também no error_log padrão do PHP/servidor
        error_log('[finance] ' . $message);
    }

    /**
     * Retorna as últimas linhas do log (para exibir na aba de diagnóstico).
     */
    public function logs(): void
    {
        $file = self::logFile();
        if (!is_file($file)) {
            $this->json(['ok' => true, 'lines' => [], 'message' => 'Nenhum log ainda.']);
            return;
        }
        $content = @file_get_contents($file);
        $lines = $content !== false ? explode(PHP_EOL, trim($content)) : [];
        // Últimas 200 linhas
        $lines = array_slice($lines, -200);
        $this->json(['ok' => true, 'lines' => $lines]);
    }

    /**
     * Limpa o arquivo de log.
     */
    public function clearLogs(): void
    {
        @file_put_contents(self::logFile(), '');
        $this->json(['ok' => true]);
    }
}
