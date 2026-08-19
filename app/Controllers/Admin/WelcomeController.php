<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class WelcomeController extends Controller
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
        $this->view('admin.welcome.index', [
            'user'    => Auth::user(),
            'profile' => User::find((int) Auth::id()),
            'flash'   => $this->getFlash(),
        ]);
    }
}
