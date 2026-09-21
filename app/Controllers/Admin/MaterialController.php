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
            'project_type' => $this->input('project_type', 'both'),
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
            'project_type' => $this->input('project_type', 'both'),
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
            'project_type' => $this->input('project_type', 'both'),
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
        $stmt = $db->prepare("INSERT INTO materials (code, name, specification, classification, unit_id, category_id, project_type, active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");

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

            // Processar tipo de projeto (se disponível na importação)
            $projectType = 'both'; // default
            if ($colMap['project_type'] !== null && isset($row[$colMap['project_type']])) {
                $ptRaw = mb_strtolower(trim((string) $row[$colMap['project_type']]));
                if (in_array($ptRaw, ['construcao', 'construction', 'construção'], true)) {
                    $projectType = 'construction';
                } elseif (in_array($ptRaw, ['reforma', 'renovation'], true)) {
                    $projectType = 'renovation';
                }
            }

            $stmt->execute([$code ?: null, $name, $spec, null, $unitId, $catId, $projectType]);
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
        $map = ['classification' => 0, 'code' => 1, 'name' => 2, 'unit' => 3, 'project_type' => null];
        
        if (!$header) return $map;

        foreach ($header as $i => $col) {
            $col = strtolower(trim($col));
            $col = preg_replace('/[^a-z]/', '', $col);
            
            if (str_contains($col, 'classific')) $map['classification'] = $i;
            elseif (str_contains($col, 'codigo') || str_contains($col, 'cdigo') || str_contains($col, 'code')) $map['code'] = $i;
            elseif (str_contains($col, 'descri') || str_contains($col, 'nome') || str_contains($col, 'name') || str_contains($col, 'insumo')) $map['name'] = $i;
            elseif (str_contains($col, 'unid') || str_contains($col, 'unit')) $map['unit'] = $i;
            elseif (str_contains($col, 'tipoprojeto') || str_contains($col, 'projecttype') || str_contains($col, 'tipo')) $map['project_type'] = $i;
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
     * Exportar TODOS os materiais em CSV para correção externa (ex: IA).
     *
     * O arquivo inclui a coluna "id" (chave imutável do material). Essa coluna
     * é o que torna a reimportação segura: a atualização é feita por id, então
     * nenhum vínculo/histórico (pedidos, transporte, estoque, preço, listas) é
     * perdido. NÃO edite/remova a coluna id no arquivo corrigido.
     */
    public function export(): void
    {
        $materials = \App\Core\Database::fetchAll(
            "SELECT m.id, m.code, m.name, m.specification,
                    m.category_id, mc.name AS category_name,
                    m.unit_id, mu.name AS unit_name, mu.abbreviation AS unit_abbr,
                    m.classification, m.project_type, m.active
             FROM materials m
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             ORDER BY m.name ASC"
        );

        $filename = 'materiais_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 para o Excel abrir acentuação corretamente
        fwrite($out, "\xEF\xBB\xBF");

        // Cabeçalho. Mantenha "id" na primeira coluna e não altere.
        fputcsv($out, [
            'id',
            'codigo',
            'nome',
            'especificacao',
            'classificacao',
            'unidade',
            'categoria',
            'tipo_projeto',
            'ativo',
        ], ';');

        // Mapear project_type para valores legíveis
        $projectTypeLabels = [
            'construction' => 'construcao',
            'renovation' => 'reforma',
            'both' => 'ambos',
        ];

        foreach ($materials as $m) {
            fputcsv($out, [
                $m['id'],
                $m['code'] ?? '',
                $m['name'] ?? '',
                $m['specification'] ?? '',
                $m['classification'] ?? '',
                // Unidade: exporta a abreviação (usada como chave na reimportação)
                $m['unit_abbr'] ?? ($m['unit_name'] ?? ''),
                // Categoria: nome legível (usada como chave na reimportação)
                $m['category_name'] ?? '',
                // Tipo de projeto
                $projectTypeLabels[$m['project_type'] ?? 'both'] ?? 'ambos',
                (int) $m['active'],
            ], ';');
        }

        fclose($out);
        exit;
    }

    /**
     * Reimportar o arquivo corrigido, ATUALIZANDO os materiais existentes por id.
     *
     * Regras de segurança:
     *  - Só faz UPDATE por materials.id (nunca DELETE, nunca altera o id).
     *  - Linhas sem id válido ou com id inexistente são ignoradas (reportadas).
     *  - Categoria/unidade novas são criadas automaticamente e vinculadas.
     *  - Como todas as tabelas dependentes usam material_id + snapshots próprios
     *    e não fazem cascade a partir de materials, nenhum vínculo é perdido.
     */
    public function reimportProcess(): void
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

        if (!in_array($ext, ['csv', 'txt'])) {
            $this->json(['error' => 'Formato não suportado. Reexporte/edite e salve como CSV (separado por ;).'], 400);
            return;
        }

        // Ler CSV normalizando quebras de linha e detectando separador
        $content = file_get_contents($file['tmp_name']);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        // Remover BOM UTF-8 se presente
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'reimport_');
        file_put_contents($tmpFile, $content);

        $firstLine = strtok($content, "\n");
        $separator = ';';
        if (substr_count($firstLine, ',') > substr_count($firstLine, ';')) {
            $separator = ',';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ';')) {
            $separator = "\t";
        }

        $handle = fopen($tmpFile, 'r');
        if (!$handle) {
            @unlink($tmpFile);
            $this->json(['error' => 'Não foi possível ler o arquivo.'], 400);
            return;
        }

        $header = fgetcsv($handle, 0, $separator, '"', '\\');
        if ($header) {
            $header = array_map(fn($h) => trim(preg_replace('/\s+/', ' ', (string) $h)), $header);
        }
        $colMap = $this->mapReimportColumns($header);

        if ($colMap['id'] === null) {
            fclose($handle);
            @unlink($tmpFile);
            $this->json(['error' => 'Coluna "id" não encontrada no arquivo. A reimportação exige a coluna id gerada pela exportação.'], 400);
            return;
        }

        // Caches de categoria/unidade (por nome/abreviação, em minúsculas)
        $catMap = [];
        foreach (MaterialCategory::all('name ASC') as $c) {
            $catMap[mb_strtolower(trim($c['name']))] = (int) $c['id'];
        }
        $unitMap = [];
        foreach (MeasurementUnit::all('name ASC') as $u) {
            if (!empty($u['abbreviation'])) $unitMap[mb_strtolower(trim($u['abbreviation']))] = (int) $u['id'];
            if (!empty($u['name'])) $unitMap[mb_strtolower(trim($u['name']))] = (int) $u['id'];
        }

        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $total = 0;
        $errors = [];

        $db = \App\Core\Database::getConnection();
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $separator, '"', '\\')) !== false) {
                // Ignorar linhas totalmente vazias
                if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $total++;

                $id = (int) trim((string) ($row[$colMap['id']] ?? 0));
                if ($id <= 0) { $skipped++; continue; }

                $existing = \App\Core\Database::fetch("SELECT id FROM materials WHERE id = ?", [$id]);
                if (!$existing) { $notFound++; continue; }

                $data = [];

                // Nome (obrigatório se a coluna existir)
                if ($colMap['name'] !== null) {
                    $name = trim((string) ($row[$colMap['name']] ?? ''));
                    if ($name === '') { $skipped++; continue; }
                    $data['name'] = $name;
                }

                if ($colMap['code'] !== null) {
                    $code = trim((string) ($row[$colMap['code']] ?? ''));
                    $data['code'] = $code !== '' ? $code : null;
                }

                if ($colMap['specification'] !== null) {
                    $spec = trim((string) ($row[$colMap['specification']] ?? ''));
                    $data['specification'] = $spec !== '' ? $spec : null;
                }

                if ($colMap['classification'] !== null) {
                    $cls = trim((string) ($row[$colMap['classification']] ?? ''));
                    $data['classification'] = $cls !== '' ? $cls : null;
                }

                // Categoria: resolve por nome; cria se não existir
                if ($colMap['category'] !== null) {
                    $catName = trim((string) ($row[$colMap['category']] ?? ''));
                    if ($catName !== '') {
                        $key = mb_strtolower($catName);
                        if (!isset($catMap[$key])) {
                            $catMap[$key] = MaterialCategory::create([
                                'name' => $catName,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                        $data['category_id'] = $catMap[$key];
                    } else {
                        $data['category_id'] = null;
                    }
                }

                // Unidade: resolve por abreviação/nome; cria se não existir
                if ($colMap['unit'] !== null) {
                    $unit = trim((string) ($row[$colMap['unit']] ?? ''));
                    if ($unit !== '') {
                        $key = mb_strtolower($unit);
                        if (!isset($unitMap[$key])) {
                            $newUnitId = MeasurementUnit::create([
                                'name' => $unit,
                                'abbreviation' => $unit,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                            $unitMap[$key] = $newUnitId;
                        }
                        $data['unit_id'] = $unitMap[$key];
                    } else {
                        $data['unit_id'] = null;
                    }
                }

                // Ativo (opcional)
                if ($colMap['active'] !== null) {
                    $activeRaw = mb_strtolower(trim((string) ($row[$colMap['active']] ?? '')));
                    $data['active'] = in_array($activeRaw, ['1', 'sim', 'ativo', 'true', 'yes'], true) ? 1 : 0;
                }

                // Tipo de projeto (opcional)
                if ($colMap['project_type'] !== null) {
                    $ptRaw = mb_strtolower(trim((string) ($row[$colMap['project_type']] ?? '')));
                    // Mapear valores legíveis para o enum
                    if (in_array($ptRaw, ['construcao', 'construction', 'construção'], true)) {
                        $data['project_type'] = 'construction';
                    } elseif (in_array($ptRaw, ['reforma', 'renovation'], true)) {
                        $data['project_type'] = 'renovation';
                    } elseif (in_array($ptRaw, ['ambos', 'both', 'todos'], true)) {
                        $data['project_type'] = 'both';
                    }
                    // Se não reconhecer, não altera (mantém o atual)
                }

                if (empty($data)) { $skipped++; continue; }

                // UPDATE seguro por id — nunca toca no id nem em outras tabelas
                \App\Core\Database::update('materials', $data, 'id = ?', [$id]);
                $updated++;
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            @unlink($tmpFile);
            $this->json(['error' => 'Falha na reimportação (nenhuma alteração aplicada): ' . $e->getMessage()], 500);
            return;
        }

        fclose($handle);
        @unlink($tmpFile);

        $this->json([
            'success'  => true,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'notFound' => $notFound,
            'total'    => $total,
        ]);
    }

    /**
     * Mapear colunas do CSV de reimportação (por header). Retorna índice ou null.
     */
    private function mapReimportColumns(?array $header): array
    {
        $map = [
            'id'             => null,
            'code'           => null,
            'name'           => null,
            'specification'  => null,
            'classification' => null,
            'unit'           => null,
            'category'       => null,
            'project_type'   => null,
            'active'         => null,
        ];

        if (!$header) return $map;

        foreach ($header as $i => $col) {
            $c = mb_strtolower(trim((string) $col));
            $c = preg_replace('/[^a-z0-9]/', '', $c);

            if ($c === 'id') $map['id'] = $i;
            elseif (str_contains($c, 'classific')) $map['classification'] = $i;
            elseif (str_contains($c, 'especific') || str_contains($c, 'specif')) $map['specification'] = $i;
            elseif (str_contains($c, 'categor')) $map['category'] = $i;
            elseif (str_contains($c, 'codigo') || str_contains($c, 'cdigo') || $c === 'code') $map['code'] = $i;
            elseif (str_contains($c, 'descri') || str_contains($c, 'nome') || $c === 'name' || str_contains($c, 'insumo')) $map['name'] = $i;
            elseif (str_contains($c, 'unid') || str_contains($c, 'unit')) $map['unit'] = $i;
            elseif (str_contains($c, 'tipoprojeto') || str_contains($c, 'projecttype') || str_contains($c, 'tipo')) $map['project_type'] = $i;
            elseif (str_contains($c, 'ativo') || str_contains($c, 'active') || str_contains($c, 'status')) $map['active'] = $i;
        }

        return $map;
    }

    /**
     * Diagnóstico de materiais e especificações para debug
     */
    public function diagnostics(): void
    {
        // 1. Especificações únicas
        $specs = \App\Core\Database::fetchAll(
            "SELECT 
                COALESCE(NULLIF(TRIM(specification), ''), 'Sem Especificação') AS spec_name,
                COUNT(*) as total
             FROM materials
             WHERE active = 1
             GROUP BY spec_name
             ORDER BY total DESC
             LIMIT 50"
        );

        // 2. Amostra de materiais
        $materials = \App\Core\Database::fetchAll(
            "SELECT m.id, m.name, m.specification, m.classification, m.category_id, m.unit_id,
                    mc.name as category_name, mu.abbreviation as unit_abbr
             FROM materials m
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE m.active = 1
             ORDER BY m.name ASC
             LIMIT 30"
        );

        // 3. Listas existentes
        $templates = \App\Core\Database::fetchAll(
            "SELECT t.id, t.name, t.description, t.active, t.created_at,
                    (SELECT COUNT(*) FROM material_template_items WHERE template_id = t.id) as item_count
             FROM material_templates t
             ORDER BY t.name ASC"
        );

        // 4. Categorias
        $categories = \App\Core\Database::fetchAll(
            "SELECT id, name FROM material_categories ORDER BY name ASC"
        );

        // 5. Totais
        $totalMaterials = \App\Core\Database::fetch("SELECT COUNT(*) as t FROM materials WHERE active = 1")['t'];
        $totalSpecs = count($specs);

        // 6. Nomes de listas que NÃO batem com nenhuma especificação atual.
        //    Se aparecer muita coisa aqui, as listas foram criadas por outra coluna (ex: classification).
        $orphanTemplates = \App\Core\Database::fetchAll(
            "SELECT t.name,
                    (SELECT COUNT(*) FROM material_template_items WHERE template_id = t.id) AS item_count
             FROM material_templates t
             WHERE t.name NOT IN (
                 SELECT DISTINCT COALESCE(NULLIF(TRIM(specification), ''), 'Sem Especificação')
                 FROM materials WHERE active = 1
             )
             ORDER BY t.name ASC"
        );

        $this->view('admin.materials.diagnostics', [
            'specs' => $specs,
            'materials' => $materials,
            'templates' => $templates,
            'categories' => $categories,
            'totalMaterials' => $totalMaterials,
            'totalSpecs' => $totalSpecs,
            'orphanTemplates' => $orphanTemplates,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Corrige a coluna `specification` dos materiais a partir do nome da
     * categoria vinculada (category_id → material_categories.name).
     *
     * Contexto: a reimportação não gravou a especificação corretamente e a
     * coluna ficou poluída com valores de dimensão (ex: "100mm", "BRANCA").
     * Como a categoria foi importada corretamente e, no arquivo de origem,
     * `especificacao` == `categoria` em ~99,8% das linhas, sincronizar a
     * specification pela categoria limpa os dados de forma segura.
     *
     * Acesso: GET /admin/materials/fix-specifications
     */
    public function fixSpecificationsFromCategory(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        @set_time_limit(0);

        echo "=== Corrigir specification a partir da categoria ===\n\n";

        // Distintos ANTES
        $before = \App\Core\Database::fetch(
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(specification), ''), '(vazio)')) t
             FROM materials WHERE active = 1"
        )['t'];
        echo "Especificacoes distintas ANTES: {$before}\n\n";

        // Diagnóstico: quantos materiais têm category_id nulo?
        $semCategoria = \App\Core\Database::fetch(
            "SELECT COUNT(*) t FROM materials WHERE active = 1 AND category_id IS NULL"
        )['t'];
        echo "Materiais SEM category_id: {$semCategoria}\n";

        $comCategoria = \App\Core\Database::fetch(
            "SELECT COUNT(*) t FROM materials WHERE active = 1 AND category_id IS NOT NULL"
        )['t'];
        echo "Materiais COM category_id: {$comCategoria}\n\n";

        // ESTRATÉGIA 1: quando há categoria vinculada, specification = nome da categoria
        $affected1 = \App\Core\Database::query(
            "UPDATE materials m
             JOIN material_categories mc ON m.category_id = mc.id
             SET m.specification = mc.name
             WHERE m.category_id IS NOT NULL
               AND (m.specification IS NULL OR TRIM(m.specification) <> mc.name)"
        )->rowCount();
        echo "Estrategia 1 (specification = categoria vinculada): {$affected1} atualizados\n";

        // ESTRATÉGIA 2: para materiais SEM categoria mas cujo specification atual
        // JÁ é o nome de uma categoria válida, vincular a category_id correspondente
        $affected2 = \App\Core\Database::query(
            "UPDATE materials m
             JOIN material_categories mc ON TRIM(m.specification) = mc.name
             SET m.category_id = mc.id
             WHERE m.category_id IS NULL
               AND TRIM(COALESCE(m.specification, '')) <> ''"
        )->rowCount();
        echo "Estrategia 2 (vincular categoria pelo nome da spec valida): {$affected2} atualizados\n";

        // Distintos DEPOIS
        $after = \App\Core\Database::fetch(
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(specification), ''), '(vazio)')) t
             FROM materials WHERE active = 1"
        )['t'];
        echo "\nEspecificacoes distintas DEPOIS: {$after}\n\n";

        // Listar as especificações que AINDA não são categorias válidas (o lixo restante)
        $restantes = \App\Core\Database::fetchAll(
            "SELECT COALESCE(NULLIF(TRIM(m.specification), ''), '(vazio)') AS spec, COUNT(*) AS qtd
             FROM materials m
             WHERE m.active = 1
               AND COALESCE(NULLIF(TRIM(m.specification), ''), '(vazio)') NOT IN (
                   SELECT name FROM material_categories
               )
             GROUP BY spec
             ORDER BY qtd DESC"
        );
        echo "Especificacoes que NAO sao categorias validas (lixo restante): " . count($restantes) . "\n";
        foreach ($restantes as $r) {
            echo "  " . str_pad((string) $r['qtd'], 5) . " | " . $r['spec'] . "\n";
        }

        // Total de categorias cadastradas
        $totalCats = \App\Core\Database::fetch("SELECT COUNT(*) t FROM material_categories")['t'];
        echo "\nTotal de CATEGORIAS cadastradas: {$totalCats}\n";

        // Distribuição real de specification (o que vira lista)
        echo "\n=== Distribuicao atual de specification (o que gera as listas) ===\n";
        $dist = \App\Core\Database::fetchAll(
            "SELECT COALESCE(NULLIF(TRIM(specification), ''), '(vazio)') AS spec, COUNT(*) AS qtd
             FROM materials WHERE active = 1
             GROUP BY spec ORDER BY qtd DESC"
        );
        echo "Total distintas: " . count($dist) . "\n";
        foreach ($dist as $d) {
            echo "  " . str_pad((string) $d['qtd'], 5) . " | " . $d['spec'] . "\n";
        }

        echo "\n=== FIM ===\n";
    }

    /**
     * Executa a recriação de listas DIRETAMENTE e imprime o resultado em texto puro.
     * Ferramenta de diagnóstico: sem modal, sem confirmação, para isolar o problema.
     * Acesso: GET /admin/materials/rebuild-lists
     */
    public function rebuildListsDebug(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        @set_time_limit(0);

        $db = \App\Core\Database::getConnection();

        echo "=== DEBUG: Recriação de Listas ===\n\n";

        // Estado ANTES
        $before = \App\Core\Database::fetch("SELECT COUNT(*) t FROM material_templates")['t'];
        echo "Listas ANTES: {$before}\n";

        $specifications = \App\Core\Database::fetchAll(
            "SELECT DISTINCT COALESCE(NULLIF(TRIM(specification), ''), 'Sem Especificação') AS spec_name
             FROM materials WHERE active = 1 ORDER BY spec_name ASC"
        );
        echo "Especificacoes distintas encontradas: " . count($specifications) . "\n\n";

        $listsCreated = 0;
        $itemsCreated = 0;

        $db->beginTransaction();
        try {
            \App\Core\Database::query("DELETE FROM material_template_items");
            \App\Core\Database::query("DELETE FROM material_templates");
            echo "Tabelas limpas.\n";

            $actor = 'Diagnostico';
            $now = date('Y-m-d H:i:s');

            $stmtTemplate = $db->prepare(
                "INSERT INTO material_templates (name, description, active, created_by_name, created_at)
                 VALUES (?, ?, 1, ?, ?)"
            );

            foreach ($specifications as $spec) {
                $specName = $spec['spec_name'];
                $materials = \App\Core\Database::fetchAll(
                    "SELECT m.*, mu.abbreviation AS unit_abbr
                     FROM materials m
                     LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                     WHERE m.active = 1
                       AND COALESCE(NULLIF(TRIM(m.specification), ''), 'Sem Especificação') = ?
                     ORDER BY m.name ASC",
                    [$specName]
                );
                if (empty($materials)) continue;

                $stmtTemplate->execute([
                    $specName,
                    'Lista automatica: ' . $specName,
                    $actor,
                    $now,
                ]);
                $templateId = (int) $db->lastInsertId();
                $listsCreated++;

                $sortOrder = 0;
                foreach (array_chunk($materials, 200) as $chunk) {
                    $ph = [];
                    $vals = [];
                    foreach ($chunk as $mat) {
                        $ph[] = "(?, ?, ?, ?, ?, ?, ?, 1, ?, 1, ?)";
                        array_push($vals,
                            $templateId, (int) $mat['id'], $mat['name'],
                            $mat['specification'] ?? $specName,
                            $mat['classification'] ?? null,
                            $mat['unit_abbr'] ?? null,
                            $mat['project_type'] ?? 'both',
                            $sortOrder++, $now
                        );
                        $itemsCreated++;
                    }
                    $sql = "INSERT INTO material_template_items
                                (template_id, material_id, material_name, specification, classification, unit, project_type, default_quantity, sort_order, active, created_at)
                            VALUES " . implode(', ', $ph);
                    $db->prepare($sql)->execute($vals);
                }
            }

            $db->commit();
            echo "\nCOMMIT OK.\n";
        } catch (\Throwable $e) {
            $db->rollBack();
            echo "\n!!! ERRO (rollback): " . $e->getMessage() . "\n";
            echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
            return;
        }

        $after = \App\Core\Database::fetch("SELECT COUNT(*) t FROM material_templates")['t'];
        echo "\nListas DEPOIS: {$after}\n";
        echo "Listas criadas: {$listsCreated}\n";
        echo "Itens criados: {$itemsCreated}\n";
        echo "\n=== FIM ===\n";
    }
}
