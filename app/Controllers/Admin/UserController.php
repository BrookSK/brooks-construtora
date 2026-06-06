<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('users')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $users = User::all('name ASC');

        $this->view('admin.users.index', [
            'users' => $users,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->view('admin.users.form', [
            'user' => Auth::user(),
            'editUser' => null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
            return;
        }

        $name = trim($this->input('name'));
        $email = trim($this->input('email'));
        $password = $this->input('password');
        $role = $this->input('role');

        // Validações
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/admin/users/create');
            return;
        }

        // Verifica se email já existe
        $existing = User::whereFirst('email', $email);
        if ($existing) {
            $this->setFlash('error', 'Este e-mail já está cadastrado.');
            $this->redirect('/admin/users/create');
            return;
        }

        // Apenas super_admin pode criar outros super_admin
        if ($role === 'super_admin' && !Auth::isSuperAdmin()) {
            $this->setFlash('error', 'Sem permissão para criar este tipo de usuário.');
            $this->redirect('/admin/users/create');
            return;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Usuário criado com sucesso!');
        $this->redirect('/admin/users');
    }

    public function edit(string $id = ''): void
    {
        $id = (int) ($id ?: $this->input('id'));
        $editUser = User::find($id);

        if (!$editUser) {
            $this->setFlash('error', 'Usuário não encontrado.');
            $this->redirect('/admin/users');
            return;
        }

        $this->view('admin.users.form', [
            'user' => Auth::user(),
            'editUser' => $editUser,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
            return;
        }

        $id = (int) $this->input('id');
        $editUser = User::find($id);

        if (!$editUser) {
            $this->setFlash('error', 'Usuário não encontrado.');
            $this->redirect('/admin/users');
            return;
        }

        $data = [
            'name' => trim($this->input('name')),
            'email' => trim($this->input('email')),
            'role' => $this->input('role'),
            'active' => $this->input('active', 1),
        ];

        $password = $this->input('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Apenas super_admin pode alterar role para super_admin
        if ($data['role'] === 'super_admin' && !Auth::isSuperAdmin()) {
            $this->setFlash('error', 'Sem permissão para definir este tipo de usuário.');
            $this->redirect('/admin/users/edit/' . $id);
            return;
        }

        User::updateById($id, $data);

        $this->setFlash('success', 'Usuário atualizado com sucesso!');
        $this->redirect('/admin/users');
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/users');
            return;
        }

        $id = (int) $this->input('id');

        // Não permite excluir a si mesmo
        if ($id === Auth::id()) {
            $this->setFlash('error', 'Você não pode excluir seu próprio usuário.');
            $this->redirect('/admin/users');
            return;
        }

        User::deleteById($id);
        $this->setFlash('success', 'Usuário excluído com sucesso!');
        $this->redirect('/admin/users');
    }
}
