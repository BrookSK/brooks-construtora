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
use App\Services\NotificationService;
use App\Services\XlsxService;

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
        $suppliers = Supplier::allActive();
        
        // Buscar histórico de preços para os materiais deste pedido
        $materialIds = array_filter(array_column($items, 'material_id'));
        $priceHistory = [];
        if (!empty($materialIds)) {
            $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
            $priceHistory = Database::fetchAll(
                "SELECT * FROM material_price_history WHERE material_id IN ({$placeholders}) ORDER BY created_at DESC",
                $materialIds
            );
        }

        $this->view('site.orders.quote', [
            'order' => $order,
            'items' => $items,
            'orderSuppliers' => $orderSuppliers,
            'suppliers' => $suppliers,
            'priceHistory' => $priceHistory,
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
        $supplierFinancials = $_POST['supplier_financials'] ?? [];
        $supplierVendor = $_POST['supplier_vendor'] ?? [];
        $supplierIds = $_POST['supplier_ids'] ?? [];
        $lowestTotal = PHP_FLOAT_MAX;

        // Se fornecedores foram adicionados na cotação (novo fluxo)
        if (!empty($supplierIds)) {
            foreach ($supplierIds as $sid) {
                $sid = (int) $sid;
                if ($sid <= 0) continue;

                // Criar registro de fornecedor no pedido (se não existe)
                $existing = PurchaseOrderSupplier::findByOrderAndSupplier($order['id'], $sid);
                if (!$existing) {
                    $posId = PurchaseOrderSupplier::create([
                        'order_id' => $order['id'],
                        'supplier_id' => $sid,
                        'status' => 'quoted',
                        'quoted_by_name' => $quotedByName,
                        'quoted_at' => date('Y-m-d H:i:s'),
                        'quote_notes' => $quoteNotes,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $posId = $existing['id'];
                    PurchaseOrderSupplier::updateById($posId, [
                        'status' => 'quoted',
                        'quoted_by_name' => $quotedByName,
                        'quoted_at' => date('Y-m-d H:i:s'),
                        'quote_notes' => $quoteNotes,
                    ]);
                }

                // Salvar preços por item
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

                // Financeiros
                $fin = $supplierFinancials[$sid] ?? [];
                $discountType = $fin['discount_type'] ?? 'percent';
                $discountValue = (float) str_replace(['.', ','], ['', '.'], $fin['discount_value'] ?? '0');
                $surchargeType = $fin['surcharge_type'] ?? 'percent';
                $surchargeValue = (float) str_replace(['.', ','], ['', '.'], $fin['surcharge_value'] ?? '0');
                $ipiPercent = (float) str_replace(',', '.', $fin['ipi_percent'] ?? '0');
                $icmsPercent = (float) str_replace(',', '.', $fin['icms_percent'] ?? '0');
                $freight = (float) str_replace(['.', ','], ['', '.'], $fin['freight'] ?? '0');

                // Calcular total final
                $finalTotal = $supplierTotal;
                if ($discountType === 'percent') $finalTotal -= $supplierTotal * ($discountValue / 100);
                else $finalTotal -= $discountValue;
                if ($surchargeType === 'percent') $finalTotal += $supplierTotal * ($surchargeValue / 100);
                else $finalTotal += $surchargeValue;
                $finalTotal += $supplierTotal * ($ipiPercent / 100);
                $finalTotal += $supplierTotal * ($icmsPercent / 100);
                $finalTotal += $freight;

                // Atualizar fornecedor com totais e financeiros
                $vendor = $supplierVendor[$sid] ?? [];
                PurchaseOrderSupplier::updateById($posId, [
                    'total' => $finalTotal,
                    'subtotal_items' => $supplierTotal,
                    'subtotal_final' => $finalTotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'surcharge_type' => $surchargeType,
                    'surcharge_value' => $surchargeValue,
                    'ipi_percent' => $ipiPercent,
                    'icms_percent' => $icmsPercent,
                    'freight' => $freight,
                    'vendor_name' => trim($vendor['name'] ?? ''),
                    'vendor_phone' => trim($vendor['phone'] ?? ''),
                    'vendor_email' => trim($vendor['email'] ?? ''),
                    'delivery_days' => !empty($vendor['delivery_days']) ? (int) $vendor['delivery_days'] : null,
                ]);

                // Registrar histórico de preços
                MaterialPriceHistory::recordFromQuote($order['id'], $sid, $items, $pricesForHistory);

                if ($finalTotal < $lowestTotal) $lowestTotal = $finalTotal;
            }
        } else {
            // Fluxo legado (sem fornecedores)
            $itemPrices = $_POST['items'] ?? [];
            $totalEstimated = 0;
            foreach ($itemPrices as $itemId => $itemData) {
                $unitPrice = (float) str_replace(['.', ','], ['', '.'], $itemData['unit_price'] ?? '0');
                $item = PurchaseOrderItem::find((int) $itemId);
                if ($item && $item['order_id'] == $order['id']) {
                    $totalPrice = $unitPrice * $item['quantity'];
                    PurchaseOrderItem::updateById((int) $itemId, ['unit_price' => $unitPrice, 'total_price' => $totalPrice]);
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

            // E-mail de rejeição (fase 3) - via fila
            $emails = Setting::get('orders_completed_emails', '');
            if (!empty($emails)) {
                $subject = "Pedido REJEITADO - {$order['code']}";
                $body = EmailTemplate::purchaseOrderRejected($order, $personName, $notes);
                NotificationService::queueEmails($emails, $subject, $body);
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

    /**
     * Gerar XLSX de um pedido aprovado
     */
    public function xlsx(string $id = ''): void
    {
        $orderId = (int) $id;
        $order = PurchaseOrder::findFull($orderId);

        if (!$order || $order['status'] !== 'approved') {
            $this->show404();
            return;
        }

        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $approvedSupplier = PurchaseOrderSupplier::getApproved($orderId);

        $xlsx = new XlsxService();
        $xlsx->setSheetName('Pedido ' . $order['code']);
        $xlsx->setColumnWidths([6, 45, 12, 12, 8, 8, 12, 14]);

        // Título
        $xlsx->addRow(['BROOKS CONSTRUTORA - Pedido de Materiais'], 'title');
        $xlsx->addEmptyRow();

        // Informações
        $xlsx->addRow(['Pedido:', $order['code'], '', 'Data:', date('d/m/Y', strtotime($order['created_at']))], 'bold');
        $xlsx->addRow(['Solicitante:', $order['created_by_name'] ?? '-', '', 'Status:', 'Aprovado'], 'bold');

        $supplierName = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? '-');
        $xlsx->addRow(['Fornecedor:', $supplierName], 'bold');

        if ($approvedSupplier) {
            $details = [];
            if ($approvedSupplier['vendor_name']) $details[] = 'Vendedor: ' . $approvedSupplier['vendor_name'];
            if ($approvedSupplier['vendor_phone']) $details[] = 'Tel: ' . $approvedSupplier['vendor_phone'];
            if ($approvedSupplier['delivery_days']) $details[] = 'Prazo: ' . $approvedSupplier['delivery_days'] . ' dias';
            if (!empty($details)) {
                $xlsx->addRow([implode(' | ', $details)]);
            }
        }

        $xlsx->addRow(['Aprovado por:', $order['approved_by_name'] ?? '-', '', 'Data:', $order['approved_at'] ? date('d/m/Y H:i', strtotime($order['approved_at'])) : ''], 'bold');
        $xlsx->addEmptyRow();

        // Header
        $xlsx->addRow(['#', 'Material', 'Espec.', 'Classificação', 'Unid.', 'Qtd', 'Unit.', 'Total'], 'header');

        // Itens
        $subtotalInsumos = 0;
        foreach ($items as $i => $item) {
            $unitPrice = $item['unit_price'] ?? 0;
            $totalPrice = $item['total_price'] ?? 0;
            $subtotalInsumos += $totalPrice;

            $xlsx->addRow([
                $i + 1,
                $item['material_name'],
                $item['specification'] ?? '',
                $item['classification'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'],
                $unitPrice,
                $totalPrice,
            ]);
        }

        // Totais
        $xlsx->addRow(['', '', '', '', '', '', 'Insumos:', $subtotalInsumos], 'total');
        if ($order['total_estimated'] != $subtotalInsumos && $order['total_estimated'] > 0) {
            $xlsx->addRow(['', '', '', '', '', '', 'TOTAL:', $order['total_estimated']], 'total');
        }

        // Financeiros
        if ($approvedSupplier && $approvedSupplier['subtotal_items'] > 0) {
            $finRows = [];
            if ($approvedSupplier['discount_value'] > 0) $finRows[] = 'Desconto: ' . $approvedSupplier['discount_value'] . ($approvedSupplier['discount_type'] === 'percent' ? '%' : ' R$');
            if ($approvedSupplier['surcharge_value'] > 0) $finRows[] = 'Acréscimo: ' . $approvedSupplier['surcharge_value'] . ($approvedSupplier['surcharge_type'] === 'percent' ? '%' : ' R$');
            if ($approvedSupplier['ipi_percent'] > 0) $finRows[] = 'IPI: ' . $approvedSupplier['ipi_percent'] . '%';
            if ($approvedSupplier['icms_percent'] > 0) $finRows[] = 'ICMS: ' . $approvedSupplier['icms_percent'] . '%';
            if ($approvedSupplier['freight'] > 0) $finRows[] = 'Frete: R$ ' . number_format($approvedSupplier['freight'], 2, ',', '.');
            if (!empty($finRows)) {
                $xlsx->addEmptyRow();
                $xlsx->addRow(['Detalhamento: ' . implode(' | ', $finRows)], 'bold');
            }
        }

        // Comparação
        if (count($orderSuppliers) > 1) {
            $xlsx->addEmptyRow();
            $xlsx->addRow(['Comparação de Fornecedores:'], 'bold');
            $xlsx->addRow(['Fornecedor', 'Insumos', 'Desconto', 'Acréscimo', 'IPI', 'ICMS', 'Frete', 'Total'], 'header');
            foreach ($orderSuppliers as $os) {
                $xlsx->addRow([
                    $os['supplier_name'] . ($os['approved'] ? ' (APROVADO)' : ''),
                    $os['subtotal_items'] ?? 0,
                    $os['discount_value'] > 0 ? $os['discount_value'] . ($os['discount_type'] === 'percent' ? '%' : ' R$') : '-',
                    $os['surcharge_value'] > 0 ? $os['surcharge_value'] . ($os['surcharge_type'] === 'percent' ? '%' : ' R$') : '-',
                    $os['ipi_percent'] > 0 ? $os['ipi_percent'] . '%' : '-',
                    $os['icms_percent'] > 0 ? $os['icms_percent'] . '%' : '-',
                    $os['freight'] ?? 0,
                    $os['subtotal_final'] ?? $os['total'] ?? 0,
                ]);
            }
        }

        if (!empty($order['description'])) {
            $xlsx->addEmptyRow();
            $xlsx->addRow(['Observações: ' . $order['description']]);
        }

        $xlsx->download('Pedido_' . $order['code'] . '.xlsx');
    }

    // ============================
    // PAINEL COM PIN (ACESSO RÁPIDO)
    // ============================

    /**
     * Cadastro rápido de fornecedor (rota pública para a tela de cotação)
     */
    public function quickStoreSupplier(): void
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
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $supplier = Supplier::find($id);
        $this->json(['success' => true, 'supplier' => $supplier]);
    }

    /**
     * Painel de pedidos - redireciona pro admin se autenticado por PIN
     */
    public function pinPanel(): void
    {
        if (!$this->isPinAuthenticated()) {
            $this->redirect('/pedidos/login');
            return;
        }
        // Redireciona pro admin de pedidos (a sessão do PIN simula um user comprador)
        $this->redirect('/admin/orders');
    }

    /**
     * Tela de login por PIN
     */
    public function pinLogin(): void
    {
        if ($this->isPinAuthenticated()) {
            $this->redirect('/admin/orders');
            return;
        }

        $flash = $this->getFlash();
        $this->view('site.orders.pin_login', ['flash' => $flash]);
    }

    /**
     * Processar autenticação por PIN
     */
    public function pinAuth(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/pedidos/login');
            return;
        }

        $pin = trim($this->input('pin', ''));
        $correctPin = Setting::get('orders_pin_code', '');

        if (empty($correctPin)) {
            $this->setFlash('error', 'PIN não configurado. Contate o administrador.');
            $this->redirect('/pedidos/login');
            return;
        }

        if ($pin !== $correctPin) {
            $this->setFlash('error', 'PIN incorreto.');
            $this->redirect('/pedidos/login');
            return;
        }

        // Autenticar como "comprador" via sessão
        $_SESSION['user_id'] = 0;
        $_SESSION['user_name'] = 'Comprador';
        $_SESSION['user_email'] = 'comprador@pin';
        $_SESSION['user_role'] = 'comprador';
        $_SESSION['pin_auth'] = true;
        $_SESSION['pin_auth_time'] = time();

        // Cookie de 30 dias para manter logado
        setcookie('pin_session', hash('sha256', $correctPin . 'brooks_pin_salt'), time() + (30 * 24 * 60 * 60), '/');

        $this->redirect('/admin/orders');
    }

    /**
     * Logout do PIN
     */
    public function pinLogout(): void
    {
        unset($_SESSION['pin_auth']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);
        setcookie('pin_session', '', time() - 3600, '/');
        $this->redirect('/pedidos/login');
    }

    /**
     * Verifica se está autenticado por PIN
     */
    private function isPinAuthenticated(): bool
    {
        // Já tem sessão ativa
        if (!empty($_SESSION['pin_auth'])) {
            return true;
        }

        // Verificar cookie de 30 dias
        $cookie = $_COOKIE['pin_session'] ?? '';
        if (!empty($cookie)) {
            $correctPin = Setting::get('orders_pin_code', '');
            if (!empty($correctPin) && $cookie === hash('sha256', $correctPin . 'brooks_pin_salt')) {
                // Restaurar sessão
                $_SESSION['user_id'] = 0;
                $_SESSION['user_name'] = 'Comprador';
                $_SESSION['user_email'] = 'comprador@pin';
                $_SESSION['user_role'] = 'comprador';
                $_SESSION['pin_auth'] = true;
                return true;
            }
        }

        return false;
    }

    // ============================
    // MÉTODOS PRIVADOS
    // ============================

    private function sendApprovalNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$token}";

        $emails = Setting::get('orders_approval_emails', '');
        if (!empty($emails)) {
            $subject = "Aprovação Pendente - Pedido {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
            $body = EmailTemplate::purchaseOrderApproval($order, $items, $approvalUrl, $orderSuppliers);
            NotificationService::queueEmails($emails, $subject, $body);
        }

        $webhookUrl = Setting::get('orders_approval_webhook', '');
        if (!empty($webhookUrl)) {
            $supplierComparison = '';
            $suppliersData = [];
            if (!empty($orderSuppliers)) {
                $supplierComparison = "*Cotações por fornecedor:*\n";
                foreach ($orderSuppliers as $os) {
                    $totalFmt = $os['total'] ? 'R$ ' . number_format($os['total'], 2, ',', '.') : 'Pendente';
                    $supplierComparison .= "- {$os['supplier_name']}: {$totalFmt}\n";
                    $suppliersData[] = ['id' => $os['supplier_id'], 'name' => $os['supplier_name'], 'total' => $os['total']];
                }
                $supplierComparison .= "\n";
            }

            $message = "*PEDIDO AGUARDANDO APROVAÇÃO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Itens:* " . count($items) . "\n"
                . "*Cotado por:* {$order['quoted_by_name']}\n"
                . "*Data cotação:* " . date('d/m/Y H:i', strtotime($order['quoted_at'])) . "\n\n"
                . $supplierComparison
                . "*Link para aprovar/rejeitar:*\n{$approvalUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'approval_requested',
                'order_code' => $order['code'],
                'suppliers' => $suppliersData,
                'total' => $order['total_estimated'],
                'items_count' => count($items),
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
        $xlsxUrl = "{$baseUrl}/pedido/xlsx/{$orderId}";

        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
            $body = EmailTemplate::purchaseOrderCompleted($order, $items, $pdfUrl, $xlsxUrl);
            NotificationService::queueEmails($emails, $subject, $body);
        }

        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($order['total_estimated'], 2, ',', '.');

            $message = "*PEDIDO APROVADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Aprovado por:* {$order['approved_by_name']}\n\n"
                . "*PDF do pedido:*\n{$pdfUrl}\n\n"
                . "*Planilha do pedido:*\n{$xlsxUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'order_approved',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'approved_by' => $order['approved_by_name'],
                'pdf_url' => $pdfUrl,
                'xlsx_url' => $xlsxUrl,
                'phone' => Setting::get('orders_completed_phone', ''),
                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    private function sendWebhook(string $url, array $data): void
    {
        NotificationService::queueWebhook($url, $data);
        NotificationService::processImmediate();
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
