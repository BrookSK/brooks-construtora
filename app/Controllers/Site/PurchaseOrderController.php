<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseOrderSupplier;
use App\Models\PurchaseOrderItemPrice;
use App\Models\MaterialPriceHistory;
use App\Models\Supplier;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\EmailTemplate;

class PurchaseOrderController extends Controller
{
    /**
     * Página pública de cotação
     */
    public function quote(string $token = ''): void
    {
        if (empty($token)) {
            $this->show404();
            return;
        }

        $order = PurchaseOrder::findByQuoteToken($token);

        if (!$order) {
            $this->show404();
            return;
        }

        if (!in_array($order['status'], ['pending_quote'])) {
            $this->view('site.orders.already_processed', [
                'order' => $order,
                'message' => 'Este pedido já foi cotado.',
            ]);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($order['id']);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);

        $this->view('site.orders.quote', [
            'order' => $order,
            'items' => $items,
            'orderSuppliers' => $orderSuppliers,
            'token' => $token,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar cotação (POST)
     */
    public function submitQuote(string $token = ''): void
    {
        if (!$this->isPost() || empty($token)) {
            $this->show404();
            return;
        }

        $order = PurchaseOrder::findByQuoteToken($token);

        if (!$order || $order['status'] !== 'pending_quote') {
            $this->setFlash('error', 'Pedido não encontrado ou já foi processado.');
            $this->redirect('/pedido/cotacao/' . $token);
            return;
        }

        $quotedByName = trim($this->input('quoted_by_name', ''));
        if (empty($quotedByName)) {
            $this->setFlash('error', 'Informe seu nome para registrar a cotação.');
            $this->redirect('/pedido/cotacao/' . $token);
            return;
        }

        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);
        $items = PurchaseOrderItem::getByOrder($order['id']);
        $quoteNotes = trim($this->input('quote_notes', ''));
        $supplierPrices = $_POST['supplier_prices'] ?? [];
        $lowestTotal = PHP_FLOAT_MAX;

        // Processar preços por fornecedor
        foreach ($orderSuppliers as $os) {
            $sid = $os['supplier_id'];
            $supplierTotal = 0;
            $pricesForHistory = [];

            if (isset($supplierPrices[$sid])) {
                foreach ($supplierPrices[$sid] as $itemId => $priceStr) {
                    $unitPrice = (float) str_replace(['.', ','], ['', '.'], $priceStr);
                    $item = PurchaseOrderItem::find((int) $itemId);
                    
                    if ($item && $item['order_id'] == $order['id']) {
                        $totalPrice = $unitPrice * $item['quantity'];
                        $supplierTotal += $totalPrice;

                        PurchaseOrderItemPrice::create([
                            'order_id' => $order['id'],
                            'item_id' => (int) $itemId,
                            'supplier_id' => $sid,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);

                        $pricesForHistory[$item['id']] = $unitPrice;
                    }
                }
            }

            // Atualizar o fornecedor do pedido
            PurchaseOrderSupplier::updateById($os['id'], [
                'status' => 'quoted',
                'total' => $supplierTotal,
                'quoted_by_name' => $quotedByName,
                'quoted_at' => date('Y-m-d H:i:s'),
                'quote_notes' => $quoteNotes,
            ]);

            // Salvar no histórico de preços
            MaterialPriceHistory::recordFromQuote($order['id'], $sid, $items, $pricesForHistory);

            if ($supplierTotal < $lowestTotal) {
                $lowestTotal = $supplierTotal;
            }
        }

        // Se não tem fornecedores vinculados, usar fluxo legado (preços direto nos itens)
        if (empty($orderSuppliers)) {
            $itemPrices = $_POST['items'] ?? [];
            $totalEstimated = 0;
            foreach ($itemPrices as $itemId => $itemData) {
                $unitPrice = (float) str_replace(['.', ','], ['', '.'], $itemData['unit_price'] ?? '0');
                $item = PurchaseOrderItem::find((int) $itemId);
                if ($item && $item['order_id'] == $order['id']) {
                    $totalPrice = $unitPrice * $item['quantity'];
                    PurchaseOrderItem::updateById((int) $itemId, [
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                    $totalEstimated += $totalPrice;
                }
            }
            $lowestTotal = $totalEstimated;
        }

        // Atualizar pedido
        PurchaseOrder::updateById($order['id'], [
            'status' => 'pending_approval',
            'total_estimated' => $lowestTotal != PHP_FLOAT_MAX ? $lowestTotal : 0,
            'quoted_by_name' => $quotedByName,
            'quoted_at' => date('Y-m-d H:i:s'),
            'quote_notes' => $quoteNotes,
        ]);

        // Log no histórico
        $finalTotal = $lowestTotal != PHP_FLOAT_MAX ? $lowestTotal : 0;
        PurchaseOrderHistory::log(
            $order['id'],
            'quoted',
            "Cotação realizada por {$quotedByName}. Total: R$ " . number_format($finalTotal, 2, ',', '.'),
            $quotedByName
        );

        // Enviar notificações de aprovação
        $this->sendApprovalNotifications($order['id'], $order['approval_token']);

        $this->view('site.orders.quote_success', [
            'order' => $order,
            'total' => $finalTotal,
            'orderSuppliers' => PurchaseOrderSupplier::getByOrder($order['id']),
        ]);
    }

    /**
     * Página pública de aprovação
     */
    public function approval(string $token = ''): void
    {
        if (empty($token)) {
            $this->show404();
            return;
        }

        $order = PurchaseOrder::findByApprovalToken($token);

        if (!$order) {
            $this->show404();
            return;
        }

        if (!in_array($order['status'], ['pending_approval'])) {
            $this->view('site.orders.already_processed', [
                'order' => $order,
                'message' => $order['status'] === 'approved' ? 'Este pedido já foi aprovado.' : 'Este pedido já foi processado.',
            ]);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($order['id']);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);
        $itemPrices = PurchaseOrderItemPrice::getByOrder($order['id']);

        $this->view('site.orders.approval', [
            'order' => $order,
            'items' => $items,
            'orderSuppliers' => $orderSuppliers,
            'itemPrices' => $itemPrices,
            'token' => $token,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar aprovação/rejeição (POST)
     */
    public function submitApproval(string $token = ''): void
    {
        if (!$this->isPost() || empty($token)) {
            $this->show404();
            return;
        }

        $order = PurchaseOrder::findByApprovalToken($token);

        if (!$order || $order['status'] !== 'pending_approval') {
            $this->setFlash('error', 'Pedido não encontrado ou já foi processado.');
            $this->redirect('/pedido/aprovacao/' . $token);
            return;
        }

        $action = $this->input('action', '');
        $personName = trim($this->input('person_name', ''));
        $notes = trim($this->input('approval_notes', ''));
        $approvedSupplierId = (int) $this->input('approved_supplier_id', 0);

        if (empty($personName)) {
            $this->setFlash('error', 'Informe seu nome para registrar a decisão.');
            $this->redirect('/pedido/aprovacao/' . $token);
            return;
        }

        if ($action === 'approve') {
            $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);

            // Se tem fornecedores vinculados, precisa selecionar qual aprovar
            if (!empty($orderSuppliers) && $approvedSupplierId <= 0) {
                $this->setFlash('error', 'Selecione qual fornecedor está aprovando.');
                $this->redirect('/pedido/aprovacao/' . $token);
                return;
            }

            $approvedSupplierName = '';
            $approvedTotal = $order['total_estimated'];

            if (!empty($orderSuppliers) && $approvedSupplierId > 0) {
                // Marcar fornecedor aprovado
                foreach ($orderSuppliers as $os) {
                    if ($os['supplier_id'] == $approvedSupplierId) {
                        PurchaseOrderSupplier::updateById($os['id'], ['approved' => 1, 'status' => 'approved']);
                        $approvedSupplierName = $os['supplier_name'];
                        $approvedTotal = $os['total'];

                        // Copiar preços do fornecedor aprovado para os itens do pedido
                        $prices = PurchaseOrderItemPrice::getByOrderAndSupplier($order['id'], $approvedSupplierId);
                        foreach ($prices as $p) {
                            PurchaseOrderItem::updateById($p['item_id'], [
                                'unit_price' => $p['unit_price'],
                                'total_price' => $p['total_price'],
                            ]);
                        }

                        // Marcar no histórico de preços como aprovado
                        Database::query(
                            "UPDATE material_price_history SET was_approved = 1 WHERE order_id = ? AND supplier_id = ?",
                            [$order['id'], $approvedSupplierId]
                        );
                    } else {
                        PurchaseOrderSupplier::updateById($os['id'], ['status' => 'rejected']);
                    }
                }
            }

            PurchaseOrder::updateById($order['id'], [
                'status' => 'approved',
                'supplier_id' => $approvedSupplierId ?: null,
                'total_estimated' => $approvedTotal,
                'approved_by_name' => $personName,
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_notes' => $notes,
            ]);

            $approvalDesc = "Pedido aprovado por {$personName}";
            if ($approvedSupplierName) {
                $approvalDesc .= ". Fornecedor aprovado: {$approvedSupplierName}";
            }
            if (!empty($notes)) {
                $approvalDesc .= ". Obs: {$notes}";
            }

            PurchaseOrderHistory::log($order['id'], 'approved', $approvalDesc, $personName);

            // Enviar notificações de conclusão
            $this->sendCompletedNotifications($order['id']);

            $this->view('site.orders.approval_success', [
                'order' => $order,
                'action' => 'approved',
                'approvedSupplier' => PurchaseOrderSupplier::getApproved($order['id']),
            ]);
        } elseif ($action === 'reject') {
            if (empty($notes)) {
                $this->setFlash('error', 'Informe o motivo da rejeição.');
                $this->redirect('/pedido/aprovacao/' . $token);
                return;
            }

            PurchaseOrder::updateById($order['id'], [
                'status' => 'rejected',
                'rejected_by_name' => $personName,
                'rejected_at' => date('Y-m-d H:i:s'),
                'approval_notes' => $notes,
            ]);

            PurchaseOrderHistory::log(
                $order['id'],
                'rejected',
                "Pedido rejeitado por {$personName}. Motivo: {$notes}",
                $personName
            );

            // E-mail de rejeição (fase 3)
            $emails = Setting::get('orders_completed_emails', '');
            if (!empty($emails)) {
                try {
                    $mailService = new MailService();
                    $subject = "Pedido REJEITADO - {$order['code']}";
                    $body = EmailTemplate::purchaseOrderRejected($order, $personName, $notes);
                    
                    $emailList = array_map('trim', explode(',', $emails));
                    foreach ($emailList as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $mailService->send($email, $subject, $body, true);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao enviar e-mail de rejeição: " . $e->getMessage());
                }
            }

            // Webhook de rejeição (usa os dados da fase 3 - conclusão)
            $webhookUrl = Setting::get('orders_completed_webhook', '');
            if (!empty($webhookUrl)) {
                $message = "*PEDIDO REJEITADO*\n\n"
                    . "*Pedido:* {$order['code']}\n"
                    . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                    . "*Valor cotado:* R$ " . number_format($order['total_estimated'], 2, ',', '.') . "\n"
                    . "*Rejeitado por:* {$personName}\n"
                    . "*Data:* " . date('d/m/Y H:i') . "\n\n"
                    . "*Motivo da rejeição:*\n{$notes}";

                $this->sendWebhook($webhookUrl, [
                    'event' => 'order_rejected',
                    'order_code' => $order['code'],
                    'supplier' => $order['supplier_name'] ?? 'N/A',
                    'total' => $order['total_estimated'],
                    'rejected_by' => $personName,
                    'rejected_at' => date('Y-m-d H:i:s'),
                    'reason' => $notes,
                    'phone' => Setting::get('orders_completed_phone', ''),
                    'phone_name' => Setting::get('orders_completed_phone_name', ''),
                    'message' => $message,
                ]);
            }

            $this->view('site.orders.approval_success', [
                'order' => $order,
                'action' => 'rejected',
                'approvedSupplier' => null,
            ]);
        } else {
            $this->setFlash('error', 'Ação inválida.');
            $this->redirect('/pedido/aprovacao/' . $token);
        }
    }

    /**
     * Gerar PDF (renderizado no client via JS, dados servidos por esta rota)
     */
    public function pdf(string $id = ''): void
    {
        $orderId = (int) $id;
        $order = PurchaseOrder::findFull($orderId);

        if (!$order || $order['status'] !== 'approved') {
            $this->show404();
            return;
        }

        $items = PurchaseOrderItem::getByOrder($orderId);
        $history = PurchaseOrderHistory::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $approvedSupplier = PurchaseOrderSupplier::getApproved($orderId);

        $this->view('site.orders.pdf', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'orderSuppliers' => $orderSuppliers,
            'approvedSupplier' => $approvedSupplier,
        ]);
    }

    // ============================
    // MÉTODOS PRIVADOS
    // ============================

    private function sendApprovalNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$token}";

        $emails = Setting::get('orders_approval_emails', '');
        if (!empty($emails)) {
            try {
                $mailService = new MailService();
                $subject = "Aprovação Pendente - Pedido {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
                $body = EmailTemplate::purchaseOrderApproval($order, $items, $approvalUrl);
                
                $emailList = array_map('trim', explode(',', $emails));
                foreach ($emailList as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mailService->send($email, $subject, $body, true);
                    }
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail de aprovação: " . $e->getMessage());
            }
        }

        $webhookUrl = Setting::get('orders_approval_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($order['total_estimated'], 2, ',', '.');

            $message = "*PEDIDO AGUARDANDO APROVAÇÃO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Cotado por:* {$order['quoted_by_name']}\n\n"
                . "*Link para aprovar/rejeitar:*\n{$approvalUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'approval_requested',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'approval_url' => $approvalUrl,
                'quoted_by' => $order['quoted_by_name'],
                'phone' => Setting::get('orders_approval_phone', ''),
                'phone_name' => Setting::get('orders_approval_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    private function sendCompletedNotifications(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $pdfUrl = "{$baseUrl}/pedido/pdf/{$orderId}";

        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            try {
                $mailService = new MailService();
                $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
                $body = EmailTemplate::purchaseOrderCompleted($order, $items, $pdfUrl);
                
                $emailList = array_map('trim', explode(',', $emails));
                foreach ($emailList as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mailService->send($email, $subject, $body, true);
                    }
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail de conclusão: " . $e->getMessage());
            }
        }

        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($order['total_estimated'], 2, ',', '.');

            $message = "*PEDIDO APROVADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Aprovado por:* {$order['approved_by_name']}\n\n"
                . "*PDF do pedido:*\n{$pdfUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'order_approved',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'approved_by' => $order['approved_by_name'],
                'pdf_url' => $pdfUrl,
                'phone' => Setting::get('orders_completed_phone', ''),
                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    private function sendWebhook(string $url, array $data): void
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            error_log("Erro ao enviar webhook: " . $e->getMessage());
        }
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        return $scheme . '://' . $host;
    }

    private function show404(): void
    {
        http_response_code(404);
        if (file_exists(ROOT_PATH . '/app/Views/site/errors/404.php')) {
            require_once ROOT_PATH . '/app/Views/site/errors/404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }
    }
}
