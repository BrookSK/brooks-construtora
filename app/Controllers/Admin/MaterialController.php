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
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 50;
        $search = trim($this->input('q', ''));
        
        if (!empty($search)) {
            $where = "m.active = 1 AND (m.name LIKE ? OR m.specification LIKE ? OR m.code LIKE ? OR m.classification LIKE ?)";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
            $total = (int) \App\Core\Database::fetch("SELECT COUNT(*) as total FROM materials m WHERE {$where}", $params)['total'];
            $offset = ($page - 1) * $perPage;
            $materials = \App\Core\Database::fetchAll(
                "SELECT m.*, mc.name as category_name, mu.name as unit_name, mu.abbreviation as unit_abbr 
                 FROM materials m
                 LEFT JOIN material_categories mc ON m.category_id = mc.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 WHERE {$where} ORDER BY m.name ASC LIMIT {$perPage} OFFSET {$offset}",
                $params
            );
        } else {
            $total = Material::count();
            $offset = ($page - 1) * $perPage;
            $materials = \App\Core\Database::fetchAll(
                "SELECT m.*, mc.name as category_name, mu.name as unit_name, mu.abbreviation as unit_abbr 
                 FROM materials m
                 LEFT JOIN material_categories mc ON m.category_id = mc.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 ORDER BY m.name ASC LIMIT {$perPage} OFFSET {$offset}"
            );
        }

        $totalPages = ceil($total / $perPage);
        $categories = MaterialCategory::all('name ASC');
        $units = MeasurementUnit::all('name ASC');

        $this->view('admin.materials.index', [
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
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
            $content = file_get_contents($file['tmp_name']);
            // Normalizar quebras de linha
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            // Salvar conteúdo normalizado em arquivo temporário
            $tmpFile = tempnam(sys_get_temp_dir(), 'import_');
            file_put_contents($tmpFile, $content);
            
            // Detectar separador pela primeira linha
            $firstLine = strtok($content, "\n");
            $separator = ',';
            if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                $separator = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $separator = "\t";
            }

            $handle = fopen($tmpFile, 'r');
            if (!$handle) {
                $this->json(['error' => 'Não foi possível ler o arquivo.'], 400);
                return;
            }

            $header = fgetcsv($handle, 0, $separator, '"', '\\');
            // Limpar header (remover quebras de linha internas, espaços extras)
            if ($header) {
                $header = array_map(function($h) {
                    return trim(preg_replace('/\s+/', ' ', $h));
                }, $header);
            }

            while (($line = fgetcsv($handle, 0, $separator, '"', '\\')) !== false) {
                if (count($line) >= 3 && !empty(trim($line[0] ?? ''))) {
                    $rows[] = $line;
                }
            }
            fclose($handle);
            @unlink($tmpFile);
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

    /**
     * Upload da imagem/foto de um material (AJAX).
     * Aceita arquivo enviado por selecao ou captura de camera (mesmo campo file).
     * Segue o padrao de upload de SettingsController::uploadAvatar.
     */
    public function uploadImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $materialId = (int) $this->input('id', 0);
        if ($materialId <= 0 || !Material::find($materialId)) {
            $this->json(['error' => 'Material não encontrado.'], 404);
            return;
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file         = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($file['type'], $allowedTypes, true)) {
            $this->json(['error' => 'Tipo não permitido. Use JPG, PNG, WEBP ou GIF.'], 400);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['error' => 'Arquivo muito grande. Máximo 5 MB.'], 400);
            return;
        }

        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        $filename  = 'material_' . $materialId . '_' . time() . '.' . $ext;
        $uploadDir = ROOT_PATH . '/public/uploads/materials/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->json(['error' => 'Erro ao salvar arquivo.'], 500);
            return;
        }

        // Remove imagem anterior, se houver
        $current = Material::find($materialId);
        if (!empty($current['image_path']) && file_exists(ROOT_PATH . '/public' . $current['image_path'])) {
            @unlink(ROOT_PATH . '/public' . $current['image_path']);
        }

        $imageUrl = '/uploads/materials/' . $filename;

        try {
            Material::updateById($materialId, ['image_path' => $imageUrl]);
        } catch (\PDOException $e) {
            // Coluna image_path pode nao existir — executa a migration 036 e tenta de novo.
            if ($this->isMissingColumn($e)) {
                $this->runImageMigration();
                Material::updateById($materialId, ['image_path' => $imageUrl]);
            } else {
                throw $e;
            }
        }

        $this->json(['success' => true, 'url' => $imageUrl]);
    }

    /**
     * Remove a imagem de um material (AJAX), mantendo os demais dados.
     */
    public function removeImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $materialId = (int) $this->input('id', 0);
        $current = Material::find($materialId);
        if (!$current) {
            $this->json(['error' => 'Material não encontrado.'], 404);
            return;
        }

        if (!empty($current['image_path']) && file_exists(ROOT_PATH . '/public' . $current['image_path'])) {
            @unlink(ROOT_PATH . '/public' . $current['image_path']);
        }

        try {
            Material::updateById($materialId, ['image_path' => null]);
        } catch (\PDOException $e) {
            if (!$this->isMissingColumn($e)) {
                throw $e;
            }
            // Coluna nao existe — nada a remover.
        }

        $this->json(['success' => true]);
    }

    /**
     * Detecta erro de coluna inexistente (para acionar a migration lazy).
     */
    private function isMissingColumn(\PDOException $e): bool
    {
        $msg = $e->getMessage();
        return stripos($msg, 'Unknown column') !== false
            || stripos($msg, "doesn't exist") !== false;
    }

    /**
     * Executa a migration 036 (adiciona image_path) de forma idempotente.
     */
    private function runImageMigration(): void
    {
        $migrationFile = ROOT_PATH . '/database/migrations/036_add_image_to_materials.sql';
        if (!file_exists($migrationFile)) {
            return;
        }
        $sql = file_get_contents($migrationFile);
        $sql = preg_replace('/--[^\n]*/', '', $sql);
        $pdo = \App\Core\Database::getConnection();
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            try { $pdo->exec($stmt); } catch (\PDOException $ignore) {}
        }
    }
}
