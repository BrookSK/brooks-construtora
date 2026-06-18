<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;

class MaterialController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('materials')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $materials = Material::allWithRelations();
        $categories = MaterialCategory::all('name ASC');
        $units = MeasurementUnit::all('name ASC');

        $this->view('admin.materials.index', [
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/materials');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome do material é obrigatório.');
            $this->redirect('/admin/materials');
            return;
        }

        Material::create([
            'name' => $name,
            'specification' => trim($this->input('specification', '')),
            'category_id' => (int) $this->input('category_id') ?: null,
            'unit_id' => (int) $this->input('unit_id') ?: null,
            'classification' => trim($this->input('classification', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Material cadastrado com sucesso!');
        $this->redirect('/admin/materials');
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/materials');
            return;
        }

        $id = (int) $this->input('id', 0);
        $material = Material::find($id);

        if (!$material) {
            $this->setFlash('error', 'Material não encontrado.');
            $this->redirect('/admin/materials');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'O nome do material é obrigatório.');
            $this->redirect('/admin/materials');
            return;
        }

        Material::updateById($id, [
            'name' => $name,
            'specification' => trim($this->input('specification', '')),
            'category_id' => (int) $this->input('category_id') ?: null,
            'unit_id' => (int) $this->input('unit_id') ?: null,
            'classification' => trim($this->input('classification', '')),
        ]);

        $this->setFlash('success', 'Material atualizado com sucesso!');
        $this->redirect('/admin/materials');
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/materials');
            return;
        }

        $id = (int) $this->input('id', 0);
        Material::updateById($id, ['active' => 0]);

        $this->setFlash('success', 'Material desativado com sucesso!');
        $this->redirect('/admin/materials');
    }

    /**
     * API para busca inline (AJAX)
     */
    public function search(): void
    {
        $term = trim($this->input('q', ''));
        $materials = empty($term) ? Material::allActive() : Material::search($term);
        $this->json(['materials' => $materials]);
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

        $id = Material::create([
            'name' => $name,
            'specification' => trim($this->input('specification', '')),
            'category_id' => (int) $this->input('category_id') ?: null,
            'unit_id' => (int) $this->input('unit_id') ?: null,
            'classification' => trim($this->input('classification', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $material = Material::find($id);
        $this->json(['success' => true, 'material' => $material]);
    }

    /**
     * API para listar categorias (AJAX)
     */
    public function categories(): void
    {
        $categories = MaterialCategory::all('name ASC');
        $this->json(['categories' => $categories]);
    }

    /**
     * API para listar unidades (AJAX)
     */
    public function units(): void
    {
        $units = MeasurementUnit::all('name ASC');
        $this->json(['units' => $units]);
    }

    /**
     * API para cadastro rápido de categoria/especificação (AJAX)
     */
    public function quickStoreCategory(): void
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

        $id = MaterialCategory::create([
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $category = MaterialCategory::find($id);
        $this->json(['success' => true, 'category' => $category]);
    }

    /**
     * API para cadastro rápido de unidade de medida (AJAX)
     */
    public function quickStoreUnit(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $name = trim($this->input('name', ''));
        $abbreviation = trim($this->input('abbreviation', ''));

        if (empty($name) || empty($abbreviation)) {
            $this->json(['error' => 'Nome e abreviação são obrigatórios.'], 400);
            return;
        }

        $id = MeasurementUnit::create([
            'name' => $name,
            'abbreviation' => $abbreviation,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $unit = MeasurementUnit::find($id);
        $this->json(['success' => true, 'unit' => $unit]);
    }
}
