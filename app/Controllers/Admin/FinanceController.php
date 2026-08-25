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

        // Snapshot anterior (para calcular o "que mudou")
        $prev = NiboSyncSnapshot::latest();
        $prevPayload = $prev ? NiboSyncSnapshot::decodePayload($prev) : null;

        try {
            $result = NiboService::syncAll();
        } catch (\Throwable $e) {
            // Nunca limpa a tela: devolve erro e o front mantém os dados antigos
            $this->json([
                'ok' => false,
                'error' => 'Não foi possível atualizar os dados agora. Mostrando a última versão sincronizada.',
                'detail' => $e->getMessage(),
            ], 502);
            return;
        }

        // Se veio completamente vazio e com erros, não sobrescreve
        if (!$result['ok'] && empty($result['payables']) && empty($result['receivables']) && empty($result['accounts'])) {
            $this->json([
                'ok' => false,
                'error' => 'A sincronização falhou (verifique o token/conexão). Mantendo a última versão.',
                'errors' => $result['errors'] ?? [],
            ], 502);
            return;
        }

        // Salva o novo snapshot (o anterior continua no histórico)
        $createdBy = Auth::user()['name'] ?? 'Sistema';
        NiboSyncSnapshot::store($result, $createdBy);

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
}
