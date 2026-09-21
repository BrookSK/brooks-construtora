<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\MaterialTemplate;
use App\Models\MaterialTemplateItem;

/**
 * Gerenciamento das Listas de Materiais Pré-definidas (templates).
 *
 * Acesso restrito a:
 *   - Administradores do sistema (super_admin / admin), OU
 *   - Usuários com PIN de permissão "completo" (role 'all').
 *
 * Essas listas são aplicadas na criação de um novo pedido: ao
 * selecionar uma lista, os materiais vêm pré-carregados com
 * quantidades padrão, e o gerente ativa/desativa e ajusta antes
 * de enviar — sempre por sua conta e risco.
 *
 * Rotas (prefixo /admin/material-lists):
 *   ''            -> index      (tela de gerenciamento)
 *   /store        -> store      (criar lista)
 *   /update       -> update     (editar cabeçalho da lista)
 *   /delete       -> delete     (excluir/desativar lista)
 *   /items        -> items      (AJAX: itens de uma lista em JSON)
 *   /store-item   -> storeItem  (AJAX: adicionar item)
 *   /update-item  -> updateItem (AJAX: editar item)
 *   /delete-item  -> deleteItem (AJAX: remover item)
 */
class MaterialTemplateController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
        if (!$this->canAccess()) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Acesso permitido para admin de sistema ou PIN com role 'all' (completo).
     */
    private function canAccess(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        return ($_SESSION['pin_user_role'] ?? null) === 'all';
    }

    /**
     * Tela de gerenciamento das listas.
     */
    public function index(): void
    {
        $templates = MaterialTemplate::allWithCount();

        // Carrega os itens de cada lista para edição inline
        $itemsByTemplate = [];
        foreach ($templates as $t) {
            $itemsByTemplate[$t['id']] = MaterialTemplateItem::forTemplate((int) $t['id'], false);
        }

        $this->view('admin.material_lists.index', [
            'templates'       => $templates,
            'itemsByTemplate' => $itemsByTemplate,
            'materials'       => Material::allActive(),
            'categories'      => MaterialCategory::all('name ASC'),
            'units'           => MeasurementUnit::all('name ASC'),
            'user'            => Auth::user(),
            'flash'           => $this->getFlash(),
            'pageTitle'       => 'Listas de Materiais',
            'currentPage'     => 'material_lists',
        ]);
    }

    /**
     * Criar nova lista.
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/material-lists');
            return;
        }

        $name = trim($this->input('name', ''));
        if ($name === '') {
            $this->setFlash('error', 'O nome da lista é obrigatório.');
            $this->redirect('/admin/material-lists');
            return;
        }

        $id = MaterialTemplate::create([
            'name'            => $name,
            'description'     => trim($this->input('description', '')) ?: null,
            'active'          => 1,
            'created_by_name' => $this->currentActorName(),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Lista criada com sucesso!');
        $this->redirect('/admin/material-lists?open=' . $id);
    }

    /**
     * Editar cabeçalho (nome/descrição) da lista.
     */
    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/material-lists');
            return;
        }

        $id = (int) $this->input('id', 0);
        if (!MaterialTemplate::find($id)) {
            $this->setFlash('error', 'Lista não encontrada.');
            $this->redirect('/admin/material-lists');
            return;
        }

        $name = trim($this->input('name', ''));
        if ($name === '') {
            $this->setFlash('error', 'O nome da lista é obrigatório.');
            $this->redirect('/admin/material-lists');
            return;
        }

        MaterialTemplate::updateById($id, [
            'name'        => $name,
            'description' => trim($this->input('description', '')) ?: null,
        ]);

        $this->setFlash('success', 'Lista atualizada com sucesso!');
        $this->redirect('/admin/material-lists?open=' . $id);
    }

    /**
     * Excluir a lista (e seus itens) permanentemente.
     */
    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/material-lists');
            return;
        }

        $id = (int) $this->input('id', 0);
        if ($id) {
            MaterialTemplateItem::deleteByTemplate($id);
            MaterialTemplate::deleteById($id);
            $this->setFlash('success', 'Lista excluída com sucesso!');
        }

        $this->redirect('/admin/material-lists');
    }

    /**
     * Recriar todas as listas a partir das categorias de materiais.
     * Apaga todas as listas existentes e cria novas baseadas nas categorias.
     */
    public function recreate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/material-lists');
            return;
        }

        // Confirmar ação destrutiva
        $confirm = $this->input('confirm', '');
        if ($confirm !== 'RECRIAR') {
            $this->setFlash('error', 'Confirmação inválida. Digite "RECRIAR" para confirmar.');
            $this->redirect('/admin/material-lists');
            return;
        }

        // 1. Apagar todos os itens e templates existentes
        $existingTemplates = MaterialTemplate::all();
        foreach ($existingTemplates as $t) {
            MaterialTemplateItem::deleteByTemplate((int) $t['id']);
        }
        Database::query("DELETE FROM material_templates");

        // 2. Buscar todas as categorias e seus materiais
        $categories = MaterialCategory::all('name ASC');
        $listsCreated = 0;
        $itemsCreated = 0;

        foreach ($categories as $cat) {
            $catId = (int) $cat['id'];
            $catName = $cat['name'];

            // Buscar materiais ativos desta categoria
            $materials = Database::fetchAll(
                "SELECT m.*, mu.abbreviation AS unit_abbr
                 FROM materials m
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 WHERE m.category_id = ? AND m.active = 1
                 ORDER BY m.name ASC",
                [$catId]
            );

            if (empty($materials)) {
                continue; // Pula categorias sem materiais ativos
            }

            // Criar a lista (template) para esta categoria
            $templateId = MaterialTemplate::create([
                'name'            => $catName,
                'description'     => 'Lista criada automaticamente a partir da categoria "' . $catName . '"',
                'active'          => 1,
                'created_by_name' => $this->currentActorName(),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $listsCreated++;

            // Adicionar os materiais como itens da lista
            $sortOrder = 0;
            foreach ($materials as $mat) {
                MaterialTemplateItem::create([
                    'template_id'      => $templateId,
                    'material_id'      => (int) $mat['id'],
                    'material_name'    => $mat['name'],
                    'specification'    => $mat['specification'] ?? $catName,
                    'classification'   => $mat['classification'] ?? null,
                    'unit'             => $mat['unit_abbr'] ?? null,
                    'project_type'     => $mat['project_type'] ?? 'both',
                    'default_quantity' => 1,
                    'sort_order'       => $sortOrder++,
                    'active'           => 1,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
                $itemsCreated++;
            }
        }

        $this->setFlash('success', "Listas recriadas com sucesso! {$listsCreated} listas criadas com {$itemsCreated} itens no total.");
        $this->redirect('/admin/material-lists');
    }

    // ─────────────────────────────────────────────────────────────
    // Endpoints AJAX (JSON)
    // ─────────────────────────────────────────────────────────────

    /**
     * AJAX: itens ativos de uma lista, prontos para pré-preencher
     * o formulário de novo pedido.
     *
     * Parâmetros opcionais:
     *   - obra_type: se informado ('construction' ou 'renovation'), filtra
     *     os itens para mostrar apenas materiais compatíveis com o tipo da obra.
     */
    public function items(): void
    {
        $templateId = (int) $this->input('template_id', 0);
        $template = MaterialTemplate::find($templateId);
        if (!$template) {
            $this->json(['error' => 'Lista não encontrada.'], 404);
            return;
        }

        $rows = MaterialTemplateItem::forTemplate($templateId, true);

        // Filtrar por tipo de projeto da obra, se informado
        $obraType = $this->input('obra_type', '');
        if ($obraType === 'construction' || $obraType === 'renovation') {
            $rows = array_filter($rows, function ($item) use ($obraType) {
                $itemType = $item['project_type'] ?? 'both';
                // Inclui se for 'both' ou se coincidir com o tipo da obra
                return $itemType === 'both' || $itemType === $obraType;
            });
            $rows = array_values($rows); // Re-indexar
        }

        $items = array_map(function ($i) {
            return [
                'id'             => $i['material_id'] !== null ? (int) $i['material_id'] : null,
                'item_id'        => (int) $i['id'],
                'name'           => $i['material_name'],
                'specification'  => $i['specification'] ?? ($i['category_name'] ?? ''),
                'classification' => $i['classification'] ?? '',
                'unit'           => $i['unit_abbr'] ?? $i['unit'] ?? '',
                'quantity'       => (float) $i['default_quantity'],
            ];
        }, $rows);

        $this->json([
            'success'  => true,
            'template' => ['id' => (int) $template['id'], 'name' => $template['name']],
            'items'    => $items,
            'filtered_by_obra_type' => $obraType ?: null,
        ]);
    }

    /**
     * AJAX: adicionar item à lista.
     */
    public function storeItem(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $templateId = (int) $this->input('template_id', 0);
        if (!MaterialTemplate::find($templateId)) {
            $this->json(['error' => 'Lista não encontrada.'], 404);
            return;
        }

        $materialId = (int) $this->input('material_id', 0);
        $material   = $materialId ? Material::find($materialId) : null;

        // Nome: usa o do material vinculado ou o informado manualmente
        $name = $material['name'] ?? trim($this->input('material_name', ''));
        if ($name === '') {
            $this->json(['error' => 'Selecione um material ou informe o nome.'], 400);
            return;
        }

        $qty = (float) $this->input('default_quantity', 1);
        if ($qty < 0.01) $qty = 1;

        // Resolve especificação/classificação/unidade a partir do material
        $specification  = trim($this->input('specification', ''));
        $classification = trim($this->input('classification', ''));
        $unit           = trim($this->input('unit', ''));
        $projectType    = 'both'; // default
        if ($material) {
            if ($specification === '') {
                $cat = $material['category_id'] ? MaterialCategory::find((int) $material['category_id']) : null;
                $specification = $cat['name'] ?? ($material['specification'] ?? '');
            }
            if ($classification === '') $classification = $material['classification'] ?? '';
            if ($unit === '' && !empty($material['unit_id'])) {
                $u = MeasurementUnit::find((int) $material['unit_id']);
                $unit = $u['abbreviation'] ?? '';
            }
            $projectType = $material['project_type'] ?? 'both';
        }

        $id = MaterialTemplateItem::create([
            'template_id'      => $templateId,
            'material_id'      => $materialId ?: null,
            'material_name'    => $name,
            'specification'    => $specification ?: null,
            'classification'   => $classification ?: null,
            'unit'             => $unit ?: null,
            'project_type'     => $projectType,
            'default_quantity' => $qty,
            'sort_order'       => (int) $this->input('sort_order', 0),
            'active'           => 1,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'success' => true,
            'item'    => [
                'id'             => $id,
                'material_id'    => $materialId ?: null,
                'material_name'  => $name,
                'specification'  => $specification,
                'classification' => $classification,
                'unit'           => $unit,
                'project_type'   => $projectType,
                'default_quantity' => $qty,
                'active'         => 1,
            ],
        ]);
    }

    /**
     * AJAX: editar item (quantidade padrão e ativo/inativo).
     */
    public function updateItem(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $id = (int) $this->input('id', 0);
        $item = MaterialTemplateItem::find($id);
        if (!$item) {
            $this->json(['error' => 'Item não encontrado.'], 404);
            return;
        }

        $data = [];
        if ($this->input('default_quantity', null) !== null) {
            $qty = (float) $this->input('default_quantity', 1);
            $data['default_quantity'] = $qty >= 0.01 ? $qty : 1;
        }
        if ($this->input('active', null) !== null) {
            $data['active'] = (int) $this->input('active') ? 1 : 0;
        }

        if (!empty($data)) {
            MaterialTemplateItem::updateById($id, $data);
        }

        $this->json(['success' => true]);
    }

    /**
     * AJAX: remover item da lista.
     */
    public function deleteItem(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $id = (int) $this->input('id', 0);
        if ($id) {
            MaterialTemplateItem::deleteById($id);
        }

        $this->json(['success' => true]);
    }

    /**
     * Nome do ator atual (admin logado ou PIN completo).
     */
    private function currentActorName(): string
    {
        $user = Auth::user();
        if (!empty($user['name'])) {
            return $user['name'];
        }
        return $_SESSION['pin_user_name'] ?? 'Sistema';
    }
}
