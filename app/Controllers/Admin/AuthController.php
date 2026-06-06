<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('/admin/dashboard');
            return;
        }

        $flash = $this->getFlash();
        $this->view('admin.auth.login', ['flash' => $flash]);
    }

    public function authenticate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/login');
            return;
        }

        $email = $this->input('email');
        $password = $this->input('password');

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Preencha todos os campos.');
            $this->redirect('/admin/login');
            return;
        }

        if (Auth::attempt($email, $password)) {
            $this->redirect('/admin/dashboard');
        } else {
            $this->setFlash('error', 'E-mail ou senha inválidos.');
            $this->redirect('/admin/login');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
