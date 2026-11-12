<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Newsletter;
use App\Models\Magazine;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\NiboSyncSnapshot;

class DashboardController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $data = [
            'totalSubscribers' => Newsletter::count('active = 1'),
            'totalMagazines' => Magazine::count(),
            'publishedMagazines' => Magazine::count("status = 'published'"),
            'pendingMagazines' => Magazine::count("status IN ('generated', 'review')"),
            'totalUsers' => User::count(),
            'recentMagazines' => Magazine::getLatest(5),
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ];

        // Dados de pedidos (se tiver permissão)
        if (Auth::hasPermission('orders')) {
            $data['totalOrders'] = PurchaseOrder::count();
            $data['pendingQuoteOrders'] = PurchaseOrder::countByStatus('pending_quote');
            $data['pendingApprovalOrders'] = PurchaseOrder::countByStatus('pending_approval');
            $data['approvedOrders'] = PurchaseOrder::countByStatus('approved');
        }

        // Dados financeiros do Nibo (se tiver permissão)
        if (Auth::hasPermission('orders.payment')) {
            $data['financeData'] = $this->getFinanceData();
        }

        $this->view('admin.dashboard.index', $data);
    }

    /**
     * Busca dados financeiros consolidados do Nibo para exibir no dashboard
     */
    private function getFinanceData(): ?array
    {
        try {
            // Busca snapshot consolidado mais recente
            $snapshot = $this->getConsolidatedSnapshot();
            
            if (!$snapshot) {
                return null;
            }

            $payload = NiboSyncSnapshot::decodePayload($snapshot);
            
            if (!$payload) {
                return null;
            }

            $today = new \DateTime();
            $next30Days = (clone $today)->modify('+30 days');
            
            // Filtra contas dos próximos 30 dias
            $payables = array_filter($payload['payables'] ?? [], function($item) use ($today, $next30Days) {
                if (isset($item['status']) && $item['status'] === 'paid') {
                    return false;
                }
                $dueDate = isset($item['due_date']) ? new \DateTime($item['due_date']) : null;
                return $dueDate && $dueDate >= $today && $dueDate <= $next30Days;
            });

            $receivables = array_filter($payload['receivables'] ?? [], function($item) use ($today, $next30Days) {
                if (isset($item['status']) && $item['status'] === 'paid') {
                    return false;
                }
                $dueDate = isset($item['due_date']) ? new \DateTime($item['due_date']) : null;
                return $dueDate && $dueDate >= $today && $dueDate <= $next30Days;
            });

            // Contas vencidas (a pagar)
            $overduePayables = array_filter($payload['payables'] ?? [], function($item) use ($today) {
                if (isset($item['status']) && $item['status'] === 'paid') {
                    return false;
                }
                $dueDate = isset($item['due_date']) ? new \DateTime($item['due_date']) : null;
                return $dueDate && $dueDate < $today;
            });

            // Agrupa por categoria
            $categoriesData = $this->aggregateByCategory($payables, $receivables, $payload['filters']['categories'] ?? []);
            
            // Agrupa por centro de custo
            $costCentersData = $this->aggregateByCostCenter($payables, $receivables);

            return [
                'balance' => $payload['totals']['balance'] ?? 0,
                'totalPayables' => array_sum(array_column($payables, 'value')),
                'totalReceivables' => array_sum(array_column($receivables, 'value')),
                'totalOverdue' => array_sum(array_column($overduePayables, 'value')),
                'countPayables' => count($payables),
                'countReceivables' => count($receivables),
                'countOverdue' => count($overduePayables),
                'payables' => array_slice($this->sortByDate($payables), 0, 10),
                'receivables' => array_slice($this->sortByDate($receivables), 0, 10),
                'overduePayables' => array_slice($this->sortByDate($overduePayables), 0, 5),
                'categories' => $categoriesData,
                'costCenters' => $costCentersData,
                'syncedAt' => $snapshot['created_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            error_log('[Dashboard] Erro ao carregar dados financeiros: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca snapshot consolidado (completo) ou da primeira empresa disponível
     */
    private function getConsolidatedSnapshot(): ?array
    {
        // Tenta buscar visão consolidada primeiro
        $snapshot = NiboSyncSnapshot::latest('completo');
        
        if (!$snapshot) {
            // Fallback: busca brooks ou vetriks
            $snapshot = NiboSyncSnapshot::latest('brooks');
            if (!$snapshot) {
                $snapshot = NiboSyncSnapshot::latest('vetriks');
            }
        }

        return $snapshot;
    }

    /**
     * Ordena lançamentos por data de vencimento
     */
    private function sortByDate(array $items): array
    {
        usort($items, function($a, $b) {
            $dateA = isset($a['due_date']) ? strtotime($a['due_date']) : 0;
            $dateB = isset($b['due_date']) ? strtotime($b['due_date']) : 0;
            return $dateA - $dateB;
        });
        return $items;
    }

    /**
     * Agrega valores por categoria do Nibo
     */
    private function aggregateByCategory(array $payables, array $receivables, array $categories): array
    {
        $categoryMap = [];
        
        // Mapeia IDs para nomes
        foreach ($categories as $cat) {
            if (isset($cat['id'])) {
                $categoryMap[$cat['id']] = $cat['name'] ?? 'Sem categoria';
            }
        }

        $result = [];
        
        // Processa a pagar
        foreach ($payables as $item) {
            $catId = $item['category_id'] ?? null;
            $catName = $catId && isset($categoryMap[$catId]) ? $categoryMap[$catId] : ($item['category'] ?? 'Sem categoria');
            
            if (!isset($result[$catName])) {
                $result[$catName] = ['payables' => 0, 'receivables' => 0];
            }
            $result[$catName]['payables'] += $item['value'] ?? 0;
        }

        // Processa a receber
        foreach ($receivables as $item) {
            $catId = $item['category_id'] ?? null;
            $catName = $catId && isset($categoryMap[$catId]) ? $categoryMap[$catId] : ($item['category'] ?? 'Sem categoria');
            
            if (!isset($result[$catName])) {
                $result[$catName] = ['payables' => 0, 'receivables' => 0];
            }
            $result[$catName]['receivables'] += $item['value'] ?? 0;
        }

        // Ordena por total (maior primeiro)
        uasort($result, function($a, $b) {
            $totalA = abs($a['payables']) + abs($a['receivables']);
            $totalB = abs($b['payables']) + abs($b['receivables']);
            return $totalB <=> $totalA;
        });

        return array_slice($result, 0, 10, true);
    }

    /**
     * Agrega valores por centro de custo
     */
    private function aggregateByCostCenter(array $payables, array $receivables): array
    {
        $result = [];
        
        // Processa a pagar
        foreach ($payables as $item) {
            $ccName = $item['cost_center'] ?? 'Sem centro de custo';
            if ($ccName === '—') $ccName = 'Sem centro de custo';
            
            if (!isset($result[$ccName])) {
                $result[$ccName] = ['payables' => 0, 'receivables' => 0];
            }
            $result[$ccName]['payables'] += $item['value'] ?? 0;
        }

        // Processa a receber
        foreach ($receivables as $item) {
            $ccName = $item['cost_center'] ?? 'Sem centro de custo';
            if ($ccName === '—') $ccName = 'Sem centro de custo';
            
            if (!isset($result[$ccName])) {
                $result[$ccName] = ['payables' => 0, 'receivables' => 0];
            }
            $result[$ccName]['receivables'] += $item['value'] ?? 0;
        }

        // Ordena por total (maior primeiro)
        uasort($result, function($a, $b) {
            $totalA = abs($a['payables']) + abs($a['receivables']);
            $totalB = abs($b['payables']) + abs($b['receivables']);
            return $totalB <=> $totalA;
        });

        return array_slice($result, 0, 10, true);
    }
}
