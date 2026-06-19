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
            'code' => trim($this->input('code', '')) ?: null,
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
            'code' => trim($this->input('code', '')) ?: null,
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
        $action = $this->input('action', 'deactivate');

        if ($action === 'permanent' && \App\Core\Auth::isSuperAdmin()) {
            Material::deleteById($id);
            $this->setFlash('success', 'Material excluído permanentemente!');
        } else {
            Material::updateById($id, ['active' => 0]);
            $this->setFlash('success', 'Material desativado com sucesso!');
        }

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
            'code' => trim($this->input('code', '')) ?: null,
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
     * Tela de importação de materiais
     */
    public function import(): void
    {
        $this->view('admin.materials.import', [
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar importação de materiais (AJAX, em lotes)
     */
    public function importProcess(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'])) {
            $this->json(['error' => 'Formato não suportado. Use CSV, TXT ou XLSX.'], 400);
            return;
        }

        $rows = [];

        if ($ext === 'csv' || $ext === 'txt') {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $this->json(['error' => 'Não foi possível ler o arquivo.'], 400);
                return;
            }

            $header = null;
            while (($line = fgetcsv($handle, 0, ';')) !== false) {
                if (!$header) {
                    // Tentar detectar separador
                    if (count($line) <= 1) {
                        rewind($handle);
                        $header = fgetcsv($handle, 0, ',');
                        continue;
                    }
                    $header = $line;
                    continue;
                }
                if (count($line) >= 3) {
                    $rows[] = $line;
                }
            }
            fclose($handle);

            // Se não conseguiu com ; nem ,, tentar tab
            if (empty($rows)) {
                $handle = fopen($file['tmp_name'], 'r');
                $header = null;
                while (($line = fgetcsv($handle, 0, "\t")) !== false) {
                    if (!$header) { $header = $line; continue; }
                    if (count($line) >= 3) $rows[] = $line;
                }
                fclose($handle);
            }
        } else {
            // XLSX - ler como CSV exportado (SimpleXLSX não disponível sem Composer)
            // Converter via texto simples - instruir usuario a salvar como CSV
            $this->json(['error' => 'Para arquivos XLSX, salve como CSV (separado por ;) e tente novamente.'], 400);
            return;
        }

        if (empty($rows)) {
            $this->json(['error' => 'Nenhum dado encontrado no arquivo. Verifique o formato.'], 400);
            return;
        }

        // Mapear colunas baseado no header
        $colMap = $this->mapColumns($header);
        
        // Processar em lotes de 100
        $imported = 0;
        $skipped = 0;
        $batchSize = 100;
        $total = count($rows);

        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare("INSERT INTO materials (code, name, specification, classification, unit_id, category_id, active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");

        // Cache de categorias e unidades
        $categories = MaterialCategory::all('name ASC');
        $units = MeasurementUnit::all('name ASC');
        $catMap = [];
        foreach ($categories as $c) $catMap[strtolower(trim($c['name']))] = $c['id'];
        $unitMap = [];
        foreach ($units as $u) {
            $unitMap[strtolower(trim($u['abbreviation']))] = $u['id'];
            $unitMap[strtolower(trim($u['name']))] = $u['id'];
        }

        for ($i = 0; $i < $total; $i++) {
            $row = $rows[$i];
            
            $classification = trim($row[$colMap['classification']] ?? '');
            $code = trim($row[$colMap['code']] ?? '');
            $name = trim($row[$colMap['name']] ?? '');
            $unit = trim($row[$colMap['unit']] ?? '');

            if (empty($name)) { $skipped++; continue; }

            // Mapear classificação para category_id
            $catId = null;
            $spec = $classification;
            $clsLower = strtolower($classification);
            if (isset($catMap[$clsLower])) {
                $catId = $catMap[$clsLower];
            } else if (!empty($classification)) {
                // Criar nova categoria
                $newCatId = MaterialCategory::create(['name' => $classification, 'created_at' => date('Y-m-d H:i:s')]);
                $catMap[$clsLower] = $newCatId;
                $catId = $newCatId;
            }

            // Mapear unidade
            $unitId = null;
            $unitLower = strtolower($unit);
            if (isset($unitMap[$unitLower])) {
                $unitId = $unitMap[$unitLower];
            } else if (!empty($unit)) {
                $newUnitId = MeasurementUnit::create(['name' => $unit, 'abbreviation' => $unit, 'created_at' => date('Y-m-d H:i:s')]);
                $unitMap[$unitLower] = $newUnitId;
                $unitId = $newUnitId;
            }

            // Verificar duplicado por código
            if (!empty($code)) {
                $existing = \App\Core\Database::fetch("SELECT id FROM materials WHERE code = ?", [$code]);
                if ($existing) { $skipped++; continue; }
            }

            $stmt->execute([$code ?: null, $name, $spec, null, $unitId, $catId]);
            $imported++;
        }

        $this->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $total,
        ]);
    }

    /**
     * Mapear colunas do CSV baseado nos headers
     */
    private function mapColumns(?array $header): array
    {
        $map = ['classification' => 0, 'code' => 1, 'name' => 2, 'unit' => 3];
        
        if (!$header) return $map;

        foreach ($header as $i => $col) {
            $col = strtolower(trim($col));
            $col = preg_replace('/[^a-z]/', '', $col);
            
            if (str_contains($col, 'classific')) $map['classification'] = $i;
            elseif (str_contains($col, 'codigo') || str_contains($col, 'cdigo') || str_contains($col, 'code')) $map['code'] = $i;
            elseif (str_contains($col, 'descri') || str_contains($col, 'nome') || str_contains($col, 'name') || str_contains($col, 'insumo')) $map['name'] = $i;
            elseif (str_contains($col, 'unid') || str_contains($col, 'unit')) $map['unit'] = $i;
        }

        return $map;
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
