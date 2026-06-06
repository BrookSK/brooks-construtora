<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Newsletter;
use App\Models\Magazine;
use App\Models\User;

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

        $this->view('admin.dashboard.index', $data);
    }
}
