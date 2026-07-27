<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\Material;
use App\Models\ConstructionSite;
use App\Models\Setting;
use App\Services\NotificationService;

class StockController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('stock')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Listagem de estoque (por depósito ou geral)
     */
    public function index(): void
    {
        $locationId = $this->input('location_id') ? (int) $this->input('location_id') : null;
        $locations = StockLocation::allActive();
        $items = StockItem::allWithRelations($locationId);

        $this->view('admin.stock.index', [
            'items' => $items,
            'locations' => $locations,
            'selectedLocation' => $locationId,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * CRUD de depósitos/estoques
     */
    public function locations(): void
    {
        $locations = StockLocation::allActive();

        $this->view('admin.stock.locations', [
            'locations' => $locations,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Depósitos / Estoques',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * Salvar novo depósito
     */
    public function storeLocation(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock/locations');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'Nome do estoque é obrigatório.');
            $this->redirect('/admin/stock/locations');
            return;
        }

        StockLocation::create([
            'name' => $name,
            'code' => StockLocation::generateCode(),
            'construction_site_id' => $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : null,
            'address' => trim($this->input('address', '')),
            'responsible_name' => trim($this->input('responsible_name', '')),
            'notes' => trim($this->input('notes', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Estoque/Depósito criado com sucesso!');
        $this->redirect('/admin/stock/locations');
    }

    /**
     * Atualizar depósito
     */
    public function updateLocation(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock/locations');
            return;
        }

        $id = (int) $this->input('id', 0);
        $location = StockLocation::find($id);
        if (!$location) {
            $this->setFlash('error', 'Estoque não encontrado.');
            $this->redirect('/admin/stock/locations');
            return;
        }

        $name = trim($this->input('name', ''));
        if (empty($name)) {
            $this->setFlash('error', 'Nome é obrigatório.');
            $this->redirect('/admin/stock/locations');
            return;
        }

        StockLocation::updateById($id, [
            'name' => $name,
            'construction_site_id' => $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : null,
            'address' => trim($this->input('address', '')),
            'responsible_name' => trim($this->input('responsible_name', '')),
            'notes' => trim($this->input('notes', '')),
        ]);

        $this->setFlash('success', 'Estoque atualizado!');
        $this->redirect('/admin/stock/locations');
    }

    /**
     * Excluir depósito
     */
    public function deleteLocation(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock/locations');
            return;
        }

        $id = (int) $this->input('id', 0);
        StockLocation::updateById($id, ['active' => 0]);
        $this->setFlash('success', 'Estoque desativado.');
        $this->redirect('/admin/stock/locations');
    }

    /**
     * Formulário de cadastro de item no estoque
     */
    public function create(): void
    {
        $materials = Material::allActive();
        $locations = StockLocation::allActive();

        $this->view('admin.stock.form', [
            'item' => null,
            'materials' => $materials,
            'locations' => $locations,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Cadastrar Item no Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * Salvar novo item no estoque
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock');
            return;
        }

        $materialId = (int) $this->input('material_id', 0);
        $locationId = (int) $this->input('stock_location_id', 0);
        $quantity = (float) str_replace(',', '.', $this->input('quantity', '0'));
        $minQuantity = (float) str_replace(',', '.', $this->input('min_quantity', '0'));

        if (!$materialId || !$locationId) {
            $this->setFlash('error', 'Material e estoque são obrigatórios.');
            $this->redirect('/admin/stock/create');
            return;
        }

        // Verificar se já existe
        $existing = StockItem::findByMaterialAndLocation($materialId, $locationId);
        if ($existing) {
            $this->setFlash('error', 'Este material já está cadastrado neste estoque. Edite o item existente.');
            $this->redirect('/admin/stock');
            return;
        }

        StockItem::create([
            'material_id' => $materialId,
            'stock_location_id' => $locationId,
            'quantity' => $quantity,
            'min_quantity' => $minQuantity,
            'location_detail' => trim($this->input('location_detail', '')),
            'notes' => trim($this->input('notes', '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Se tem quantidade inicial, registrar entrada
        if ($quantity > 0) {
            StockMovement::record([
                'material_id' => $materialId,
                'from_site_id' => null,
                'to_site_id' => null,
                'from_location_id' => null,
                'to_location_id' => $locationId,
                'quantity' => $quantity,
                'type' => StockMovement::TYPE_ENTRY,
                'status' => StockMovement::STATUS_DELIVERED,
                'requested_by' => Auth::user()['name'],
                'delivered_by' => Auth::user()['name'],
                'delivered_at' => date('Y-m-d H:i:s'),
                'notes' => 'Cadastro inicial de estoque',
            ]);
        }

        $this->setFlash('success', 'Item cadastrado no estoque com sucesso!');
        $this->redirect('/admin/stock?location_id=' . $locationId);
    }

    /**
     * Editar item do estoque
     */
    public function edit(int $id = 0): void
    {
        $id = $id ?: (int) $this->input('id', 0);
        $item = StockItem::find($id);

        if (!$item) {
            $this->setFlash('error', 'Item não encontrado.');
            $this->redirect('/admin/stock');
            return;
        }

        $materials = Material::allActive();
        $locations = StockLocation::allActive();

        $this->view('admin.stock.form', [
            'item' => $item,
            'materials' => $materials,
            'locations' => $locations,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Editar Item do Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * Atualizar item do estoque
     */
    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock');
            return;
        }

        $id = (int) $this->input('id', 0);
        $item = StockItem::find($id);

        if (!$item) {
            $this->setFlash('error', 'Item não encontrado.');
            $this->redirect('/admin/stock');
            return;
        }

        $newQuantity = (float) str_replace(',', '.', $this->input('quantity', '0'));
        $oldQuantity = (float) $item['quantity'];

        StockItem::updateById($id, [
            'quantity' => $newQuantity,
            'min_quantity' => (float) str_replace(',', '.', $this->input('min_quantity', '0')),
            'location_detail' => trim($this->input('location_detail', '')),
            'notes' => trim($this->input('notes', '')),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Se a quantidade mudou, registrar ajuste
        if ($newQuantity != $oldQuantity) {
            $diff = $newQuantity - $oldQuantity;
            $locationId = $item['stock_location_id'] ?? null;
            StockMovement::record([
                'material_id' => $item['material_id'],
                'from_site_id' => null,
                'to_site_id' => null,
                'from_location_id' => $diff < 0 ? $locationId : null,
                'to_location_id' => $diff > 0 ? $locationId : null,
                'quantity' => abs($diff),
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'status' => StockMovement::STATUS_DELIVERED,
                'requested_by' => Auth::user()['name'],
                'delivered_by' => Auth::user()['name'],
                'delivered_at' => date('Y-m-d H:i:s'),
                'notes' => "Ajuste manual: {$oldQuantity} → {$newQuantity}",
            ]);
        }

        $this->setFlash('success', 'Estoque atualizado com sucesso!');
        $this->redirect('/admin/stock?location_id=' . ($item['stock_location_id'] ?? ''));
    }

    /**
     * Excluir item do estoque
     */
    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock');
            return;
        }

        $id = (int) $this->input('id', 0);
        $item = StockItem::find($id);

        if (!$item) {
            $this->setFlash('error', 'Item não encontrado.');
            $this->redirect('/admin/stock');
            return;
        }

        StockItem::deleteById($id);
        $this->setFlash('success', 'Item removido do estoque.');
        $this->redirect('/admin/stock?location_id=' . ($item['stock_location_id'] ?? ''));
    }

    /**
     * Formulário de transferência de estoque
     */
    public function transfer(): void
    {
        $materials = Material::allActive();
        $locations = StockLocation::allActive();

        $this->view('admin.stock.transfer', [
            'materials' => $materials,
            'locations' => $locations,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Transferir Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * Processar transferência de estoque
     */
    public function processTransfer(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock/transfer');
            return;
        }

        $materialId = (int) $this->input('material_id', 0);
        $fromLocationId = (int) $this->input('from_location_id', 0);
        $toLocationId = (int) $this->input('to_location_id', 0);
        $quantity = (float) str_replace(',', '.', $this->input('quantity', '0'));

        if (!$materialId || !$fromLocationId || !$toLocationId || $quantity <= 0) {
            $this->setFlash('error', 'Preencha todos os campos corretamente.');
            $this->redirect('/admin/stock/transfer');
            return;
        }

        if ($fromLocationId === $toLocationId) {
            $this->setFlash('error', 'O estoque de origem e destino devem ser diferentes.');
            $this->redirect('/admin/stock/transfer');
            return;
        }

        // Verificar estoque disponível
        $stockItem = StockItem::findByMaterialAndLocation($materialId, $fromLocationId);
        if (!$stockItem || $stockItem['quantity'] < $quantity) {
            $available = $stockItem ? $stockItem['quantity'] : 0;
            $this->setFlash('error', "Estoque insuficiente. Disponível: {$available}");
            $this->redirect('/admin/stock/transfer');
            return;
        }

        // Registrar movimentação (pendente para o Wilton)
        $movementId = StockMovement::record([
            'material_id' => $materialId,
            'from_site_id' => null,
            'to_site_id' => null,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'quantity' => $quantity,
            'type' => StockMovement::TYPE_TRANSFER,
            'status' => StockMovement::STATUS_PENDING,
            'requested_by' => Auth::user()['name'],
        ]);

        // Notificar o Wilton (transporte)
        $this->notifyTransport($movementId);

        $this->setFlash('success', 'Transferência registrada! O responsável pelo transporte foi notificado.');
        $this->redirect('/admin/stock');
    }

    /**
     * Histórico de movimentações
     */
    public function movements(int $locationId = 0): void
    {
        $locationId = $locationId ?: (int) $this->input('location_id', 0);
        $locations = StockLocation::allActive();

        if ($locationId) {
            $movements = Database::fetchAll(
                "SELECT sm.*, m.name as material_name, m.specification,
                        mu.abbreviation as unit_abbr,
                        sl_from.name as from_location_name,
                        sl_to.name as to_location_name
                 FROM stock_movements sm
                 JOIN materials m ON sm.material_id = m.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 LEFT JOIN stock_locations sl_from ON sm.from_location_id = sl_from.id
                 LEFT JOIN stock_locations sl_to ON sm.to_location_id = sl_to.id
                 WHERE sm.from_location_id = ? OR sm.to_location_id = ?
                 ORDER BY sm.created_at DESC
                 LIMIT 200",
                [$locationId, $locationId]
            );
        } else {
            $movements = Database::fetchAll(
                "SELECT sm.*, m.name as material_name, m.specification,
                        mu.abbreviation as unit_abbr,
                        sl_from.name as from_location_name,
                        sl_to.name as to_location_name
                 FROM stock_movements sm
                 JOIN materials m ON sm.material_id = m.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 LEFT JOIN stock_locations sl_from ON sm.from_location_id = sl_from.id
                 LEFT JOIN stock_locations sl_to ON sm.to_location_id = sl_to.id
                 ORDER BY sm.created_at DESC
                 LIMIT 200"
            );
        }

        $this->view('admin.stock.movements', [
            'movements' => $movements,
            'locations' => $locations,
            'selectedLocation' => $locationId,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Movimentações de Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * API: Verificar estoque para um pedido (AJAX)
     */
    public function checkStock(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $targetSiteId = (int) ($input['items']['target_site_id'] ?? 0);
        $orderItems = $input['items']['items'] ?? [];

        if (empty($orderItems)) {
            $this->json(['availability' => []]);
            return;
        }

        $materialIds = array_filter(array_map(function ($item) {
            return !empty($item['material_id']) ? (int) $item['material_id'] : null;
        }, $orderItems));

        if (empty($materialIds)) {
            $this->json(['availability' => []]);
            return;
        }

        $availability = StockItem::checkAvailability($materialIds, $targetSiteId);

        $this->json(['availability' => $availability]);
    }

    /**
     * API: Buscar estoque de um material (AJAX)
     */
    public function searchStock(): void
    {
        $materialId = (int) $this->input('material_id', 0);
        $excludeLocationId = (int) $this->input('exclude_location_id', 0);

        if (!$materialId) {
            $this->json(['stocks' => []]);
            return;
        }

        $stocks = StockItem::findMaterialInAllStocks($materialId, $excludeLocationId ?: null);
        $this->json(['stocks' => $stocks]);
    }

    /**
     * Cadastro em massa (múltiplos materiais para um depósito)
     */
    public function bulkCreate(): void
    {
        $locations = StockLocation::allActive();
        $materials = Material::allActive();

        $this->view('admin.stock.bulk_create', [
            'materials' => $materials,
            'locations' => $locations,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Cadastro em Massa - Estoque',
            'currentPage' => 'stock',
        ]);
    }

    /**
     * Processar cadastro em massa
     */
    public function bulkStore(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/stock');
            return;
        }

        $locationId = (int) $this->input('stock_location_id', 0);
        $stockItems = $_POST['stock_items'] ?? [];

        if (!$locationId || empty($stockItems)) {
            $this->setFlash('error', 'Selecione o estoque e adicione itens.');
            $this->redirect('/admin/stock/bulk-create');
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($stockItems as $item) {
            $materialId = (int) ($item['material_id'] ?? 0);
            $quantity = (float) str_replace(',', '.', $item['quantity'] ?? '0');

            if (!$materialId || $quantity <= 0) continue;

            $existing = StockItem::findByMaterialAndLocation($materialId, $locationId);

            if ($existing) {
                StockItem::credit($existing['id'], $quantity);
                $updated++;
            } else {
                StockItem::create([
                    'material_id' => $materialId,
                    'stock_location_id' => $locationId,
                    'quantity' => $quantity,
                    'min_quantity' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $created++;
            }

            // Registrar entrada
            StockMovement::record([
                'material_id' => $materialId,
                'from_site_id' => null,
                'to_site_id' => null,
                'from_location_id' => null,
                'to_location_id' => $locationId,
                'quantity' => $quantity,
                'type' => StockMovement::TYPE_ENTRY,
                'status' => StockMovement::STATUS_DELIVERED,
                'requested_by' => Auth::user()['name'],
                'delivered_by' => Auth::user()['name'],
                'delivered_at' => date('Y-m-d H:i:s'),
                'notes' => 'Cadastro em massa',
            ]);
        }

        $this->setFlash('success', "Estoque atualizado! {$created} novo(s), {$updated} atualizado(s).");
        $this->redirect('/admin/stock?location_id=' . $locationId);
    }

    /**
     * Notificar o responsável pelo transporte
     */
    private function notifyTransport(int $movementId): void
    {
        $movement = StockMovement::find($movementId);
        if (!$movement) return;

        $material = Material::find($movement['material_id']);
        $fromLocation = $movement['from_location_id'] ? StockLocation::findFull($movement['from_location_id']) : null;
        $toLocation = $movement['to_location_id'] ? StockLocation::findFull($movement['to_location_id']) : null;

        $materialName = $material['name'] ?? 'Material';
        $fromName = $fromLocation['name'] ?? 'N/A';
        $toName = $toLocation['name'] ?? 'N/A';
        $qty = $movement['quantity'];
        $type = $movement['type'] === 'transfer' ? 'TRANSFERÊNCIA' : 'SAÍDA DE ESTOQUE';

        // E-mail
        $emails = Setting::get('orders_transport_emails', '');
        if (!empty($emails)) {
            $subject = "{$type} - {$materialName}";
            $body = "<h2>{$type} de Material</h2>"
                . "<p><strong>Material:</strong> {$materialName}</p>"
                . "<p><strong>Quantidade:</strong> {$qty}</p>"
                . "<p><strong>Origem:</strong> {$fromName}</p>"
                . ($toLocation ? "<p><strong>Destino:</strong> {$toName}</p>" : '')
                . "<p><strong>Solicitado por:</strong> {$movement['requested_by']}</p>"
                . "<p><strong>Data:</strong> " . date('d/m/Y H:i') . "</p>"
                . "<br><p>Acesse o painel de transporte para mais detalhes.</p>";

            NotificationService::queueEmails($emails, $subject, $body, null, 'stock_transport');
        }

        // Webhook
        $webhookUrl = Setting::get('orders_transport_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*{$type}*\n\n"
                . "*Material:* {$materialName}\n"
                . "*Quantidade:* {$qty}\n"
                . "*Origem:* {$fromName}\n"
                . ($toLocation ? "*Destino:* {$toName}\n" : '')
                . "*Solicitado por:* {$movement['requested_by']}\n"
                . "*Data:* " . date('d/m/Y H:i');

            NotificationService::queueWebhook($webhookUrl, [
                'event' => 'stock_movement',
                'type' => $movement['type'],
                'material' => $materialName,
                'quantity' => $qty,
                'from_location' => $fromName,
                'to_location' => $toName,
                'requested_by' => $movement['requested_by'],
                'message' => $message,
                'phone' => Setting::get('orders_transport_phone', ''),
                'phone_name' => Setting::get('orders_transport_phone_name', ''),
            ], null, 'stock_transport');
        }
    }
}
