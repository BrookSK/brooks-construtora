<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Services\NotificationService;

class TransportController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('transport')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Painel Kanban de transporte (tela principal do Wilton)
     */
    public function index(): void
    {
        $pending = StockMovement::getPendingForTransport();
        $inTransit = StockMovement::getInTransit();
        $delivered = StockMovement::getDelivered(20);

        $this->view('admin.transport.index', [
            'pending' => $pending,
            'inTransit' => $inTransit,
            'delivered' => $delivered,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Painel de Transporte',
            'currentPage' => 'transport',
        ]);
    }

    /**
     * Marcar movimentação como em trânsito
     */
    public function markInTransit(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/transport');
            return;
        }

        $id = (int) $this->input('id', 0);
        $movement = StockMovement::find($id);

        if (!$movement || $movement['status'] !== 'pending') {
            $this->setFlash('error', 'Movimentação não encontrada ou já processada.');
            $this->redirect('/admin/transport');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Transporte';
        StockMovement::markInTransit($id, $userName);

        // Notificar quem recebe o checklist de entrega
        $this->notifyDeliveryRecipient($movement, 'in_transit', $userName);

        $this->setFlash('success', 'Marcado como em trânsito!');
        $this->redirect('/admin/transport');
    }

    /**
     * Marcar movimentação como entregue
     */
    public function markDelivered(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/transport');
            return;
        }

        $id = (int) $this->input('id', 0);
        $movement = StockMovement::find($id);

        if (!$movement || !in_array($movement['status'], ['pending', 'in_transit'])) {
            $this->setFlash('error', 'Movimentação não encontrada ou já entregue.');
            $this->redirect('/admin/transport');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Transporte';
        StockMovement::markDelivered($id, $userName);

        // Notificar quem recebe o checklist de entrega
        $this->notifyDeliveryRecipient($movement, 'delivered', $userName);

        // Notificar quem solicitou sobre a entrega
        $this->notifyDeliveryCompleted($id, $movement);

        $this->setFlash('success', 'Entrega confirmada! Estoque atualizado.');
        $this->redirect('/admin/transport');
    }

    /**
     * Marcar múltiplas como em trânsito (AJAX)
     */
    public function bulkInTransit(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $ids = $_POST['ids'] ?? [];
        $userName = Auth::user()['name'] ?? 'Transporte';
        $count = 0;

        foreach ($ids as $id) {
            $id = (int) $id;
            $movement = StockMovement::find($id);
            if ($movement && $movement['status'] === 'pending') {
                StockMovement::markInTransit($id, $userName);
                $count++;
            }
        }

        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * Marcar múltiplas como entregues (AJAX)
     */
    public function bulkDelivered(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $ids = $_POST['ids'] ?? [];
        $userName = Auth::user()['name'] ?? 'Transporte';
        $count = 0;

        foreach ($ids as $id) {
            $id = (int) $id;
            $movement = StockMovement::find($id);
            if ($movement && in_array($movement['status'], ['pending', 'in_transit'])) {
                StockMovement::markDelivered($id, $userName);
                $this->notifyDeliveryCompleted($id, $movement);
                $count++;
            }
        }

        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * Detalhes de uma movimentação (AJAX)
     */
    public function detail(): void
    {
        $id = (int) $this->input('id', 0);
        $movement = StockMovement::find($id);

        if (!$movement) {
            $this->json(['error' => 'Não encontrado.'], 404);
            return;
        }

        $material = \App\Models\Material::find($movement['material_id']);
        $fromSite = $movement['from_site_id'] ? \App\Models\ConstructionSite::find($movement['from_site_id']) : null;
        $toSite = $movement['to_site_id'] ? \App\Models\ConstructionSite::find($movement['to_site_id']) : null;
        $order = $movement['order_id'] ? PurchaseOrder::find($movement['order_id']) : null;

        $this->json([
            'movement' => $movement,
            'material' => $material,
            'from_site' => $fromSite,
            'to_site' => $toSite,
            'order' => $order,
        ]);
    }

    /**
     * Visualização de pedidos (somente leitura para o Wilton)
     */
    public function orders(): void
    {
        $orders = PurchaseOrder::allWithSupplier();

        $this->view('admin.transport.orders', [
            'orders' => $orders,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Pedidos (Visualização)',
            'currentPage' => 'transport_orders',
        ]);
    }

    /**
     * Notificar quem recebe o checklist de entrega sobre status do transporte
     */
    private function notifyDeliveryRecipient(array $movement, string $status, string $transportedBy): void
    {
        $material = \App\Models\Material::find($movement['material_id']);
        $materialName = $material['name'] ?? 'Material';
        $qty = (float) $movement['quantity'];
        $qtyFmt = $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2, ',', '.');

        $fromName = 'N/A';
        $toName = 'N/A';
        if ($movement['from_location_id']) {
            $loc = \App\Models\StockLocation::findFull($movement['from_location_id']);
            $fromName = $loc['name'] ?? 'N/A';
        } elseif ($movement['from_site_id']) {
            $site = \App\Models\ConstructionSite::find($movement['from_site_id']);
            $fromName = $site['name'] ?? 'N/A';
        }
        if ($movement['to_location_id']) {
            $loc = \App\Models\StockLocation::findFull($movement['to_location_id']);
            $toName = $loc['name'] ?? 'N/A';
        } elseif ($movement['to_site_id']) {
            $site = \App\Models\ConstructionSite::find($movement['to_site_id']);
            $toName = $site['name'] ?? 'N/A';
        }

        $statusLabel = $status === 'in_transit' ? 'EM TRANSPORTE' : 'ENTREGUE';
        $emoji = $status === 'in_transit' ? '🚚' : '✅';
        $orderCode = '';
        if ($movement['order_id']) {
            $order = PurchaseOrder::find($movement['order_id']);
            $orderCode = $order ? $order['code'] : '';
        }

        // Webhook para quem recebe o checklist
        $webhookUrl = Setting::get('orders_delivery_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*{$emoji} TRANSFERÊNCIA {$statusLabel}*\n\n"
                . "*Material:* {$materialName}\n"
                . "*Quantidade:* {$qtyFmt}\n"
                . "*De:* {$fromName}\n"
                . "*Para:* {$toName}\n"
                . ($orderCode ? "*Pedido:* {$orderCode}\n" : '')
                . "*{$statusLabel} por:* {$transportedBy}\n"
                . "*Data:* " . date('d/m/Y H:i');

            NotificationService::queueWebhook($webhookUrl, [
                'event' => 'stock_transport_' . $status,
                'material' => $materialName,
                'quantity' => $qtyFmt,
                'from' => $fromName,
                'to' => $toName,
                'order_code' => $orderCode,
                'status' => $statusLabel,
                'by' => $transportedBy,
                'message' => $message,
                'phone' => Setting::get('orders_delivery_phone', ''),
                'phone_name' => Setting::get('orders_delivery_phone_name', ''),
            ], $movement['order_id'] ?? null, 'stock_transport_' . $status);
        }

        // Email
        $emails = Setting::get('orders_delivery_emails', '');
        if (!empty($emails)) {
            $subject = "Transferência {$statusLabel} - {$materialName}";
            $body = "<h2>{$emoji} Transferência {$statusLabel}</h2>"
                . "<p><strong>Material:</strong> {$materialName}</p>"
                . "<p><strong>Quantidade:</strong> {$qtyFmt}</p>"
                . "<p><strong>De:</strong> {$fromName}</p>"
                . "<p><strong>Para:</strong> {$toName}</p>"
                . ($orderCode ? "<p><strong>Pedido:</strong> {$orderCode}</p>" : '')
                . "<p><strong>{$statusLabel} por:</strong> {$transportedBy}</p>"
                . "<p><strong>Data:</strong> " . date('d/m/Y H:i') . "</p>";
            NotificationService::queueEmails($emails, $subject, $body, $movement['order_id'] ?? null, 'stock_transport_' . $status);
        }
    }

    /**
     * Notificar que a entrega foi concluída
     */
    private function notifyDeliveryCompleted(int $movementId, array $movement): void
    {
        $material = \App\Models\Material::find($movement['material_id']);
        $fromSite = $movement['from_site_id'] ? \App\Models\ConstructionSite::find($movement['from_site_id']) : null;
        $toSite = $movement['to_site_id'] ? \App\Models\ConstructionSite::find($movement['to_site_id']) : null;

        $materialName = $material['name'] ?? 'Material';
        $fromName = $fromSite['name'] ?? 'N/A';
        $toName = $toSite['name'] ?? 'N/A';
        $userName = Auth::user()['name'] ?? 'Transporte';

        // Notificar via webhook de conclusão
        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $type = $movement['type'] === 'transfer' ? 'TRANSFERÊNCIA CONCLUÍDA' : 'ENTREGA DE MATERIAL';
            $message = "*{$type}*\n\n"
                . "*Material:* {$materialName}\n"
                . "*Quantidade:* {$movement['quantity']}\n"
                . "*Origem:* {$fromName}\n"
                . ($toSite ? "*Destino:* {$toName}\n" : '')
                . "*Entregue por:* {$userName}\n"
                . "*Data:* " . date('d/m/Y H:i');

            NotificationService::queueWebhook($webhookUrl, [
                'event' => 'stock_delivered',
                'material' => $materialName,
                'quantity' => $movement['quantity'],
                'from_site' => $fromName,
                'to_site' => $toName,
                'delivered_by' => $userName,
                'message' => $message,
                'phone' => Setting::get('orders_completed_phone', ''),
                'phone_name' => Setting::get('orders_completed_phone_name', ''),
            ], null, 'stock_delivered');
        }
    }
}
