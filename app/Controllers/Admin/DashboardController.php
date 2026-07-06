<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Newsletter;
use App\Models\Magazine;
use App\Models\User;
use App\Models\PurchaseOrder;

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
        // Usuário EPI não precisa do Dashboard — redireciona direto pra área de EPI
        if (Auth::role() === 'epi') {
            $this->redirect('/registro-de-entrega');
            return;
        }

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

        $this->view('admin.dashboard.index', $data);
    }
}
