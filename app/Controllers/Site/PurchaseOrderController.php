<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseOrderSupplier;
use App\Models\PurchaseOrderItemPrice;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderSpareItem;
use App\Models\PinUser;
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
     * Verifica se login por PIN é obrigatório. Se sim e não está logado, redireciona.
     */
    private function requirePinIfEnabled(): bool
    {
        if (Setting::get('orders_require_pin_login', '0') !== '1') return false;
        $pinUser = PinAuthController::getLoggedUser();
        if ($pinUser) return false;
        // Não está logado e PIN é obrigatório
        $redirect = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /pin/login?redirect=' . urlencode($redirect));
        exit;
    }

    /**
     * Página pública de cotação
     */
    public function quote(string $token = ''): void
    {
        $this->requirePinIfEnabled();
        if (empty($token)) {
            $this->show404();
            return;
        }

        $order = PurchaseOrder::findByQuoteToken($token);

        if (!$order) {
            $this->show404();
            return;
        }

        if (!in_array($order['status'], ['pending_quote', 'pending_approval'])) {
            $this->view('site.orders.already_processed', [
                'order' => $order,
                'message' => $order['status'] === 'cancelled' ? 'Este pedido foi cancelado.' : 'Este pedido já foi processado.',
            ]);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($order['id']);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);
        $suppliers = Supplier::allActive();
        $comments = Database::fetchAll("SELECT * FROM purchase_order_comments WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);
        $itemPrices = PurchaseOrderItemPrice::getByOrder($order['id']);
        
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
            'comments' => $comments,
            'itemPrices' => $itemPrices,
            'token' => $token,
            'pinUser' => \App\Controllers\Site\PinAuthController::getLoggedUser(),
            'allMaterials' => ($order['order_type'] ?? 'material') === 'service' ? \App\Models\Material::allActive() : [],
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

        if (!$order || !in_array($order['status'], ['pending_quote', 'pending_approval'])) {
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
                
                // Limitar a 100 quando tipo é porcentagem
                if ($discountType === 'percent' && $discountValue > 100) $discountValue = 100;
                if ($surchargeType === 'percent' && $surchargeValue > 100) $surchargeValue = 100;
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
                    'payment_method' => !empty($vendor['payment_method']) ? $vendor['payment_method'] : null,
                    'payment_condition' => trim($vendor['payment_condition'] ?? '') ?: null,
                    'payment_first_due' => !empty($vendor['payment_first_due']) ? $vendor['payment_first_due'] : null,
                    'payment_notes' => trim($vendor['payment_notes'] ?? '') ?: null,
                ]);

                // Registrar histórico de preços
                MaterialPriceHistory::recordFromQuote($order['id'], $sid, $items, $pricesForHistory);

                // Salvar materiais de serviço (se pedido é do tipo serviço)
                if (($order['order_type'] ?? 'material') === 'service') {
                    $serviceMaterials = $_POST['service_materials'] ?? [];
                    if (!empty($serviceMaterials[$sid])) {
                        $matsList = json_decode($serviceMaterials[$sid], true);
                        if (is_array($matsList)) {
                            // Salvar PDF (se teve upload)
                            $pdfId = null;
                            if (isset($_FILES["service_pdf_{$sid}"]) && $_FILES["service_pdf_{$sid}"]['error'] === UPLOAD_ERR_OK) {
                                $uploadDir = ROOT_PATH . '/public/uploads/orders/service_pdfs/';
                                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                                $ext = pathinfo($_FILES["service_pdf_{$sid}"]['name'], PATHINFO_EXTENSION);
                                $filename = "order_{$order['id']}_supplier_{$sid}_" . time() . '.' . $ext;
                                move_uploaded_file($_FILES["service_pdf_{$sid}"]['tmp_name'], $uploadDir . $filename);
                                $pdfId = PurchaseOrderSupplierPdf::create([
                                    'order_id' => $order['id'],
                                    'supplier_id' => $sid,
                                    'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                                    'original_name' => $_FILES["service_pdf_{$sid}"]['name'],
                                    'uploaded_by' => $quotedByName,
                                    'uploaded_at' => date('Y-m-d H:i:s'),
                                ]);
                            }

                            // Limpar materiais anteriores deste fornecedor (evitar duplicatas)
                            PurchaseOrderSupplierMaterial::deleteByOrderAndSupplier($order['id'], $sid);

                            foreach ($matsList as $mat) {
                                if (empty($mat['name'])) continue;
                                
                                // Tentar vincular com material existente
                                $materialId = !empty($mat['material_id']) ? (int) $mat['material_id'] : null;
                                
                                // Se não tem material_id, tentar cadastrar
                                if (!$materialId) {
                                    $newMatId = \App\Models\Material::create([
                                        'name' => $mat['name'],
                                        'specification' => $mat['specification'] ?? '',
                                        'classification' => $mat['classification'] ?? '',
                                        'active' => 1,
                                        'created_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $materialId = $newMatId;
                                }

                                PurchaseOrderSupplierMaterial::create([
                                    'order_id' => $order['id'],
                                    'supplier_id' => $sid,
                                    'pdf_id' => $pdfId,
                                    'material_id' => $materialId,
                                    'material_name' => $mat['name'],
                                    'specification' => $mat['specification'] ?? null,
                                    'classification' => $mat['classification'] ?? null,
                                    'unit' => $mat['unit'] ?? null,
                                    'quantity' => !empty($mat['quantity']) ? (float) $mat['quantity'] : 1,
                                    'unit_price' => !empty($mat['unit_price']) ? (float) $mat['unit_price'] : null,
                                    'total_price' => !empty($mat['total_price']) ? (float) $mat['total_price'] : null,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                        }
                    }
                }

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
        $isEdit = $order['status'] === 'pending_approval';
        
        if ($isEdit) {
            $oldTotal = (float) $order['total_estimated'];
            $diff = $finalTotal - $oldTotal;
            $diffStr = $diff != 0 ? ($diff > 0 ? ' (+R$ ' : ' (-R$ ') . number_format(abs($diff), 2, ',', '.') . ')' : '';
            PurchaseOrderHistory::log(
                $order['id'],
                'quote_edited',
                "Cotação EDITADA por {$quotedByName}. Novo total: R$ " . number_format($finalTotal, 2, ',', '.') . $diffStr . " (anterior: R$ " . number_format($oldTotal, 2, ',', '.') . ")",
                $quotedByName
            );
        } else {
            PurchaseOrderHistory::log(
                $order['id'],
                'quoted',
                "Cotação realizada por {$quotedByName}. Total: R$ " . number_format($finalTotal, 2, ',', '.'),
                $quotedByName
            );
        }

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
        $this->requirePinIfEnabled();
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
                'message' => $order['status'] === 'approved' ? 'Este pedido já foi aprovado.' : ($order['status'] === 'cancelled' ? 'Este pedido foi cancelado.' : 'Este pedido já foi processado.'),
            ]);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($order['id']);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);
        $itemPrices = PurchaseOrderItemPrice::getByOrder($order['id']);
        $comments = Database::fetchAll("SELECT * FROM purchase_order_comments WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);

        $this->view('site.orders.approval', [
            'order' => $order,
            'items' => $items,
            'orderSuppliers' => $orderSuppliers,
            'itemPrices' => $itemPrices,
            'comments' => $comments,
            'token' => $token,
            'pinUser' => \App\Controllers\Site\PinAuthController::getLoggedUser(),
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
        $itemSuppliers = $_POST['item_suppliers'] ?? [];

        if (empty($personName)) {
            $this->setFlash('error', 'Informe seu nome para registrar a decisão.');
            $this->redirect('/pedido/aprovacao/' . $token);
            return;
        }

        if ($action === 'approve') {
            $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);

            // Se tem fornecedores vinculados, precisa ter pelo menos um item selecionado
            if (!empty($orderSuppliers) && empty($itemSuppliers)) {
                $this->setFlash('error', 'Selecione o fornecedor para pelo menos um item.');
                $this->redirect('/pedido/aprovacao/' . $token);
                return;
            }

            $approvedTotal = 0;
            $approvedSupplierIds = [];
            $approvedSupplierNames = [];

            if (!empty($orderSuppliers) && !empty($itemSuppliers)) {
                // Processar aprovação por item
                foreach ($itemSuppliers as $itemId => $supplierId) {
                    $itemId = (int) $itemId;
                    $supplierId = (int) $supplierId;
                    
                    if ($supplierId <= 0) continue;

                    // Buscar o preço deste item neste fornecedor
                    $prices = PurchaseOrderItemPrice::getByOrderAndSupplier($order['id'], $supplierId);
                    foreach ($prices as $p) {
                        if ((int) $p['item_id'] === $itemId) {
                            PurchaseOrderItem::updateById($itemId, [
                                'unit_price' => $p['unit_price'],
                                'total_price' => $p['total_price'],
                                'approved_supplier_id' => $supplierId,
                            ]);
                            $approvedTotal += (float) $p['total_price'];
                            break;
                        }
                    }

                    $approvedSupplierIds[$supplierId] = true;

                    // Marcar no histórico de preços como aprovado
                    Database::query(
                        "UPDATE material_price_history SET was_approved = 1 WHERE order_id = ? AND supplier_id = ? AND material_id = (SELECT material_id FROM purchase_order_items WHERE id = ?)",
                        [$order['id'], $supplierId, $itemId]
                    );
                }

                // Marcar fornecedores que foram aprovados (pode ser mais de um)
                foreach ($orderSuppliers as $os) {
                    if (isset($approvedSupplierIds[$os['supplier_id']])) {
                        PurchaseOrderSupplier::updateById($os['id'], ['approved' => 1, 'status' => 'approved']);
                        $approvedSupplierNames[] = $os['supplier_name'];
                    } else {
                        PurchaseOrderSupplier::updateById($os['id'], ['status' => 'rejected']);
                    }
                }

                // Aplicar financeiros (desconto, acréscimo, IPI, ICMS, frete) ao total aprovado
                // Agrupa subtotal de itens por fornecedor aprovado
                $subtotalBySupplier = [];
                foreach ($itemSuppliers as $itemId => $supplierId) {
                    $supplierId = (int) $supplierId;
                    if ($supplierId <= 0) continue;
                    $prices = PurchaseOrderItemPrice::getByOrderAndSupplier($order['id'], $supplierId);
                    foreach ($prices as $p) {
                        if ((int) $p['item_id'] === (int) $itemId) {
                            if (!isset($subtotalBySupplier[$supplierId])) $subtotalBySupplier[$supplierId] = 0;
                            $subtotalBySupplier[$supplierId] += (float) $p['total_price'];
                            break;
                        }
                    }
                }

                // Somar financeiros de cada fornecedor aprovado
                $totalWithFinancials = $approvedTotal;
                foreach ($orderSuppliers as $os) {
                    $sid = $os['supplier_id'];
                    if (!isset($approvedSupplierIds[$sid]) || !isset($subtotalBySupplier[$sid])) continue;
                    
                    $subItems = $subtotalBySupplier[$sid];
                    $discVal = (float)($os['discount_value'] ?? 0);
                    $discType = $os['discount_type'] ?? 'percent';
                    $surVal = (float)($os['surcharge_value'] ?? 0);
                    $surType = $os['surcharge_type'] ?? 'percent';
                    $ipi = (float)($os['ipi_percent'] ?? 0);
                    $icms = (float)($os['icms_percent'] ?? 0);
                    $freight = (float)($os['freight'] ?? 0);

                    if ($discVal > 0) {
                        $totalWithFinancials -= ($discType === 'percent') ? $subItems * ($discVal / 100) : $discVal;
                    }
                    if ($surVal > 0) {
                        $totalWithFinancials += ($surType === 'percent') ? $subItems * ($surVal / 100) : $surVal;
                    }
                    if ($ipi > 0) $totalWithFinancials += $subItems * ($ipi / 100);
                    if ($icms > 0) $totalWithFinancials += $subItems * ($icms / 100);
                    if ($freight > 0) $totalWithFinancials += $freight;
                }
                $approvedTotal = $totalWithFinancials;
            }

            // Determinar supplier_id principal (o com mais itens, para compatibilidade)
            $supplierCounts = array_count_values(array_map('intval', $itemSuppliers));
            arsort($supplierCounts);
            $primarySupplierId = !empty($supplierCounts) ? array_key_first($supplierCounts) : null;

            PurchaseOrder::updateById($order['id'], [
                'status' => 'approved',
                'supplier_id' => $primarySupplierId,
                'total_estimated' => $approvedTotal > 0 ? $approvedTotal : $order['total_estimated'],
                'approved_by_name' => $personName,
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_notes' => $notes,
            ]);

            $approvalDesc = "Pedido aprovado por {$personName}";
            if (!empty($approvedSupplierNames)) {
                $approvalDesc .= ". Fornecedor(es) aprovado(s): " . implode(', ', $approvedSupplierNames);
            }
            if (!empty($notes)) {
                $approvalDesc .= ". Obs: {$notes}";
            }

            PurchaseOrderHistory::log($order['id'], 'approved', $approvalDesc, $personName);

            // Enviar notificações de conclusão (PDF pronto)
            $this->sendCompletedNotifications($order['id']);

            // Enviar notificação de NF pendente
            $this->sendPaymentPendingNotification($order['id']);

            // Criar checklist de entrega e enviar notificação
            $this->initDeliveryOnApproval($order['id']);

            $this->view('site.orders.approval_success', [
                'order' => $order,
                'action' => 'approved',
                'approvedSupplier' => PurchaseOrderSupplier::getApproved($order['id']),
                'approvedSuppliers' => PurchaseOrderSupplier::getAllApproved($order['id']),
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
                NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'order_rejected');
            }

            // Webhook de rejeição (usa os dados da fase 3 - conclusão)
            $webhookUrl = Setting::get('orders_completed_webhook', '');
            if (!empty($webhookUrl)) {
                $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);
                $supplierNames = !empty($orderSuppliers) ? array_column($orderSuppliers, 'supplier_name') : [];
                $supplierDisplay = !empty($supplierNames) ? implode(', ', $supplierNames) : ($order['supplier_name'] ?? 'N/A');

                $message = "*PEDIDO REJEITADO*\n\n"
                    . "*Pedido:* {$order['code']}\n"
                    . "*Fornecedores cotados:* {$supplierDisplay}\n"
                    . "*Valor cotado:* R$ " . number_format($order['total_estimated'], 2, ',', '.') . "\n"
                    . "*Rejeitado por:* {$personName}\n"
                    . "*Data:* " . date('d/m/Y H:i') . "\n\n"
                    . "*Motivo da rejeição:*\n{$notes}";

                $this->sendWebhook($webhookUrl, [
                    'event' => 'order_rejected',
                    'order_code' => $order['code'],
                    'suppliers' => $supplierNames,
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
                'approvedSuppliers' => [],
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
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);

        $xlsx = new XlsxService();

        // ========================
        // ABA 1: Pedido detalhado
        // ========================
        $xlsx->setSheetName('Pedido ' . $order['code']);
        $xlsx->setColumnWidths([6, 45, 12, 12, 8, 8, 12, 14]);

        // Título
        $xlsx->addRow(['BROOKS CONSTRUTORA - Pedido de Materiais'], 'title');
        $xlsx->addEmptyRow();

        // Informações
        $xlsx->addRow(['Pedido:', $order['code'], '', 'Data:', date('d/m/Y', strtotime($order['created_at']))], 'bold');
        $xlsx->addRow(['Solicitante:', $order['created_by_name'] ?? '-', '', 'Status:', 'Aprovado'], 'bold');

        // Fornecedores aprovados
        if (!empty($approvedSuppliers) && count($approvedSuppliers) > 1) {
            $supplierNames = implode(', ', array_column($approvedSuppliers, 'supplier_name'));
            $xlsx->addRow(['Fornecedores:', $supplierNames], 'bold');
        } else {
            $supplierName = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? '-');
            $xlsx->addRow(['Fornecedor:', $supplierName], 'bold');
        }

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

        // Header da tabela
        $hasMultiSupplier = !empty($approvedSuppliers) && count($approvedSuppliers) > 1;
        $supplierNamesMap = [];
        foreach ($orderSuppliers as $os) {
            $supplierNamesMap[$os['supplier_id']] = $os['supplier_name'];
        }

        if ($hasMultiSupplier) {
            $xlsx->addRow(['#', 'Material', 'Espec.', 'Classificação', 'Unid.', 'Qtd', 'Unit.', 'Total', 'Fornecedor'], 'header');
        } else {
            $xlsx->addRow(['#', 'Material', 'Espec.', 'Classificação', 'Unid.', 'Qtd', 'Unit.', 'Total'], 'header');
        }

        // Itens
        $subtotalInsumos = 0;
        foreach ($items as $i => $item) {
            $unitPrice = $item['unit_price'] ?? 0;
            $totalPrice = $item['total_price'] ?? 0;
            $subtotalInsumos += $totalPrice;

            $row = [
                $i + 1,
                $item['material_name'],
                $item['specification'] ?? '',
                $item['classification'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'],
                $unitPrice,
                $totalPrice,
            ];
            if ($hasMultiSupplier) {
                $row[] = $supplierNamesMap[$item['approved_supplier_id'] ?? 0] ?? '-';
            }
            $xlsx->addRow($row);
        }

        // Totais
        $xlsx->addRow(['', '', '', '', '', '', 'Insumos:', $subtotalInsumos], 'total');
        // Usar subtotal_final do fornecedor aprovado
        $xlsxTotal = ($approvedSupplier && $approvedSupplier['subtotal_final'] > 0) ? $approvedSupplier['subtotal_final'] : $order['total_estimated'];
        if ($xlsxTotal != $subtotalInsumos && $xlsxTotal > 0) {
            $xlsx->addRow(['', '', '', '', '', '', 'TOTAL:', $xlsxTotal], 'total');
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

        // Comparação de fornecedores
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

        // ========================
        // ABA 2: Controle de Orçamento
        // ========================
        $xlsx->addSheet('Controle');
        $xlsx->setColumnWidths([16, 14, 45, 20, 14, 14, 18, 14, 16, 30]);

        // Header
        $xlsx->addRow([
            'ID do Pedido',
            'Data Solicitação',
            'Item / Serviço',
            'Fornecedor',
            'Valor Orçado',
            'Valor Final',
            'Solicitado Por',
            'Status',
            'Data Aprovação',
            'Observações',
        ], 'header');

        // Uma linha por item do pedido
        $statusLabel = 'Aprovado';
        $approvalDate = $order['approved_at'] ? date('d/m/Y', strtotime($order['approved_at'])) : '';
        $supplierFinal = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? '');

        foreach ($items as $item) {
            $xlsx->addRow([
                $order['code'],
                date('d/m/Y', strtotime($order['created_at'])),
                $item['material_name'] . ($item['classification'] ? ' - ' . $item['classification'] : ''),
                $supplierFinal,
                $item['unit_price'] ?? 0,
                $item['total_price'] ?? 0,
                $order['created_by_name'] ?? '',
                $statusLabel,
                $approvalDate,
                $order['description'] ?? '',
            ]);
        }

        // Itens sobressalentes
        $spareItems = PurchaseOrderSpareItem::getByOrder($orderId);
        if (!empty($spareItems)) {
            $xlsx->addEmptyRow();
            $xlsx->addRow(['ITENS SOBRESSALENTES (Comprados na Hora)'], 'bold');
            $xlsx->addRow(['Data', 'Descrição', '', '', '', 'Qtd', 'Valor', 'Onde', 'Por'], 'header');
            $spareTotal = 0;
            foreach ($spareItems as $si) {
                $spareTotal += $si['total_price'];
                $xlsx->addRow([
                    $si['purchased_at'] ? date('d/m/Y', strtotime($si['purchased_at'])) : '-',
                    $si['description'],
                    '', '', '',
                    $si['quantity'],
                    $si['total_price'],
                    $si['supplier_name'] ?? '-',
                    $si['purchased_by'] ?? '-',
                ]);
            }
            $xlsx->addRow(['', '', '', '', '', '', $spareTotal, 'Total Sobressalentes'], 'total');
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
        
        // Primeiro tenta PIN individual (nova tabela pin_users)
        $pinUser = PinUser::findByPin($pin);
        if ($pinUser) {
            // Login por PIN individual — sessão de 30 dias
            $token = PinUser::createSession($pinUser['id']);
            setcookie('pin_session', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]);

            // Mapear role do PIN para role do sistema de permissões
            $roleMap = [
                'buyer' => 'comprador',
                'quoter' => 'cotador',
                'approver' => 'aprovador',
                'payment' => 'financeiro',
                'delivery' => 'comprador',
                'all' => 'comprador',
            ];
            $systemRole = $roleMap[$pinUser['role']] ?? 'comprador';

            $_SESSION['user_id'] = $pinUser['id'];
            $_SESSION['user_name'] = $pinUser['name'];
            $_SESSION['user_email'] = $pinUser['email'] ?? '';
            $_SESSION['user_role'] = $systemRole;
            $_SESSION['pin_auth'] = true;
            $_SESSION['pin_user_id'] = $pinUser['id'];
            $_SESSION['pin_user_role'] = $pinUser['role'];

            $this->redirect('/pedidos');
            return;
        }

        // Fallback: PIN global (configuração antiga)
        $correctPin = Setting::get('orders_pin_code', '');

        if (empty($correctPin) && !$pinUser) {
            $this->setFlash('error', 'PIN não encontrado.');
            $this->redirect('/pedidos/login');
            return;
        }

        if ($pin !== $correctPin) {
            $this->setFlash('error', 'PIN incorreto.');
            $this->redirect('/pedidos/login');
            return;
        }

        // Autenticar como "comprador" via sessão (PIN global)
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
            NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'approval_requested');
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
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
        $baseUrl = $this->getBaseUrl();
        $pdfUrl = "{$baseUrl}/pedido/pdf/{$orderId}";
        $xlsxUrl = "{$baseUrl}/pedido/xlsx/{$orderId}";

        // Total real (com financeiros)
        $realTotal = (float)($order['total_estimated'] ?? 0);
        if (!empty($approvedSuppliers)) {
            $sumFinal = 0;
            foreach ($approvedSuppliers as $as) {
                $sumFinal += (float)($as['subtotal_final'] ?? $as['total'] ?? 0);
            }
            if ($sumFinal > 0) $realTotal = $sumFinal;
        }

        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($realTotal, 2, ',', '.');
            $body = EmailTemplate::purchaseOrderCompleted($order, $items, $pdfUrl, $xlsxUrl, $approvedSuppliers);
            NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'order_approved');
        }

        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($realTotal, 2, ',', '.');

            // Montar lista de fornecedores aprovados
            $supplierNames = [];
            if (!empty($approvedSuppliers)) {
                $supplierNames = array_column($approvedSuppliers, 'supplier_name');
            }
            $supplierDisplay = !empty($supplierNames) ? implode(', ', $supplierNames) : ($order['supplier_name'] ?? 'N/A');

            $message = "*PEDIDO APROVADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor(es):* {$supplierDisplay}\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Aprovado por:* {$order['approved_by_name']}\n\n"
                . "*PDF do pedido:*\n{$pdfUrl}\n\n"
                . "*Planilha do pedido:*\n{$xlsxUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'order_approved',
                'order_code' => $order['code'],
                'suppliers' => $supplierNames,
                'total' => $realTotal,
                'approved_by' => $order['approved_by_name'],
                'pdf_url' => $pdfUrl,
                'xlsx_url' => $xlsxUrl,
                'phone' => Setting::get('orders_completed_phone', ''),
                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    /**
     * Envia notificação de NF/Boleto pendente ao aprovar
     */
    private function sendPaymentPendingNotification(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        if (!$order) return;

        $baseUrl = $this->getBaseUrl();
        $panelUrl = "{$baseUrl}/pedidos";
        $totalFmt = 'R$ ' . number_format((float)$order['total_estimated'], 2, ',', '.');

        $emails = Setting::get('orders_payment_emails', '');
        if (!empty($emails)) {
            $subject = "NF/Boleto Pendente - Pedido {$order['code']} - {$totalFmt}";
            $body = EmailTemplate::purchaseOrderPaymentPending($order, $panelUrl);
            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'payment_pending');
        }

        $webhookUrl = Setting::get('orders_payment_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*NF/BOLETO PENDENTE*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                . "*Valor:* {$totalFmt}\n"
                . "*Aprovado por:* " . ($order['approved_by_name'] ?? '-') . "\n\n"
                . "Acesse o painel para enviar a NF ou boleto:\n{$panelUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'payment_pending',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'panel_url' => $panelUrl,
                'phone' => Setting::get('orders_payment_phone', ''),
                'phone_name' => Setting::get('orders_payment_phone_name', ''),
                'message' => $message,
            ], $orderId);
        }
    }

    /**
     * Cria checklist de entrega e envia notificação ao aprovar
     */
    private function initDeliveryOnApproval(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        if (!$order) return;

        // Inicializar checklist
        PurchaseOrderDelivery::initializeForOrder($orderId);

        // Gerar token se não tem
        $deliveryToken = $order['delivery_token'] ?? '';
        if (empty($deliveryToken)) {
            $deliveryToken = bin2hex(random_bytes(32));
            PurchaseOrder::updateById($orderId, ['delivery_token' => $deliveryToken]);
        }

        // Enviar notificação
        $items = PurchaseOrderItem::getByOrder($orderId);
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
        $baseUrl = $this->getBaseUrl();
        $checklistUrl = "{$baseUrl}/pedido/entrega/{$deliveryToken}";
        $supplierNames = !empty($approvedSuppliers) ? array_column($approvedSuppliers, 'supplier_name') : [];
        $supplierDisplay = !empty($supplierNames) ? implode(', ', $supplierNames) : ($order['supplier_name'] ?? 'N/A');

        $emails = Setting::get('orders_delivery_emails', '');
        if (!empty($emails)) {
            $subject = "Checklist de Entrega - Pedido {$order['code']}";
            $body = EmailTemplate::purchaseOrderDelivery($order, $items, $checklistUrl, $supplierDisplay);
            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'delivery_ready');
        }

        $webhookUrl = Setting::get('orders_delivery_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*CHECKLIST DE ENTREGA DISPONÍVEL*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor(es):* {$supplierDisplay}\n"
                . "*Itens:* " . count($items) . "\n\n"
                . "*Acesse o checklist:*\n{$checklistUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'delivery_checklist_ready',
                'order_code' => $order['code'],
                'suppliers' => $supplierNames,
                'items_count' => count($items),
                'checklist_url' => $checklistUrl,
                'phone' => Setting::get('orders_delivery_phone', ''),
                'phone_name' => Setting::get('orders_delivery_phone_name', ''),
                'message' => $message,
            ], $orderId);
        }

        PurchaseOrderHistory::log($orderId, 'delivery_init', 'Checklist de entrega criado automaticamente na aprovação', $order['approved_by_name'] ?? 'Sistema');
    }

    private function sendWebhook(string $url, array $data, ?int $orderId = null, ?string $eventType = null): void
    {
        if (!$eventType && isset($data['event'])) $eventType = $data['event'];
        if (!$orderId && isset($data['_order_id'])) { $orderId = (int) $data['_order_id']; unset($data['_order_id']); }
        NotificationService::queueWebhook($url, $data, $orderId, $eventType);
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        return $scheme . '://' . $host;
    }

    // ============================
    // COMENTÁRIOS APROVAÇÃO ↔ COTAÇÃO
    // ============================

    /**
     * Aprovador envia pergunta/observação (da tela de aprovação)
     */
    public function approvalComment(string $token = ''): void
    {
        if (!$this->isPost() || empty($token)) { $this->show404(); return; }

        $order = PurchaseOrder::findByApprovalToken($token);
        if (!$order || $order['status'] !== 'pending_approval') {
            $this->setFlash('error', 'Pedido não encontrado ou já processado.');
            $this->redirect('/pedido/aprovacao/' . $token);
            return;
        }

        $name = trim($this->input('person_name', ''));
        $message = trim($this->input('comment_message', ''));

        if (empty($name) || empty($message)) {
            $this->setFlash('error', 'Preencha seu nome e a mensagem.');
            $this->redirect('/pedido/aprovacao/' . $token);
            return;
        }

        // Salvar comentário
        Database::insert('purchase_order_comments', [
            'order_id' => $order['id'],
            'author_name' => $name,
            'author_role' => 'approver',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Registrar no histórico
        PurchaseOrderHistory::log($order['id'], 'comment_approver', "Pergunta de {$name}: {$message}", $name);

        // Enviar notificação para o pessoal da cotação
        $baseUrl = $this->getBaseUrl();
        $quoteUrl = "{$baseUrl}/pedido/cotacao/{$order['quote_token']}";

        $emails = Setting::get('orders_quote_emails', '');
        if (!empty($emails)) {
            $subject = "Pergunta sobre Pedido {$order['code']} - Aprovação";
            $body = EmailTemplate::orderComment($order, $name, $message, $quoteUrl, 'approver');
            NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'approval_comment');
        }

        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $whMessage = "*PERGUNTA SOBRE PEDIDO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*De:* {$name} (aprovação)\n"
                . "*Mensagem:*\n{$message}\n\n"
                . "*Responder/editar cotação:*\n{$quoteUrl}";
            $this->sendWebhook($webhookUrl, [
                'event' => 'approval_comment',
                'order_code' => $order['code'],
                'from' => $name,
                'message' => $message,
                'quote_url' => $quoteUrl,
                'phone' => Setting::get('orders_quote_phone', ''),
                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                'message' => $whMessage,
            ], $order['id']);
        }

        $this->setFlash('success', 'Pergunta enviada! O responsável pela cotação será notificado.');
        $this->redirect('/pedido/aprovacao/' . $token);
    }

    /**
     * Cotador responde observação (da tela de cotação)
     */
    public function quoteComment(string $token = ''): void
    {
        if (!$this->isPost() || empty($token)) { $this->show404(); return; }

        $order = PurchaseOrder::findByQuoteToken($token);
        if (!$order) { $this->show404(); return; }

        $name = trim($this->input('person_name', ''));
        $message = trim($this->input('comment_message', ''));

        if (empty($name) || empty($message)) {
            $this->setFlash('error', 'Preencha seu nome e a mensagem.');
            $this->redirect('/pedido/cotacao/' . $token);
            return;
        }

        Database::insert('purchase_order_comments', [
            'order_id' => $order['id'],
            'author_name' => $name,
            'author_role' => 'quoter',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        PurchaseOrderHistory::log($order['id'], 'comment_quoter', "Resposta de {$name}: {$message}", $name);

        // Notifica o pessoal da aprovação
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$order['approval_token']}";

        $emails = Setting::get('orders_approval_emails', '');
        if (!empty($emails)) {
            $subject = "Resposta sobre Pedido {$order['code']} - Cotação";
            $body = EmailTemplate::orderComment($order, $name, $message, $approvalUrl, 'quoter');
            NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'quote_comment');
        }

        $webhookUrl = Setting::get('orders_approval_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $whMessage = "*RESPOSTA SOBRE PEDIDO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*De:* {$name} (cotação)\n"
                . "*Mensagem:*\n{$message}\n\n"
                . "*Ver aprovação:*\n{$approvalUrl}";
            $this->sendWebhook($webhookUrl, [
                'event' => 'quote_comment',
                'order_code' => $order['code'],
                'from' => $name,
                'message' => $message,
                'approval_url' => $approvalUrl,
                'phone' => Setting::get('orders_approval_phone', ''),
                'phone_name' => Setting::get('orders_approval_phone_name', ''),
                'message' => $whMessage,
            ], $order['id']);
        }

        $this->setFlash('success', 'Resposta enviada! O responsável pela aprovação será notificado.');
        $this->redirect('/pedido/cotacao/' . $token);
    }

    // ============================
    // CHECKLIST PÚBLICO DE ENTREGA
    // ============================

    /**
     * Página pública do checklist de entrega
     */
    public function deliveryPublic(string $token = ''): void
    {
        $this->requirePinIfEnabled();
        if (empty($token)) { $this->show404(); return; }

        $order = Database::fetch("SELECT * FROM purchase_orders WHERE delivery_token = ?", [$token]);
        if (!$order || $order['status'] !== 'approved') { $this->show404(); return; }

        $items = PurchaseOrderItem::getByOrder($order['id']);
        $deliveries = PurchaseOrderDelivery::getByOrder($order['id']);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($order['id']);

        $this->view('site.orders.delivery', [
            'order' => $order,
            'items' => $items,
            'deliveries' => $deliveries,
            'orderSuppliers' => $orderSuppliers,
            'token' => $token,
        ]);
    }

    /**
     * Endpoint AJAX para atualizar entrega (público, via token)
     */
    public function deliveryPublicUpdate(string $token = ''): void
    {
        if (!$this->isPost() || empty($token)) { $this->json(['error' => 'Não autorizado'], 403); return; }

        $order = Database::fetch("SELECT * FROM purchase_orders WHERE delivery_token = ?", [$token]);
        if (!$order) { $this->json(['error' => 'Token inválido'], 403); return; }

        $id = (int) $this->input('id');
        $delivery = PurchaseOrderDelivery::find($id);
        if (!$delivery || $delivery['order_id'] != $order['id']) { $this->json(['error' => 'Item não encontrado'], 404); return; }

        $action = $this->input('delivery_action', '');
        $performedBy = trim($this->input('performed_by', 'Obra'));
        $now = date('Y-m-d H:i:s');
        $description = '';

        switch ($action) {
            case 'mark_delivered':
                $receivedQty = $this->input('received_quantity', '');
                $updateData = ['status' => 'delivered', 'delivered_at' => $now];
                if ($receivedQty !== '') $updateData['received_quantity'] = (float) $receivedQty;
                PurchaseOrderDelivery::updateById($id, $updateData);
                $description = "Marcado como entregue por {$performedBy}" . ($receivedQty ? " (qty recebida: {$receivedQty})" : '');
                break;
            case 'mark_delivered_divergence':
                $receivedQty = $this->input('received_quantity', '');
                $notes = trim($this->input('divergence_notes', ''));
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'divergence',
                    'delivered_at' => $now,
                    'received_quantity' => $receivedQty ? (float) $receivedQty : null,
                    'divergence_notes' => $notes,
                ]);
                $description = "Entregue com divergência por {$performedBy}: {$notes}";
                break;
            case 'mark_checked':
                PurchaseOrderDelivery::updateById($id, ['status' => 'checked', 'checked_by' => $performedBy]);
                $description = "Conferido OK por {$performedBy}";
                break;
            case 'mark_divergence':
                $notes = trim($this->input('divergence_notes', ''));
                PurchaseOrderDelivery::updateById($id, ['status' => 'divergence', 'divergence_notes' => $notes]);
                $description = "Divergência: {$notes} (por {$performedBy})";
                break;
            case 'request_replacement':
                $expectedDate = $this->input('replacement_expected_date', '');
                $notes = trim($this->input('replacement_notes', ''));
                PurchaseOrderDelivery::updateById($id, ['status' => 'replacement_requested', 'replacement_requested_at' => $now, 'replacement_expected_date' => $expectedDate ?: null, 'replacement_notes' => $notes]);
                $description = "Troca solicitada por {$performedBy}";
                break;
            case 'mark_replacement_delivered':
                PurchaseOrderDelivery::updateById($id, ['status' => 'replacement_delivered', 'replacement_delivered_at' => $now]);
                $description = "Troca entregue - conferido por {$performedBy}";
                break;
            case 'reset':
                PurchaseOrderDelivery::updateById($id, ['status' => 'pending', 'delivered_at' => null, 'checked_by' => null, 'divergence_notes' => null]);
                $description = "Desfeito/resetado por {$performedBy}";
                break;
            default:
                $this->json(['error' => 'Ação inválida'], 400);
                return;
        }

        if ($description) {
            Database::insert('purchase_order_delivery_history', [
                'delivery_id' => $id, 'order_id' => $order['id'],
                'action' => $action, 'description' => $description,
                'performed_by' => $performedBy, 'created_at' => $now,
            ]);
        }

        $this->json(['success' => true, 'timestamp' => $now]);
    }

    /**
     * Endpoint AJAX para buscar dados atualizados (polling do público)
     */
    public function deliveryPublicData(string $token = ''): void
    {
        if (empty($token)) { $this->json(['error' => 'Token inválido'], 403); return; }

        $order = Database::fetch("SELECT id, code FROM purchase_orders WHERE delivery_token = ?", [$token]);
        if (!$order) { $this->json(['error' => 'Token inválido'], 403); return; }

        $deliveries = PurchaseOrderDelivery::getByOrder($order['id']);
        $history = Database::fetchAll(
            "SELECT * FROM purchase_order_delivery_history WHERE order_id = ? ORDER BY created_at DESC LIMIT 50",
            [$order['id']]
        );

        $this->json(['deliveries' => $deliveries, 'history' => $history]);
    }

    /**
     * Parse de PDF de fornecedor de serviço (rota pública para tela de cotação)
     */
    public function parseServicePdfPublic(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);

        if (!$orderId || !$supplierId) {
            $this->json(['error' => 'Pedido e fornecedor são obrigatórios.'], 400);
            return;
        }

        $order = PurchaseOrder::find($orderId);
        if (!$order || $order['order_type'] !== 'service') {
            $this->json(['error' => 'Pedido não encontrado ou não é do tipo serviço.'], 400);
            return;
        }

        if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file = $_FILES['pdf'];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['error' => 'Tipo não permitido. Use PDF, JPG, PNG ou WEBP.'], 400);
            return;
        }

        // 1. Salvar o PDF no servidor
        $uploadDir = ROOT_PATH . '/public/uploads/orders/service_pdfs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "order_{$orderId}_supplier_{$supplierId}_" . time() . '.' . $ext;
        $filePath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->json(['error' => 'Falha ao salvar o arquivo.'], 500);
            return;
        }

        // 2. Registrar PDF no banco
        $pdfId = PurchaseOrderSupplierPdf::create([
            'order_id' => $orderId,
            'supplier_id' => $supplierId,
            'file_path' => '/uploads/orders/service_pdfs/' . $filename,
            'original_name' => $file['name'],
            'uploaded_by' => $_POST['uploaded_by'] ?? 'Cotador',
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        // 3. Analisar PDF com IA
        try {
            $openaiKey = Setting::get('openai_api_key', '');
            $model = Setting::get('openai_model', 'gpt-4o');

            if (empty($openaiKey)) {
                $this->json(['success' => true, 'pdf_id' => $pdfId, 'file_path' => '/uploads/orders/service_pdfs/' . $filename, 'materials' => [], 'warning' => 'Chave API não configurada.']);
                return;
            }

            $result = null;
            if ($file['type'] === 'application/pdf' || $ext === 'pdf') {
                $result = $this->parseServicePdfViaApi($filePath, $file['name'], $openaiKey, $model);
            } else {
                $result = $this->parseServiceImageViaApi($filePath, $file['type'], $openaiKey, $model);
            }

            if ($result && isset($result['success']) && !empty($result['materials'])) {
                $this->json([
                    'success' => true,
                    'pdf_id' => $pdfId,
                    'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                    'materials' => $result['materials'],
                    'totals' => $result['totals'] ?? null,
                ]);
            } else {
                $this->json([
                    'success' => true,
                    'pdf_id' => $pdfId,
                    'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                    'materials' => [],
                    'warning' => $result['error'] ?? 'Não foi possível extrair materiais.',
                ]);
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => true,
                'pdf_id' => $pdfId,
                'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                'materials' => [],
                'warning' => 'Erro: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Salvar materiais do PDF do fornecedor de serviço (rota pública)
     */
    public function saveServiceMaterialsPublic(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        $pdfId = (int) ($_POST['pdf_id'] ?? 0);
        $materialsRaw = $_POST['materials'] ?? '[]';
        $materials = json_decode($materialsRaw, true);

        if (!$orderId || !$supplierId) {
            $this->json(['error' => 'Dados obrigatórios faltando.'], 400);
            return;
        }

        if (empty($materials) || !is_array($materials)) {
            $this->json(['error' => 'Nenhum material informado.'], 400);
            return;
        }

        $saved = 0;
        foreach ($materials as $mat) {
            if (empty($mat['name'])) continue;

            PurchaseOrderSupplierMaterial::create([
                'order_id' => $orderId,
                'supplier_id' => $supplierId,
                'pdf_id' => $pdfId ?: null,
                'material_id' => !empty($mat['material_id']) ? (int) $mat['material_id'] : null,
                'material_name' => $mat['name'],
                'description' => $mat['description'] ?? null,
                'specification' => $mat['specification'] ?? null,
                'classification' => $mat['classification'] ?? null,
                'unit' => $mat['unit'] ?? null,
                'quantity' => !empty($mat['quantity']) ? (float) $mat['quantity'] : 1,
                'weight' => !empty($mat['weight']) ? (float) $mat['weight'] : null,
                'unit_price' => !empty($mat['unit_price']) ? (float) $mat['unit_price'] : null,
                'total_price' => !empty($mat['total_price']) ? (float) $mat['total_price'] : null,
                'subtotal' => !empty($mat['subtotal']) ? (float) $mat['subtotal'] : null,
                'discount' => !empty($mat['discount']) ? (float) $mat['discount'] : null,
                'freight' => !empty($mat['freight']) ? (float) $mat['freight'] : null,
                'ipi' => !empty($mat['ipi']) ? (float) $mat['ipi'] : null,
                'icms_st' => !empty($mat['icms_st']) ? (float) $mat['icms_st'] : null,
                'grand_total' => !empty($mat['grand_total']) ? (float) $mat['grand_total'] : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $saved++;
        }

        $this->json(['success' => true, 'saved' => $saved]);
    }

    /**
     * Analisar PDF de serviço via OpenAI Responses API (privado)
     */
    private function parseServicePdfViaApi(string $filePath, string $fileName, string $apiKey, string $model): ?array
    {
        $ch = curl_init('https://api.openai.com/v1/files');
        $cFile = new \CURLFile($filePath, 'application/pdf', $fileName);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cFile, 'purpose' => 'user_data'],
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $uploadResp = curl_exec($ch);
        $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($uploadCode !== 200) return ['error' => 'Erro no upload do PDF.'];

        $uploadData = json_decode($uploadResp, true);
        $fileId = $uploadData['id'] ?? null;
        if (!$fileId) return ['error' => 'Falha ao obter ID do arquivo.'];

        $prompt = 'Analise este PDF de orçamento/nota de materiais de construção civil. '
            . 'Extraia APENAS os itens da TABELA de produtos/materiais do documento. '
            . "\n\nREGRA CRÍTICA: Na tabela, existe uma coluna CÓDIGO (com valores alfanuméricos tipo '10050ROL_PA', '06350ROL_PA', 'ARATORCIDO_') "
            . "e uma coluna DESCRIÇÃO DO MATERIAL (com textos descritivos tipo 'CA 50 10,0 MMR PA', 'ARAME RECOZIDO TORCIDO PA BWG 18 RL - 1KG'). "
            . "O campo 'name' DEVE conter o valor da coluna DESCRIÇÃO DO MATERIAL (texto descritivo legível). "
            . "O campo 'code' DEVE conter o valor da coluna CÓDIGO (alfanumérico). "
            . "NUNCA coloque códigos alfanuméricos no campo 'name'. NUNCA.\n\n"
            . 'NÃO extraia dados do cabeçalho (Ref. Pedido, Cliente, Endereço, OBS PED). '
            . 'Retorne APENAS JSON: {"materials": [...], "totals": {...}}. '
            . 'Cada material: name (DESCRIÇÃO textual, nunca código), code (CÓDIGO alfanumérico), description (complemento), specification, classification, unit (UN, M, KG, etc), '
            . 'quantity (QTDE numérico), weight (PESO numérico ou null), unit_price (preço unitário numérico), total_price (preço total numérico). '
            . 'Em "totals": subtotal, discount, freight, ipi, icms_st, grand_total (numéricos ou null). '
            . 'Valores monetários numéricos (ex: 1500.50). APENAS JSON, sem markdown.';

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'input' => [['role' => 'user', 'content' => [
                    ['type' => 'input_file', 'file_id' => $fileId],
                    ['type' => 'input_text', 'text' => $prompt]
                ]]]
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Deletar arquivo da OpenAI
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey], CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
        curl_exec($ch);
        curl_close($ch);

        if ($httpCode !== 200) return ['error' => 'Erro na API OpenAI.'];

        $result = json_decode($response, true);
        $responseText = '';
        if (isset($result['output'])) {
            foreach ($result['output'] as $output) {
                if (isset($output['content'])) {
                    foreach ($output['content'] as $content) {
                        if (isset($content['text'])) $responseText .= $content['text'];
                    }
                }
            }
        }

        if (empty($responseText)) return ['error' => 'Resposta vazia da IA.'];

        $responseText = preg_replace('/```json\s*/', '', $responseText);
        $responseText = preg_replace('/```\s*/', '', $responseText);
        $parsed = json_decode(trim($responseText), true);

        if (!is_array($parsed)) return ['error' => 'Não foi possível interpretar o documento.'];

        if (isset($parsed['materials'])) {
            return ['success' => true, 'materials' => $parsed['materials'], 'totals' => $parsed['totals'] ?? null];
        }
        return ['success' => true, 'materials' => $parsed, 'totals' => null];
    }

    /**
     * Analisar imagem de serviço via Chat Completions (privado)
     */
    private function parseServiceImageViaApi(string $filePath, string $mimeType, string $apiKey, string $model): ?array
    {
        $content = base64_encode(file_get_contents($filePath));
        $prompt = 'Analise esta imagem de orçamento/nota de materiais de construção. '
            . "REGRA CRÍTICA: O campo 'name' DEVE conter a DESCRIÇÃO DO MATERIAL (texto descritivo como 'CA 50 10,0 MMR PA'), "
            . "NUNCA o CÓDIGO do produto (como '10050ROL_PA'). Códigos vão no campo 'code'. "
            . 'Extraia APENAS itens da TABELA DE MATERIAIS. NÃO use dados do cabeçalho. '
            . 'Retorne JSON: {"materials": [{name (DESCRIÇÃO textual, nunca código), code (CÓDIGO alfanumérico), description, specification, classification, unit, quantity, weight, unit_price, total_price}], "totals": {subtotal, discount, freight, ipi, icms_st, grand_total}}. Valores numéricos. APENAS JSON.';

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Extraia materiais de orçamentos de construção. Retorne APENAS JSON válido.'],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$content}"]]
                    ]]
                ],
                'max_tokens' => 4000,
                'temperature' => 0.1,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return ['error' => 'Erro na API OpenAI.'];

        $result = json_decode($response, true);
        $text = $result['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $parsed = json_decode(trim($text), true);

        if (!is_array($parsed)) return ['error' => 'Não foi possível interpretar.'];
        if (isset($parsed['materials'])) return ['success' => true, 'materials' => $parsed['materials'], 'totals' => $parsed['totals'] ?? null];
        return ['success' => true, 'materials' => $parsed, 'totals' => null];
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
