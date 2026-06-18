<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('suppliers')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $suppliers = Supplier::all('name ASC');

        $this->view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->view('admin.suppliers.form', [
            'supplier' => null,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/suppliers');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome do fornecedor é obrigatório.');
            $this->redirect('/admin/suppliers/create');
            return;
        }

        Supplier::create([
            'name' => $name,
            'cnpj' => trim($this->input('cnpj', '')),
            'email' => trim($this->input('email', '')),
            'phone' => trim($this->input('phone', '')),
            'contact_person' => trim($this->input('contact_person', '')),
            'address' => trim($this->input('address', '')),
            'notes' => trim($this->input('notes', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Fornecedor cadastrado com sucesso!');
        $this->redirect('/admin/suppliers');
    }

    public function edit(int $id = 0): void
    {
        $id = $id ?: (int) $this->input('id', 0);
        $supplier = Supplier::find($id);

        if (!$supplier) {
            $this->setFlash('error', 'Fornecedor não encontrado.');
            $this->redirect('/admin/suppliers');
            return;
        }

        $this->view('admin.suppliers.form', [
            'supplier' => $supplier,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/suppliers');
            return;
        }

        $id = (int) $this->input('id', 0);
        $supplier = Supplier::find($id);

        if (!$supplier) {
            $this->setFlash('error', 'Fornecedor não encontrado.');
            $this->redirect('/admin/suppliers');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome do fornecedor é obrigatório.');
            $this->redirect('/admin/suppliers/edit/' . $id);
            return;
        }

        Supplier::updateById($id, [
            'name' => $name,
            'cnpj' => trim($this->input('cnpj', '')),
            'email' => trim($this->input('email', '')),
            'phone' => trim($this->input('phone', '')),
            'contact_person' => trim($this->input('contact_person', '')),
            'address' => trim($this->input('address', '')),
            'notes' => trim($this->input('notes', '')),
        ]);

        $this->setFlash('success', 'Fornecedor atualizado com sucesso!');
        $this->redirect('/admin/suppliers');
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/suppliers');
            return;
        }

        $id = (int) $this->input('id', 0);
        Supplier::updateById($id, ['active' => 0]);

        $this->setFlash('success', 'Fornecedor desativado com sucesso!');
        $this->redirect('/admin/suppliers');
    }

    /**
     * API para busca inline (AJAX)
     */
    public function search(): void
    {
        $term = trim($this->input('q', ''));
        $suppliers = empty($term) ? Supplier::allActive() : Supplier::search($term);
        $this->json(['suppliers' => $suppliers]);
    }

    /**
     * API para cadastro rápido inline (AJAX)
     */
    public function quickStore(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->json(['error' => 'Nome é obrigatório.'], 400);
            return;
        }

        $id = Supplier::create([
            'name' => $name,
            'cnpj' => trim($this->input('cnpj', '')),
            'email' => trim($this->input('email', '')),
            'phone' => trim($this->input('phone', '')),
            'contact_person' => trim($this->input('contact_person', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $supplier = Supplier::find($id);
        $this->json(['success' => true, 'supplier' => $supplier]);
    }
}
