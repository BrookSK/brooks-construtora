<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderHistory;
use App\Models\PurchaseOrderSupplier;
use App\Models\PurchaseOrderItemPrice;
use App\Models\PurchaseOrderPayment;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderSpareItem;
use App\Models\PurchaseOrderSupplierPdf;
use App\Models\PurchaseOrderSupplierMaterial;
use App\Models\MaterialPriceHistory;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\Setting;
use App\Models\PinUser;
use App\Services\MailService;
use App\Services\XlsxService;
use App\Services\EmailTemplate;
use App\Services\NotificationService;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('orders')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Listagem de pedidos
     */
    public function index(): void
    {
        $orders = PurchaseOrder::allWithSupplier();

        $this->view('admin.orders.index', [
            'orders' => $orders,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Formulário de criação de pedido
     */
    public function create(): void
    {
        $suppliers = Supplier::allActive();
        $materials = Material::allActive();
        $categories = MaterialCategory::all('name ASC');
        $units = MeasurementUnit::all('name ASC');
        $constructionSites = [];
        try {
            $chk = Database::fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'construction_sites' LIMIT 1");
            if (!empty($chk)) {
                $constructionSites = \App\Models\ConstructionSite::allActive();
            }
        } catch (\Exception $e) {}

        // EPIs cadastrados também ficam disponíveis para seleção num novo pedido.
        // Entram como itens não vinculados (material_id nulo), seguindo o mesmo
        // fluxo padrão dos demais itens.
        foreach (\App\Models\Epi::allActive() as $epi) {
            $materials[] = [
                'id' => 'epi-' . $epi['id'],
                'name' => $epi['name'],
                'specification' => $epi['category'] ?? 'EPI',
                'category_name' => $epi['category'] ?? 'EPI',
                'classification' => $epi['ca'] ? 'CA ' . $epi['ca'] : '',
                'unit_abbr' => 'un',
                'unit_name' => 'Unidade',
                'is_epi' => true,
            ];
        }

        $this->view('admin.orders.create', [
            'suppliers' => $suppliers,
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'constructionSites' => $constructionSites,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar novo pedido
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $description = trim($this->input('description', ''));
        $orderType = $this->input('order_type', 'material');
        if (!in_array($orderType, ['material', 'service'])) $orderType = 'material';
        $items = $_POST['items'] ?? [];

        if (empty($items)) {
            $this->setFlash('error', 'Adicione pelo menos um item ao pedido.');
            $this->redirect('/admin/orders/create');
            return;
        }

        // Validar quantidade mínima de cada item (>= 0.01)
        foreach ($items as $item) {
            if (empty($item['material_name'])) continue;
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty < 0.01) {
                $this->setFlash('error', 'A quantidade de "' . ($item['material_name'] ?? 'item') . '" deve ser no mínimo 0,01.');
                $this->redirect('/admin/orders/create');
                return;
            }
        }

        $code = PurchaseOrder::generateCode();
        $quoteToken = PurchaseOrder::generateToken();
        $approvalToken = PurchaseOrder::generateToken();

        $constructionSiteId = $this->input('construction_site_id');
        $constructionSiteId = !empty($constructionSiteId) ? (int) $constructionSiteId : null;

        $orderData = [
            'code' => $code,
            'order_type' => $orderType,
            'supplier_id' => null,
            'status' => 'pending_quote',
            'description' => $description,
            'created_by' => Auth::id(),
            'created_by_name' => Auth::user()['name'],
            'quote_token' => $quoteToken,
            'approval_token' => $approvalToken,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Só incluir construction_site_id se a coluna existir no banco
        if ($constructionSiteId) {
            try {
                $chk = Database::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1");
                if (!empty($chk)) {
                    $orderData['construction_site_id'] = $constructionSiteId;
                }
            } catch (\Exception $e) {}
        }

        $orderId = PurchaseOrder::create($orderData);

        // Decisões de estoque (se vieram do modal de verificação)
        $stockDecisions = $_POST['stock_decisions'] ?? [];

        // Salvar itens
        $itemIndex = 0;
        $stockMovements = [];
        $hasQuoteItems = false;

        foreach ($items as $idx => $item) {
            if (empty($item['material_name'])) continue;
            $itemIndex++;

            $materialId = !empty($item['material_id']) ? (int) $item['material_id'] : null;
            $quantity = (float) ($item['quantity'] ?? 1);

            // Verificar se há decisão de estoque para este item
            $decision = $stockDecisions[$idx] ?? null;
            $sourceType = null;
            $stockFromSiteId = null;
            $stockMovementId = null;

            if ($decision && $decision['action'] !== 'purchase') {
                $stockQty = isset($decision['stock_qty']) ? (float) $decision['stock_qty'] : $quantity;
                $isPartial = in_array($decision['action'], ['stock_partial', 'stock_transfer_partial']);
                $isMulti = in_array($decision['action'], ['stock_multi', 'stock_multi_partial']);
                $sourceType = str_replace(['_partial', '_multi'], '', $decision['action']); // stock_use ou stock_transfer
                $stockFromSiteId = !empty($decision['from_site_id']) ? (int) $decision['from_site_id'] : null;
                $stockFromLocationId = !empty($decision['from_location_id']) ? (int) $decision['from_location_id'] : null;

                // Fallback: se tem site mas não tem location, buscar
                if ($stockFromSiteId && !$stockFromLocationId) {
                    $loc = \App\Models\StockLocation::findBySite($stockFromSiteId);
                    if ($loc) $stockFromLocationId = (int) $loc['id'];
                }

                if ($isMulti) {
                    // Múltiplos estoques
                    $distributions = json_decode($decision['distributions'] ?? '[]', true) ?: [];
                    $purchaseQty = isset($decision['purchase_qty']) ? (float) $decision['purchase_qty'] : 0;

                    foreach ($distributions as $dist) {
                        $distQty = (float) ($dist['quantity'] ?? 0);
                        $distSiteId = (int) ($dist['site_id'] ?? 0);
                        $distLocationId = (int) ($dist['location_id'] ?? 0);
                        $distIsLocal = !empty($dist['is_local']);

                        if ($distQty <= 0) continue;

                        // Fallback location
                        if ($distSiteId && !$distLocationId) {
                            $loc = \App\Models\StockLocation::findBySite($distSiteId);
                            if ($loc) $distLocationId = (int) $loc['id'];
                        }

                        $movData = [
                            'material_id' => $materialId,
                            'from_site_id' => $distSiteId ?: null,
                            'to_site_id' => $constructionSiteId,
                            'from_location_id' => $distLocationId ?: null,
                            'to_location_id' => null,
                            'quantity' => $distQty,
                            'type' => $distIsLocal ? \App\Models\StockMovement::TYPE_EXIT : \App\Models\StockMovement::TYPE_TRANSFER,
                            'status' => \App\Models\StockMovement::STATUS_PENDING,
                            'requested_by' => Auth::user()['name'],
                            'order_id' => $orderId,
                        ];
                        if (!$distIsLocal && $constructionSiteId) {
                            $destLocation = \App\Models\StockLocation::findBySite($constructionSiteId);
                            if ($destLocation) $movData['to_location_id'] = $destLocation['id'];
                        }

                        $movId = \App\Models\StockMovement::record($movData);
                        $stockMovements[] = $movId;

                        // Criar item do pedido para esta parte do estoque
                        PurchaseOrderItem::create([
                            'order_id' => $orderId,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $distQty,
                            'source_type' => $distIsLocal ? 'stock_use' : 'stock_transfer',
                            'stock_from_site_id' => $distSiteId ?: null,
                            'stock_movement_id' => $movId,
                            'unit_price' => $this->getStockUnitPrice($materialId, $distLocationId ?: null, $distSiteId ?: null),
                            'total_price' => $distQty * ($this->getStockUnitPrice($materialId, $distLocationId ?: null, $distSiteId ?: null) ?? 0),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    // Se tem parte pra cotação
                    if ($purchaseQty > 0) {
                        $hasQuoteItems = true;
                        PurchaseOrderItem::create([
                            'order_id' => $orderId,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $purchaseQty,
                            'source_type' => 'purchase',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                } else {
                    // Um único estoque (lógica existente)
                    $fromStockQty = $isPartial ? $stockQty : $quantity;

                    // Registrar movimentação de estoque
                    if ($materialId) {
                        $movData = [
                            'material_id' => $materialId,
                            'from_site_id' => $stockFromSiteId,
                            'to_site_id' => $constructionSiteId,
                            'from_location_id' => $stockFromLocationId,
                            'to_location_id' => null,
                            'quantity' => $fromStockQty,
                            'type' => $sourceType === 'stock_transfer' ? \App\Models\StockMovement::TYPE_TRANSFER : \App\Models\StockMovement::TYPE_EXIT,
                            'status' => \App\Models\StockMovement::STATUS_PENDING,
                            'requested_by' => Auth::user()['name'],
                            'order_id' => $orderId,
                        ];
                        if ($sourceType === 'stock_transfer' && $constructionSiteId) {
                            $destLocation = \App\Models\StockLocation::findBySite($constructionSiteId);
                            if ($destLocation) $movData['to_location_id'] = $destLocation['id'];
                        }
                        $movId = \App\Models\StockMovement::record($movData);
                        $stockMovementId = $movId;
                        $stockMovements[] = $movId;
                    }

                    // Criar item de estoque
                    PurchaseOrderItem::create([
                        'order_id' => $orderId,
                        'material_id' => $materialId,
                        'material_name' => $item['material_name'],
                        'specification' => $item['specification'] ?? '',
                        'classification' => $item['classification'] ?? '',
                        'unit' => $item['unit'] ?? '',
                        'quantity' => $fromStockQty,
                        'source_type' => $sourceType,
                        'stock_from_site_id' => $stockFromSiteId,
                        'stock_movement_id' => $stockMovementId,
                        'unit_price' => $this->getStockUnitPrice($materialId, $stockFromLocationId ?: null, $stockFromSiteId),
                        'total_price' => $fromStockQty * ($this->getStockUnitPrice($materialId, $stockFromLocationId ?: null, $stockFromSiteId) ?? 0),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Se é parcial, criar segundo item para cotação
                    if ($isPartial && ($quantity - $fromStockQty) > 0) {
                        $hasQuoteItems = true;
                        PurchaseOrderItem::create([
                            'order_id' => $orderId,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $quantity - $fromStockQty,
                            'source_type' => 'purchase',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            } else {
                $hasQuoteItems = true;

                PurchaseOrderItem::create([
                    'order_id' => $orderId,
                    'material_id' => $materialId,
                    'material_name' => $item['material_name'],
                    'specification' => $item['specification'] ?? '',
                    'classification' => $item['classification'] ?? '',
                    'unit' => $item['unit'] ?? '',
                    'quantity' => $quantity,
                    'source_type' => null,
                    'stock_from_site_id' => null,
                    'stock_movement_id' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Notificar transporte para movimentações de estoque
        if (!empty($stockMovements)) {
            $stockController = new \App\Controllers\Admin\StockController();
            foreach ($stockMovements as $movId) {
                // Usa reflection para chamar método privado, ou melhor, extrai a lógica
                $this->notifyTransportMovement($movId);
            }
        }

        // Determinar se precisa de cotação/aprovação ou se vai direto
        $requireTransferApproval = Setting::get('orders_require_transfer_approval', '1') === '1';
        $allFromStock = !$hasQuoteItems; // Se não tem itens de compra, todos são de estoque

        // Montar descrição do histórico
        $historyDesc = 'Pedido criado';
        if (!empty($stockMovements)) {
            $historyDesc .= '. ' . count($stockMovements) . ' item(ns) saindo do estoque';
        }
        if ($hasQuoteItems) {
            $historyDesc .= '. Itens restantes enviados para cotação';
        }

        if ($allFromStock && !$requireTransferApproval) {
            // Todos os itens são de estoque/transferência e aprovação de transferência está desativada
            // Pular cotação e aprovação → aprovar automaticamente e ir direto pro checklist
            PurchaseOrder::updateById($orderId, [
                'status' => 'approved',
                'approved_by_name' => 'Sistema (transferência automática)',
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_notes' => 'Aprovação automática - todos os itens saíram do estoque',
            ]);

            PurchaseOrderHistory::log(
                $orderId,
                'approved',
                $historyDesc . '. Aprovação automática (transferência sem aprovação)',
                'Sistema',
                Auth::id()
            );

            // Criar checklist de entrega e enviar notificação
            $this->initDeliveryOnApproval($orderId);

            // Executar movimentações de estoque (deduzir do estoque de origem)
            $this->executeStockMovementsOnApproval($orderId);

            $this->setFlash('success', "Pedido {$code} criado! Itens de estoque aprovados automaticamente. Checklist de entrega criado.");
            $this->redirect('/admin/orders');
            return;
        }

        if ($allFromStock && $requireTransferApproval) {
            // Todos os itens são de estoque/transferência mas precisa de aprovação
            // Pular cotação → ir direto para aprovação
            PurchaseOrder::updateById($orderId, [
                'status' => 'pending_approval',
                'total_estimated' => 0,
            ]);

            PurchaseOrderHistory::log(
                $orderId,
                'created',
                $historyDesc . '. Enviado direto para aprovação (sem cotação - apenas transferência)',
                Auth::user()['name'],
                Auth::id()
            );

            // Enviar notificações de aprovação
            $siteController = new \App\Controllers\Site\PurchaseOrderController();
            $siteController->sendApprovalNotifications($orderId, $approvalToken);

            $this->setFlash('success', "Pedido {$code} criado! Enviado para aprovação (transferência de estoque).");
            $this->redirect('/admin/orders');
            return;
        }

        // Log no histórico (fluxo normal com cotação)
        PurchaseOrderHistory::log(
            $orderId,
            'created',
            $historyDesc,
            Auth::user()['name'],
            Auth::id()
        );

        // Enviar notificações (e-mail + webhook) para cotação (se houver itens para cotar)
        if ($hasQuoteItems) {
            $this->sendQuoteNotifications($orderId, $quoteToken);
        }

        $this->setFlash('success', "Pedido {$code} criado com sucesso! Notificações enviadas para cotação.");
        $this->redirect('/admin/orders');
    }

    /**
     * Parsear PDF de materiais via IA (AJAX)
     */
    public function parsePdf(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
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

        // Extrair conteúdo para enviar à IA
        try {
            $openaiKey = Setting::get('openai_api_key', '');
            $model = Setting::get('openai_model', 'gpt-4o');

            if (empty($openaiKey)) {
                $this->json(['error' => 'Chave API OpenAI não configurada.'], 400);
                return;
            }

            $content = '';
            $mediaType = '';
        
        if ($file['type'] === 'application/pdf') {
            // PDF: Upload via Files API e usar Responses API
            $result = $this->parsePdfViaResponsesApi($file['tmp_name'], $file['name'], $openaiKey, $model);
            if ($result !== null) {
                $this->json($result);
                return;
            }
            $this->json(['error' => 'Falha ao processar PDF. Tente novamente.'], 500);
            return;
        } else {
            // Para imagens: enviar como base64
            $content = base64_encode(file_get_contents($file['tmp_name']));
            $mediaType = $file['type'];

            $messages = [
                ['role' => 'system', 'content' => 'Você é um assistente que analisa documentos de listagem de materiais de construção. Extraia todos os materiais listados e retorne APENAS um JSON array. Cada item deve ter: name (nome do material), specification (tipo/especificação como "mat. Hidraulica", "mat. Civil", "madeira", etc), classification (medida como "100mm", "3/4", "50x40", etc), unit (unidade de medida como "unid", "mts", "m²", "kg", etc), quantity (quantidade numérica, use 1 se não especificado). Se não conseguir identificar algum campo, use string vazia. Retorne APENAS o JSON, sem markdown, sem explicação.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => 'Analise este documento e extraia a lista de materiais com quantidades:'],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$content}"]]
                ]]
            ];
        }

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 4000,
                    'temperature' => 0.1,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openaiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                // Se falhou com formato file, tentar upload via Responses API
                if ($file['type'] === 'application/pdf') {
                    $result = $this->parsePdfViaResponsesApi($file['tmp_name'], $file['name'], $openaiKey, $model);
                    if ($result !== null) {
                        $this->json($result);
                        return;
                    }
                }
                $errorBody = json_decode($response, true);
                $errorMsg = $errorBody['error']['message'] ?? "HTTP {$httpCode}";
                $this->json(['error' => 'Erro na API OpenAI: ' . $errorMsg], 500);
                return;
            }

            $result = json_decode($response, true);
            $text = $result['choices'][0]['message']['content'] ?? '';

            // Limpar possível markdown do response
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = trim($text);

            $materials = json_decode($text, true);

            if (!is_array($materials)) {
                $this->json(['error' => 'Não foi possível interpretar o documento. Tente uma imagem mais nítida.'], 400);
                return;
            }

            $this->json(['success' => true, 'materials' => $materials]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ver detalhes do pedido
     */
    public function show(int $id = 0): void
    {
        $id = $id ?: (int) $this->input('id', 0);
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        $items = PurchaseOrderItem::getByOrder($id);
        $history = PurchaseOrderHistory::getByOrder($id);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($id);
        $itemPrices = PurchaseOrderItemPrice::getByOrder($id);
        $payments = PurchaseOrderPayment::getByOrder($id);
        $deliveries = PurchaseOrderDelivery::getByOrder($id);
        $spareItems = PurchaseOrderSpareItem::getByOrder($id);
        $materials = $order['status'] === 'approved' ? Material::allActive() : [];

        $this->view('admin.orders.show', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'orderSuppliers' => $orderSuppliers,
            'itemPrices' => $itemPrices,
            'payments' => $payments,
            'deliveries' => $deliveries,
            'spareItems' => $spareItems,
            'materials' => $materials,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Reenviar notificação de cotação
     */
    public function resendQuote(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order || !in_array($order['status'], ['pending_quote'])) {
            $this->setFlash('error', 'Pedido não encontrado ou status inválido.');
            $this->redirect('/admin/orders');
            return;
        }

        $this->sendQuoteNotifications($id, $order['quote_token']);

        PurchaseOrderHistory::log($id, 'resent_quote', 'Notificação de cotação reenviada', Auth::user()['name'], Auth::id());
        $this->setFlash('success', 'Notificação de cotação reenviada com sucesso!');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Reenviar notificação de aprovação
     */
    public function resendApproval(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order || !in_array($order['status'], ['pending_approval'])) {
            $this->setFlash('error', 'Pedido não encontrado ou status inválido.');
            $this->redirect('/admin/orders');
            return;
        }

        $this->sendApprovalNotifications($id, $order['approval_token']);

        PurchaseOrderHistory::log($id, 'resent_approval', 'Notificação de aprovação reenviada', Auth::user()['name'], Auth::id());
        $this->setFlash('success', 'Notificação de aprovação reenviada com sucesso!');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Reabrir pedido aprovado para reaprovação (quando aprovaram fornecedor errado)
     */
    public function reopenApproval(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders'); return; }

        $id = (int) $this->input('id', 0);
        $reason = trim($this->input('reason', ''));
        $order = PurchaseOrder::findFull($id);

        if (!$order || $order['status'] !== 'approved') {
            $this->setFlash('error', 'Pedido não encontrado ou não está aprovado.');
            $this->redirect('/admin/orders');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Admin';

        // Salvar quem era o fornecedor aprovado anteriormente (para histórico)
        $previousSupplier = $order['supplier_name'] ?? 'N/A';
        $previousTotal = $order['total_estimated'];

        // Restaurar total_estimated com o menor subtotal_final dos fornecedores cotados
        // (reflete a última cotação editada, não o valor da aprovação que pode ter financeiros diferentes)
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($id);
        $lowestQuotedTotal = PHP_FLOAT_MAX;
        foreach ($orderSuppliers as $os) {
            $osTotal = (float)($os['subtotal_final'] ?? $os['total'] ?? 0);
            if ($osTotal > 0 && $osTotal < $lowestQuotedTotal) {
                $lowestQuotedTotal = $osTotal;
            }
        }
        $restoredTotal = $lowestQuotedTotal != PHP_FLOAT_MAX ? $lowestQuotedTotal : 0;

        // Resetar aprovação — volta para pending_approval com total da última cotação
        PurchaseOrder::updateById($id, [
            'status' => 'pending_approval',
            'supplier_id' => null,
            'approved_by_name' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'total_estimated' => $restoredTotal,
        ]);

        // Resetar fornecedores — remove flags de aprovado/rejeitado
        foreach ($orderSuppliers as $os) {
            PurchaseOrderSupplier::updateById($os['id'], ['approved' => 0, 'status' => 'quoted']);
        }

        // Resetar preços dos itens (unit_price/total_price são preenchidos na aprovação,
        // não devem refletir valores antigos quando o pedido volta para reaprovação)
        Database::query("UPDATE purchase_order_items SET approved_supplier_id = NULL, unit_price = NULL, total_price = NULL WHERE order_id = ?", [$id]);

        // Histórico detalhado
        $historyDesc = "REABERTO PARA REAPROVAÇÃO por {$userName}. Fornecedor anterior: {$previousSupplier} (R$ " . number_format($previousTotal, 2, ',', '.') . ")";
        if ($reason) $historyDesc .= ". Motivo: {$reason}";
        PurchaseOrderHistory::log($id, 'reopened_approval', $historyDesc, $userName, Auth::id());

        // Enviar notificações de reaprovação (respeitando modo de notificação)
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$order['approval_token']}";

        // Resolver modo de notificação (both/site_only/global_only)
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;
        $notifyMode = Setting::get('orders_approval_notify_mode', 'both');
        $siteApprovers = [];
        if (!empty($constructionSiteId)) {
            $siteApprovers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'approval');
        }
        $sendGlobal = in_array($notifyMode, ['both', 'global_only']);
        $sendSite = in_array($notifyMode, ['both', 'site_only']) && !empty($siteApprovers);
        if ($notifyMode === 'site_only' && empty($siteApprovers)) {
            $sendGlobal = true;
        }

        $subject = "⚠️ REAPROVAÇÃO - Pedido {$order['code']}";
        $body = \App\Services\EmailTemplate::orderReopened($order, $previousSupplier, $reason, $approvalUrl, $userName);

        // E-mails globais
        if ($sendGlobal) {
            $emails = Setting::get('orders_approval_emails', '');
            if (!empty($emails)) {
                NotificationService::queueEmails($emails, $subject, $body, $id, 'reopened_approval');
            }
        }

        // E-mails dos aprovadores da obra
        if ($sendSite) {
            $approverEmails = array_filter(array_column($siteApprovers, 'email'));
            if (!empty($approverEmails)) {
                NotificationService::queueEmails(implode(',', $approverEmails), $subject, $body, $id, 'reopened_approval');
            }
        }

        $webhookUrl = Setting::get('orders_approval_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*⚠️ REAPROVAÇÃO DE PEDIDO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor anterior:* {$previousSupplier}\n"
                . "*Valor anterior:* R$ " . number_format($previousTotal, 2, ',', '.') . "\n"
                . "*Reaberto por:* {$userName}\n"
                . ($reason ? "*Motivo:* {$reason}\n" : '')
                . "\n*O pedido precisa ser reaprovado. Acesse:*\n{$approvalUrl}";

            // Webhook para aprovadores da obra
            if ($sendSite) {
                $approverPhones = array_filter(array_column($siteApprovers, 'phone'));
                $approverNames = array_filter(array_column($siteApprovers, 'name'));
                if (!empty($approverPhones)) {
                    $this->sendWebhook($webhookUrl, [
                        'event' => 'reopened_approval',
                        'order_code' => $order['code'],
                        'previous_supplier' => $previousSupplier,
                        'previous_total' => $previousTotal,
                        'reopened_by' => $userName,
                        'reason' => $reason,
                        'approval_url' => $approvalUrl,
                        'phone' => implode(',', $approverPhones),
                        'phone_name' => implode(',', $approverNames),
                        'message' => $message,
                    ], $id);
                }
            }

            // Webhook para globais
            if ($sendGlobal) {
                $this->sendWebhook($webhookUrl, [
                    'event' => 'reopened_approval',
                    'order_code' => $order['code'],
                    'previous_supplier' => $previousSupplier,
                    'previous_total' => $previousTotal,
                    'reopened_by' => $userName,
                    'reason' => $reason,
                    'approval_url' => $approvalUrl,
                    'phone' => Setting::get('orders_approval_phone', ''),
                    'phone_name' => Setting::get('orders_approval_phone_name', ''),
                    'message' => $message,
                ], $id);
            }
        }

        $this->setFlash('success', 'Pedido reaberto para reaprovação! Notificações enviadas.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Marcar pedido como revisado pelo financeiro
     */
    public function financialReview(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        if (!Auth::hasPermission('orders.payment')) {
            $this->setFlash('error', 'Sem permissão.');
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Financeiro';

        PurchaseOrder::updateById($id, [
            'financial_reviewed_at' => date('Y-m-d H:i:s'),
            'financial_reviewed_by' => $userName,
        ]);

        PurchaseOrderHistory::log($id, 'financial_reviewed', "Revisado pelo financeiro: {$userName}", $userName, Auth::id());

        $this->setFlash('success', 'Pedido marcado como revisado pelo financeiro.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Desmarcar revisão financeira
     */
    public function financialUnreview(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        if (!Auth::hasPermission('orders.payment')) {
            $this->setFlash('error', 'Sem permissão.');
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Financeiro';

        PurchaseOrder::updateById($id, [
            'financial_reviewed_at' => null,
            'financial_reviewed_by' => null,
        ]);

        PurchaseOrderHistory::log($id, 'financial_unreview', "Revisão financeira desmarcada por: {$userName}", $userName, Auth::id());

        $this->setFlash('success', 'Revisão financeira desmarcada.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Marcar pedido como comprado
     */
    public function markPurchased(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        if ($order['status'] !== 'approved') {
            $this->setFlash('error', 'Somente pedidos aprovados podem ser marcados como comprados.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $userName = Auth::user()['name'] ?? 'Usuário';

        PurchaseOrder::updateById($id, [
            'purchased_at' => date('Y-m-d H:i:s'),
            'purchased_by' => $userName,
        ]);

        PurchaseOrderHistory::log($id, 'marked_purchased', "Marcado como comprado por: {$userName}", $userName, Auth::id());

        $this->setFlash('success', 'Pedido marcado como comprado.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Desmarcar pedido como comprado
     */
    public function unmarkPurchased(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Usuário';

        PurchaseOrder::updateById($id, [
            'purchased_at' => null,
            'purchased_by' => null,
        ]);

        PurchaseOrderHistory::log($id, 'unmarked_purchased', "Desmarcado como comprado por: {$userName}", $userName, Auth::id());

        $this->setFlash('success', 'Marcação de comprado removida.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Tela de edição financeira (quantidade e preço) de itens após aprovação
     */
    public function financialEdit(string $id = ''): void
    {
        $id = (int) ($id ?: $this->input('id'));
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        if (!Auth::hasPermission('orders.payment')) {
            $this->setFlash('error', 'Sem permissão para edição financeira.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        if ($order['status'] !== 'approved') {
            $this->setFlash('error', 'Somente pedidos aprovados podem ser editados pelo financeiro.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($id);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($id);

        // Buscar obras ativas para o dropdown
        $constructionSites = [];
        try {
            $constructionSites = \App\Models\ConstructionSite::allActive();
        } catch (\Exception $e) {}

        $this->view('admin.orders.financial_edit', [
            'order' => $order,
            'items' => $items,
            'orderSuppliers' => $orderSuppliers,
            'constructionSites' => $constructionSites,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar edição financeira de itens (quantidade, preço, financeiros, obra)
     */
    public function financialUpdate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        if (!Auth::hasPermission('orders.payment')) {
            $this->setFlash('error', 'Sem permissão para edição financeira.');
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id');
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        if ($order['status'] !== 'approved') {
            $this->setFlash('error', 'Somente pedidos aprovados podem ser editados pelo financeiro.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $userName = Auth::user()['name'] ?? 'Financeiro';
        $userId = Auth::id();
        $editedItems = $_POST['items'] ?? [];
        $editedSuppliers = $_POST['suppliers'] ?? [];
        $priceMode = $this->input('price_mode', 'unit');
        $newConstructionSiteId = $this->input('construction_site_id', '');
        $financialNotes = trim($this->input('financial_notes', ''));

        // Capturar estado anterior dos itens para histórico detalhado
        $oldItems = PurchaseOrderItem::getByOrder($id);
        $oldItemsMap = [];
        foreach ($oldItems as $item) {
            $oldItemsMap[$item['id']] = $item;
        }

        // Capturar fornecedores antigos
        $oldSuppliers = PurchaseOrderSupplier::getAllApproved($id);
        $oldSuppliersMap = [];
        foreach ($oldSuppliers as $os) {
            $oldSuppliersMap[$os['id']] = $os;
        }

        $changes = [];
        $financialChanges = [];
        $obraChanged = false;
        $oldObraName = $order['construction_site_name'] ?? '';
        $newObraName = '';
        $subtotalItems = 0;
        $oldTotal = (float) ($order['total_estimated'] ?? 0);

        // ====== PROCESSAR ITENS ======
        foreach ($editedItems as $itemId => $data) {
            $itemId = (int) $itemId;
            if (!isset($oldItemsMap[$itemId])) continue;

            $oldItem = $oldItemsMap[$itemId];
            $newQty = (float) ($data['quantity'] ?? $oldItem['quantity']);

            // Determinar preço com base no modo
            if ($priceMode === 'total') {
                $newTotalPrice = (float) ($data['total_price'] ?? 0);
                $newUnitPrice = $newQty > 0 ? $newTotalPrice / $newQty : 0;
            } else {
                $newUnitPrice = (float) ($data['unit_price'] ?? $oldItem['unit_price']);
                $newTotalPrice = $newQty * $newUnitPrice;
            }

            $oldQty = (float) $oldItem['quantity'];
            $oldUnitPrice = (float) ($oldItem['unit_price'] ?? 0);
            $oldTotalPrice = (float) ($oldItem['total_price'] ?? 0);

            // Detectar se houve mudança
            $qtyChanged = abs($newQty - $oldQty) > 0.001;
            $priceChanged = abs($newUnitPrice - $oldUnitPrice) > 0.001;
            $totalChanged = abs($newTotalPrice - $oldTotalPrice) > 0.001;

            if ($qtyChanged || $priceChanged || $totalChanged) {
                $change = [
                    'item_id' => $itemId,
                    'material_name' => $oldItem['material_name'],
                    'specification' => $oldItem['specification'] ?? '',
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty,
                    'old_unit_price' => $oldUnitPrice,
                    'new_unit_price' => $newUnitPrice,
                    'old_total_price' => $oldTotalPrice,
                    'new_total_price' => $newTotalPrice,
                    'qty_changed' => $qtyChanged,
                    'price_changed' => $priceChanged,
                ];
                $changes[] = $change;
            }

            // Atualizar o item
            PurchaseOrderItem::updateById($itemId, [
                'quantity' => $newQty,
                'unit_price' => $newUnitPrice,
                'total_price' => $newTotalPrice,
            ]);

            $subtotalItems += $newTotalPrice;
        }

        // ====== PROCESSAR FINANCEIROS (por fornecedor) ======
        $newTotal = $subtotalItems;

        foreach ($editedSuppliers as $supData) {
            $supplierOrderId = (int) ($supData['supplier_order_id'] ?? 0);
            $discountValue = (float) ($supData['discount_value'] ?? 0);
            $discountType = $supData['discount_type'] ?? 'percent';
            $surchargeValue = (float) ($supData['surcharge_value'] ?? 0);
            $surchargeType = $supData['surcharge_type'] ?? 'percent';
            $ipiPercent = (float) ($supData['ipi_percent'] ?? 0);
            $icmsPercent = (float) ($supData['icms_percent'] ?? 0);
            $freight = (float) ($supData['freight'] ?? 0);

            // Calcular ajustes no total
            if ($discountValue > 0) {
                $newTotal -= ($discountType === 'percent') ? $subtotalItems * ($discountValue / 100) : $discountValue;
            }
            if ($surchargeValue > 0) {
                $newTotal += ($surchargeType === 'percent') ? $subtotalItems * ($surchargeValue / 100) : $surchargeValue;
            }
            if ($ipiPercent > 0) $newTotal += $subtotalItems * ($ipiPercent / 100);
            if ($icmsPercent > 0) $newTotal += $subtotalItems * ($icmsPercent / 100);
            if ($freight > 0) $newTotal += $freight;

            // Atualizar fornecedor se existir no banco
            if ($supplierOrderId > 0) {
                $oldSup = $oldSuppliersMap[$supplierOrderId] ?? null;
                if ($oldSup) {
                    $finFields = [
                        'discount_value' => ['label' => 'Desconto', 'old' => (float)($oldSup['discount_value'] ?? 0), 'new' => $discountValue],
                        'discount_type' => ['label' => 'Tipo desconto', 'old' => $oldSup['discount_type'] ?? 'percent', 'new' => $discountType],
                        'surcharge_value' => ['label' => 'Acréscimo', 'old' => (float)($oldSup['surcharge_value'] ?? 0), 'new' => $surchargeValue],
                        'surcharge_type' => ['label' => 'Tipo acréscimo', 'old' => $oldSup['surcharge_type'] ?? 'percent', 'new' => $surchargeType],
                        'ipi_percent' => ['label' => 'IPI', 'old' => (float)($oldSup['ipi_percent'] ?? 0), 'new' => $ipiPercent],
                        'icms_percent' => ['label' => 'ICMS', 'old' => (float)($oldSup['icms_percent'] ?? 0), 'new' => $icmsPercent],
                        'freight' => ['label' => 'Frete', 'old' => (float)($oldSup['freight'] ?? 0), 'new' => $freight],
                    ];
                    foreach ($finFields as $field => $info) {
                        if ($field === 'discount_type' || $field === 'surcharge_type') {
                            if ($info['old'] !== $info['new']) {
                                $financialChanges[] = ['field' => $field, 'label' => $info['label'], 'supplier_name' => $oldSup['supplier_name'] ?? 'Geral', 'old_value' => $info['old'] === 'percent' ? '%' : 'R$', 'new_value' => $info['new'] === 'percent' ? '%' : 'R$'];
                            }
                        } else {
                            if (abs($info['old'] - $info['new']) > 0.001) {
                                $financialChanges[] = ['field' => $field, 'label' => $info['label'], 'supplier_name' => $oldSup['supplier_name'] ?? 'Geral', 'old_value' => $info['old'], 'new_value' => $info['new'], 'is_percent' => in_array($field, ['ipi_percent', 'icms_percent']), 'is_money' => $field === 'freight'];
                            }
                        }
                    }
                }
                PurchaseOrderSupplier::updateById($supplierOrderId, [
                    'discount_value' => $discountValue, 'discount_type' => $discountType,
                    'surcharge_value' => $surchargeValue, 'surcharge_type' => $surchargeType,
                    'ipi_percent' => $ipiPercent, 'icms_percent' => $icmsPercent,
                    'freight' => $freight, 'subtotal_items' => $subtotalItems, 'subtotal_final' => $newTotal,
                ]);
            }
        }

        // ====== PROCESSAR OBRA ======
        $oldConstructionSiteId = (int) ($order['construction_site_id'] ?? 0);
        $newConstructionSiteIdInt = $newConstructionSiteId !== '' ? (int) $newConstructionSiteId : 0;
        if ($oldConstructionSiteId !== $newConstructionSiteIdInt) {
            $obraChanged = true;
            PurchaseOrder::updateById($id, ['construction_site_id' => $newConstructionSiteIdInt > 0 ? $newConstructionSiteIdInt : null]);
            if ($newConstructionSiteIdInt > 0) {
                $newSite = \App\Models\ConstructionSite::find($newConstructionSiteIdInt);
                $newObraName = $newSite ? (($newSite['code'] ?? '') . ' - ' . $newSite['name']) : 'ID ' . $newConstructionSiteIdInt;
            } else {
                $newObraName = '(nenhuma)';
            }
        }

        // ====== VERIFICAR SE HOUVE ALGUMA MUDANÇA ======
        $notesChanged = $financialNotes !== ($order['financial_notes'] ?? '');
        if (empty($changes) && empty($financialChanges) && !$obraChanged && !$notesChanged) {
            $this->setFlash('info', 'Nenhuma alteração detectada.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        // Atualizar total do pedido e observações do financeiro
        PurchaseOrder::updateById($id, [
            'total_estimated' => $newTotal,
            'financial_notes' => $financialNotes,
        ]);

        // ====== MONTAR HISTÓRICO DETALHADO ======
        $historyDescription = "Edição financeira realizada por {$userName}.\n";
        $historyDescription .= "Total anterior: R$ " . number_format($oldTotal, 2, ',', '.') . " → Novo total: R$ " . number_format($newTotal, 2, ',', '.') . "\n";
        if ($obraChanged) {
            $oldObraDisplay = !empty($oldObraName) ? $oldObraName : '(nenhuma)';
            $historyDescription .= "Obra: {$oldObraDisplay} → {$newObraName}\n";
        }
        if ($notesChanged) {
            $historyDescription .= "Observações do Financeiro atualizadas.\n";
        }
        if (!empty($financialChanges)) {
            $historyDescription .= "\nAlterações financeiras:\n";
            foreach ($financialChanges as $fc) {
                if (isset($fc['is_percent']) && $fc['is_percent']) {
                    $historyDescription .= "• {$fc['label']} ({$fc['supplier_name']}): {$fc['old_value']}% → {$fc['new_value']}%\n";
                } elseif (isset($fc['is_money']) && $fc['is_money']) {
                    $historyDescription .= "• {$fc['label']} ({$fc['supplier_name']}): R$ " . number_format($fc['old_value'], 2, ',', '.') . " → R$ " . number_format($fc['new_value'], 2, ',', '.') . "\n";
                } else {
                    $historyDescription .= "• {$fc['label']} ({$fc['supplier_name']}): {$fc['old_value']} → {$fc['new_value']}\n";
                }
            }
        }
        if (!empty($changes)) {
            $historyDescription .= "\nAlterações nos itens:\n";
            foreach ($changes as $change) {
                $historyDescription .= "• {$change['material_name']}";
                if (!empty($change['specification'])) $historyDescription .= " ({$change['specification']})";
                $historyDescription .= ":\n";
                if ($change['qty_changed']) {
                    $oldQtyFmt = number_format($change['old_quantity'], $change['old_quantity'] == (int)$change['old_quantity'] ? 0 : 2, ',', '.');
                    $newQtyFmt = number_format($change['new_quantity'], $change['new_quantity'] == (int)$change['new_quantity'] ? 0 : 2, ',', '.');
                    $historyDescription .= "  Quantidade: {$oldQtyFmt} → {$newQtyFmt}\n";
                }
                if ($change['price_changed']) {
                    $historyDescription .= "  Preço unitário: R$ " . number_format($change['old_unit_price'], 2, ',', '.') . " → R$ " . number_format($change['new_unit_price'], 2, ',', '.') . "\n";
                }
                $historyDescription .= "  Total item: R$ " . number_format($change['old_total_price'], 2, ',', '.') . " → R$ " . number_format($change['new_total_price'], 2, ',', '.') . "\n";
            }
        }

        PurchaseOrderHistory::log($id, 'financial_edit', $historyDescription, $userName, $userId);

        // Enviar notificações
        $this->sendFinancialEditNotifications($id, $order, $changes, $financialChanges, $obraChanged, $oldObraName, $newObraName, $userName, $oldTotal, $newTotal);

        $this->setFlash('success', 'Pedido atualizado pelo financeiro com sucesso! Notificações enviadas.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Envia notificações de edição financeira (email + webhook) para cotador, aprovador e financeiro
     * Respeita o notify_mode de cada fase e envia por obra quando configurado.
     */
    private function sendFinancialEditNotifications(int $orderId, array $order, array $changes, array $financialChanges, bool $obraChanged, string $oldObraName, string $newObraName, string $editedBy, float $oldTotal, float $newTotal): void
    {
        // Montar mensagem de mudanças para webhook
        $changesText = "";
        if ($obraChanged) {
            $oldObraDisplay = !empty($oldObraName) ? $oldObraName : '(nenhuma)';
            $changesText .= "*Obra:* {$oldObraDisplay} → {$newObraName}\n\n";
        }
        if (!empty($financialChanges)) {
            $changesText .= "*Financeiros:*\n";
            foreach ($financialChanges as $fc) {
                if (isset($fc['is_percent']) && $fc['is_percent']) {
                    $changesText .= "• {$fc['label']}: {$fc['old_value']}% → {$fc['new_value']}%\n";
                } elseif (isset($fc['is_money']) && $fc['is_money']) {
                    $changesText .= "• {$fc['label']}: R$ " . number_format($fc['old_value'], 2, ',', '.') . " → R$ " . number_format($fc['new_value'], 2, ',', '.') . "\n";
                } else {
                    $changesText .= "• {$fc['label']}: {$fc['old_value']} → {$fc['new_value']}\n";
                }
            }
            $changesText .= "\n";
        }
        if (!empty($changes)) {
            $changesText .= "*Itens alterados:*\n";
            foreach ($changes as $change) {
                $changesText .= "• *{$change['material_name']}*";
                if (!empty($change['specification'])) $changesText .= " ({$change['specification']})";
                $changesText .= "\n";
                if ($change['qty_changed']) {
                    $oldQtyFmt = number_format($change['old_quantity'], $change['old_quantity'] == (int)$change['old_quantity'] ? 0 : 2, ',', '.');
                    $newQtyFmt = number_format($change['new_quantity'], $change['new_quantity'] == (int)$change['new_quantity'] ? 0 : 2, ',', '.');
                    $changesText .= "  Qtd: {$oldQtyFmt} → {$newQtyFmt}\n";
                }
                if ($change['price_changed']) {
                    $changesText .= "  Unit.: R$ " . number_format($change['old_unit_price'], 2, ',', '.') . " → R$ " . number_format($change['new_unit_price'], 2, ',', '.') . "\n";
                }
                $changesText .= "  Total: R$ " . number_format($change['old_total_price'], 2, ',', '.') . " → R$ " . number_format($change['new_total_price'], 2, ',', '.') . "\n";
            }
        }

        $obraInfo = '';
        if (!empty($order['construction_site_name'])) {
            $obraInfo = "*Obra:* {$order['construction_site_code']} - {$order['construction_site_name']}\n";
        }

        $message = "*⚠️ PEDIDO EDITADO PELO FINANCEIRO*\n\n"
            . "*Pedido:* {$order['code']}\n"
            . $obraInfo
            . "*Editado por:* {$editedBy}\n"
            . "*Data/Hora:* " . date('d/m/Y H:i') . "\n"
            . "*Total anterior:* R$ " . number_format($oldTotal, 2, ',', '.') . "\n"
            . "*Novo total:* R$ " . number_format($newTotal, 2, ',', '.') . "\n\n"
            . $changesText;

        // Webhook para cotador (orders_quote_webhook)
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;

        // === FASE COTAÇÃO ===
        $quoteMode = Setting::get('orders_quote_notify_mode', 'global_only');
        $sendQuoteGlobal = in_array($quoteMode, ['both', 'global_only']);
        $sendQuoteSite = in_array($quoteMode, ['both', 'site_only']) && !empty($constructionSiteId);
        if ($quoteMode === 'site_only' && empty($constructionSiteId)) $sendQuoteGlobal = true;

        $quoteWebhook = Setting::get('orders_quote_webhook', '');
        if (!empty(trim($quoteWebhook)) && $sendQuoteGlobal) {
            $this->sendWebhook($quoteWebhook, [
                'event' => 'financial_edit',
                'order_code' => $order['code'],
                'edited_by' => $editedBy,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'changes' => $changes,
                'financial_changes' => $financialChanges,
                'obra_changed' => $obraChanged,
                'phone' => Setting::get('orders_quote_phone', ''),
                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                'message' => $message,
            ], $orderId, 'financial_edit');
        }
        if ($sendQuoteSite && $constructionSiteId) {
            $siteQuoteUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'quote');
            if (!empty($siteQuoteUsers) && !empty(trim($quoteWebhook))) {
                $sitePhones = implode(',', array_filter(array_column($siteQuoteUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($siteQuoteUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($quoteWebhook, [
                        'event' => 'financial_edit',
                        'order_code' => $order['code'],
                        'edited_by' => $editedBy,
                        'old_total' => $oldTotal,
                        'new_total' => $newTotal,
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ], $orderId, 'financial_edit');
                }
            }
        }

        // === FASE APROVAÇÃO ===
        $approvalMode = Setting::get('orders_approval_notify_mode', 'global_only');
        $sendApprovalGlobal = in_array($approvalMode, ['both', 'global_only']);
        $sendApprovalSite = in_array($approvalMode, ['both', 'site_only']) && !empty($constructionSiteId);
        if ($approvalMode === 'site_only' && empty($constructionSiteId)) $sendApprovalGlobal = true;

        $approvalWebhook = Setting::get('orders_approval_webhook', '');
        if (!empty(trim($approvalWebhook)) && $sendApprovalGlobal) {
            $this->sendWebhook($approvalWebhook, [
                'event' => 'financial_edit',
                'order_code' => $order['code'],
                'edited_by' => $editedBy,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'changes' => $changes,
                'financial_changes' => $financialChanges,
                'obra_changed' => $obraChanged,
                'phone' => Setting::get('orders_approval_phone', ''),
                'phone_name' => Setting::get('orders_approval_phone_name', ''),
                'message' => $message,
            ], $orderId, 'financial_edit');
        }
        if ($sendApprovalSite && $constructionSiteId) {
            $siteApprovalUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'approval');
            if (!empty($siteApprovalUsers) && !empty(trim($approvalWebhook))) {
                $sitePhones = implode(',', array_filter(array_column($siteApprovalUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($siteApprovalUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($approvalWebhook, [
                        'event' => 'financial_edit',
                        'order_code' => $order['code'],
                        'edited_by' => $editedBy,
                        'old_total' => $oldTotal,
                        'new_total' => $newTotal,
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ], $orderId, 'financial_edit');
                }
            }
        }

        // === FASE CONCLUSÃO/FINANCEIRO ===
        $completedMode = Setting::get('orders_completed_notify_mode', 'global_only');
        $sendCompletedGlobal = in_array($completedMode, ['both', 'global_only']);
        $sendCompletedSite = in_array($completedMode, ['both', 'site_only']) && !empty($constructionSiteId);
        if ($completedMode === 'site_only' && empty($constructionSiteId)) $sendCompletedGlobal = true;

        $completedWebhook = Setting::get('orders_completed_webhook', '');
        if (!empty(trim($completedWebhook)) && $sendCompletedGlobal) {
            $this->sendWebhook($completedWebhook, [
                'event' => 'financial_edit',
                'order_code' => $order['code'],
                'edited_by' => $editedBy,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'changes' => $changes,
                'financial_changes' => $financialChanges,
                'obra_changed' => $obraChanged,
                'phone' => Setting::get('orders_completed_phone', ''),
                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                'message' => $message,
            ], $orderId, 'financial_edit');
        }
        if ($sendCompletedSite && $constructionSiteId) {
            $siteCompletedUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'completed');
            if (!empty($siteCompletedUsers) && !empty(trim($completedWebhook))) {
                $sitePhones = implode(',', array_filter(array_column($siteCompletedUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($siteCompletedUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($completedWebhook, [
                        'event' => 'financial_edit',
                        'order_code' => $order['code'],
                        'edited_by' => $editedBy,
                        'old_total' => $oldTotal,
                        'new_total' => $newTotal,
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ], $orderId, 'financial_edit');
                }
            }
        }

        // === FASE PAGAMENTO/NF (financeiro) ===
        $paymentMode = Setting::get('orders_payment_notify_mode', 'global_only');
        $sendPaymentGlobal = in_array($paymentMode, ['both', 'global_only']);
        $sendPaymentSite = in_array($paymentMode, ['both', 'site_only']) && !empty($constructionSiteId);
        if ($paymentMode === 'site_only' && empty($constructionSiteId)) $sendPaymentGlobal = true;

        $paymentWebhook = Setting::get('orders_payment_webhook', '');
        if (!empty(trim($paymentWebhook)) && $sendPaymentGlobal) {
            $this->sendWebhook($paymentWebhook, [
                'event' => 'financial_edit',
                'order_code' => $order['code'],
                'edited_by' => $editedBy,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'changes' => $changes,
                'financial_changes' => $financialChanges,
                'obra_changed' => $obraChanged,
                'phone' => Setting::get('orders_payment_phone', ''),
                'phone_name' => Setting::get('orders_payment_phone_name', ''),
                'message' => $message,
            ], $orderId, 'financial_edit');
        }
        if ($sendPaymentSite && $constructionSiteId) {
            $sitePaymentUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'payment');
            if (!empty($sitePaymentUsers) && !empty(trim($paymentWebhook))) {
                $sitePhones = implode(',', array_filter(array_column($sitePaymentUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($sitePaymentUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($paymentWebhook, [
                        'event' => 'financial_edit',
                        'order_code' => $order['code'],
                        'edited_by' => $editedBy,
                        'old_total' => $oldTotal,
                        'new_total' => $newTotal,
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ], $orderId, 'financial_edit');
                }
            }
        }

        // === E-MAILS (todos os grupos) ===
        $quoteEmails = Setting::get('orders_quote_emails', '');
        $approvalEmails = Setting::get('orders_approval_emails', '');
        $completedEmails = Setting::get('orders_completed_emails', '');
        $paymentEmails = Setting::get('orders_payment_emails', '');

        $allEmails = [];
        if ($sendQuoteGlobal) $allEmails = array_merge($allEmails, explode(',', $quoteEmails));
        if ($sendApprovalGlobal) $allEmails = array_merge($allEmails, explode(',', $approvalEmails));
        if ($sendCompletedGlobal) $allEmails = array_merge($allEmails, explode(',', $completedEmails));
        if ($sendPaymentGlobal) $allEmails = array_merge($allEmails, explode(',', $paymentEmails));

        // Adicionar emails de usuários da obra
        if ($constructionSiteId) {
            $phases = [];
            if ($sendQuoteSite) $phases[] = 'quote';
            if ($sendApprovalSite) $phases[] = 'approval';
            if ($sendCompletedSite) $phases[] = 'completed';
            if ($sendPaymentSite) $phases[] = 'payment';
            foreach ($phases as $phase) {
                $siteUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, $phase);
                foreach ($siteUsers as $su) {
                    if (!empty($su['email'])) $allEmails[] = $su['email'];
                }
            }
        }

        $allEmails = array_unique(array_filter(array_map('trim', $allEmails)));

        if (!empty($allEmails)) {
            $subject = "⚠️ Pedido Editado pelo Financeiro - {$order['code']}";
            $body = EmailTemplate::purchaseOrderFinancialEdit($order, $changes, $financialChanges, $obraChanged, $oldObraName, $newObraName, $editedBy, $oldTotal, $newTotal);
            NotificationService::queueEmails(implode(',', $allEmails), $subject, $body, $orderId, 'financial_edit');
        }
    }

    /**
     * Tela de edição de itens do pedido
     */
    public function editItems(string $id = ''): void
    {
        $id = (int) ($id ?: $this->input('id'));
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        // Só permite editar se status pending_quote e cotação não foi iniciada
        if ($order['status'] !== 'pending_quote') {
            $this->setFlash('error', 'Este pedido não pode ser editado (status: ' . $order['status'] . ').');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        if (!empty($order['quote_started_at'])) {
            $this->setFlash('error', 'A cotação já foi iniciada. Não é possível editar os itens.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($id);
        $materials = Material::allActive();
        $categories = MaterialCategory::all('name ASC');
        $units = MeasurementUnit::all('name ASC');

        $constructionSites = [];
        try {
            $constructionSites = \App\Models\ConstructionSite::allActive();
        } catch (\Exception $e) {}

        $this->view('admin.orders.edit_items', [
            'order' => $order,
            'items' => $items,
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
            'constructionSites' => $constructionSites,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar edição de itens do pedido
     */
    public function updateItems(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id');
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        if ($order['status'] !== 'pending_quote') {
            $this->setFlash('error', 'Este pedido não pode ser editado.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        if (!empty($order['quote_started_at'])) {
            $this->setFlash('error', 'A cotação já foi iniciada. Não é possível editar os itens.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $newItems = $_POST['items'] ?? [];
        if (empty($newItems)) {
            $this->setFlash('error', 'Adicione pelo menos um item ao pedido.');
            $this->redirect('/admin/orders/edit-items/' . $id);
            return;
        }

        // Filtrar itens sem material (linhas vazias) e validar que sobra pelo menos 1
        $validItems = array_filter($newItems, fn($item) => !empty($item['material_name']));
        if (empty($validItems)) {
            $this->setFlash('error', 'Adicione pelo menos um item com material selecionado ao pedido.');
            $this->redirect('/admin/orders/edit-items/' . $id);
            return;
        }

        // Atualizar nome do material a partir do cadastro (caso tenha sido renomeado)
        $allMaterials = Material::allActive();
        $materialsMap = [];
        foreach ($allMaterials as $mat) {
            $materialsMap[(int)$mat['id']] = $mat;
        }
        foreach ($validItems as $idx => &$item) {
            $matId = !empty($item['material_id']) ? (int)$item['material_id'] : 0;
            if ($matId > 0 && isset($materialsMap[$matId])) {
                $item['material_name'] = $materialsMap[$matId]['name'];
                if (empty($item['specification']) && !empty($materialsMap[$matId]['specification'])) {
                    $item['specification'] = $materialsMap[$matId]['specification'];
                }
                if (empty($item['classification']) && !empty($materialsMap[$matId]['classification'])) {
                    $item['classification'] = $materialsMap[$matId]['classification'];
                }
            }
        }
        unset($item);

        // Usar apenas itens válidos daqui pra frente
        $newItems = $validItems;

        // Validar quantidades
        foreach ($newItems as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty < 0.01) {
                $this->setFlash('error', 'A quantidade de "' . ($item['material_name'] ?? 'item') . '" deve ser no mínimo 0,01.');
                $this->redirect('/admin/orders/edit-items/' . $id);
                return;
            }
        }

        // Capturar itens antigos para comparação
        $oldItems = PurchaseOrderItem::getByOrder($id);
        $oldItemsData = array_map(function($item) {
            return [
                'material_name' => $item['material_name'],
                'specification' => $item['specification'] ?? '',
                'classification' => $item['classification'] ?? '',
                'quantity' => (float) $item['quantity'],
                'source_type' => $item['source_type'] ?? null,
            ];
        }, $oldItems);

        // Reverter movimentações de estoque antigas
        foreach ($oldItems as $oldItem) {
            if (!empty($oldItem['stock_movement_id'])) {
                // Cancelar a movimentação de estoque
                Database::update('stock_movements', ['status' => 'cancelled'], 'id = ?', [$oldItem['stock_movement_id']]);
            }
        }

        // Deletar itens antigos
        PurchaseOrderItem::deleteByOrder($id);

        // Recriar itens COM decisões de estoque (igual ao store)
        $stockDecisions = $_POST['stock_decisions'] ?? [];
        $newItemsData = [];
        $hasQuoteItems = false;
        $stockMovements = [];
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;

        foreach ($newItems as $idx => $item) {
            $materialId = !empty($item['material_id']) ? (int) $item['material_id'] : null;
            $quantity = (float) ($item['quantity'] ?? 1);

            // Verificar se há decisão de estoque para este item
            $decision = $stockDecisions[$idx] ?? null;

            if ($decision && $decision['action'] !== 'purchase') {
                $stockQty = isset($decision['stock_qty']) ? (float) $decision['stock_qty'] : $quantity;
                $isPartial = in_array($decision['action'], ['stock_partial', 'stock_transfer_partial']);
                $isMulti = in_array($decision['action'], ['stock_multi', 'stock_multi_partial']);
                $sourceType = str_replace(['_partial', '_multi'], '', $decision['action']);
                $stockFromSiteId = !empty($decision['from_site_id']) ? (int) $decision['from_site_id'] : null;
                $stockFromLocationId = !empty($decision['from_location_id']) ? (int) $decision['from_location_id'] : null;

                if ($stockFromSiteId && !$stockFromLocationId) {
                    $loc = \App\Models\StockLocation::findBySite($stockFromSiteId);
                    if ($loc) $stockFromLocationId = (int) $loc['id'];
                }

                if ($isMulti) {
                    $distributions = json_decode($decision['distributions'] ?? '[]', true) ?: [];
                    $purchaseQty = isset($decision['purchase_qty']) ? (float) $decision['purchase_qty'] : 0;

                    foreach ($distributions as $dist) {
                        $distQty = (float) ($dist['quantity'] ?? 0);
                        $distSiteId = (int) ($dist['site_id'] ?? 0);
                        $distLocationId = (int) ($dist['location_id'] ?? 0);
                        $distIsLocal = !empty($dist['is_local']);
                        if ($distQty <= 0) continue;

                        if ($distSiteId && !$distLocationId) {
                            $loc = \App\Models\StockLocation::findBySite($distSiteId);
                            if ($loc) $distLocationId = (int) $loc['id'];
                        }

                        $movData = [
                            'material_id' => $materialId,
                            'from_site_id' => $distSiteId ?: null,
                            'to_site_id' => $constructionSiteId,
                            'from_location_id' => $distLocationId ?: null,
                            'to_location_id' => null,
                            'quantity' => $distQty,
                            'type' => $distIsLocal ? \App\Models\StockMovement::TYPE_EXIT : \App\Models\StockMovement::TYPE_TRANSFER,
                            'status' => \App\Models\StockMovement::STATUS_PENDING,
                            'requested_by' => Auth::user()['name'],
                            'order_id' => $id,
                        ];
                        if (!$distIsLocal && $constructionSiteId) {
                            $destLocation = \App\Models\StockLocation::findBySite($constructionSiteId);
                            if ($destLocation) $movData['to_location_id'] = $destLocation['id'];
                        }

                        $movId = \App\Models\StockMovement::record($movData);
                        $stockMovements[] = $movId;

                        PurchaseOrderItem::create([
                            'order_id' => $id,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $distQty,
                            'source_type' => $distIsLocal ? 'stock_use' : 'stock_transfer',
                            'stock_from_site_id' => $distSiteId ?: null,
                            'stock_movement_id' => $movId,
                            'unit_price' => $this->getStockUnitPrice($materialId, $distLocationId ?: null, $distSiteId ?: null),
                            'total_price' => $distQty * ($this->getStockUnitPrice($materialId, $distLocationId ?: null, $distSiteId ?: null) ?? 0),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($purchaseQty > 0) {
                        $hasQuoteItems = true;
                        PurchaseOrderItem::create([
                            'order_id' => $id,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $purchaseQty,
                            'source_type' => 'purchase',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                } else {
                    $fromStockQty = $isPartial ? $stockQty : $quantity;

                    if ($materialId) {
                        $movData = [
                            'material_id' => $materialId,
                            'from_site_id' => $stockFromSiteId,
                            'to_site_id' => $constructionSiteId,
                            'from_location_id' => $stockFromLocationId,
                            'to_location_id' => null,
                            'quantity' => $fromStockQty,
                            'type' => $sourceType === 'stock_transfer' ? \App\Models\StockMovement::TYPE_TRANSFER : \App\Models\StockMovement::TYPE_EXIT,
                            'status' => \App\Models\StockMovement::STATUS_PENDING,
                            'requested_by' => Auth::user()['name'],
                            'order_id' => $id,
                        ];
                        if ($sourceType === 'stock_transfer' && $constructionSiteId) {
                            $destLocation = \App\Models\StockLocation::findBySite($constructionSiteId);
                            if ($destLocation) $movData['to_location_id'] = $destLocation['id'];
                        }
                        $movId = \App\Models\StockMovement::record($movData);
                        $stockMovements[] = $movId;
                    }

                    PurchaseOrderItem::create([
                        'order_id' => $id,
                        'material_id' => $materialId,
                        'material_name' => $item['material_name'],
                        'specification' => $item['specification'] ?? '',
                        'classification' => $item['classification'] ?? '',
                        'unit' => $item['unit'] ?? '',
                        'quantity' => $fromStockQty,
                        'source_type' => $sourceType,
                        'stock_from_site_id' => $stockFromSiteId,
                        'stock_movement_id' => $movId ?? null,
                        'unit_price' => $this->getStockUnitPrice($materialId, $stockFromLocationId ?: null, $stockFromSiteId),
                        'total_price' => $fromStockQty * ($this->getStockUnitPrice($materialId, $stockFromLocationId ?: null, $stockFromSiteId) ?? 0),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    if ($isPartial && ($quantity - $fromStockQty) > 0) {
                        $hasQuoteItems = true;
                        PurchaseOrderItem::create([
                            'order_id' => $id,
                            'material_id' => $materialId,
                            'material_name' => $item['material_name'],
                            'specification' => $item['specification'] ?? '',
                            'classification' => $item['classification'] ?? '',
                            'unit' => $item['unit'] ?? '',
                            'quantity' => $quantity - $fromStockQty,
                            'source_type' => 'purchase',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            } else {
                $hasQuoteItems = true;
                PurchaseOrderItem::create([
                    'order_id' => $id,
                    'material_id' => $materialId,
                    'material_name' => $item['material_name'],
                    'specification' => $item['specification'] ?? '',
                    'classification' => $item['classification'] ?? '',
                    'unit' => $item['unit'] ?? '',
                    'quantity' => $quantity,
                    'source_type' => null,
                    'stock_from_site_id' => null,
                    'stock_movement_id' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $newItemsData[] = [
                'material_name' => $item['material_name'],
                'specification' => $item['specification'] ?? '',
                'classification' => $item['classification'] ?? '',
                'quantity' => $quantity,
                'source_type' => null,
            ];
        }

        // Notificar transporte para movimentações de estoque
        if (!empty($stockMovements)) {
            foreach ($stockMovements as $movId) {
                $this->notifyTransportMovement($movId);
            }
        }

        // Calcular diferenças para registro
        $changes = $this->calculateItemChanges($oldItemsData, $newItemsData);

        // Registrar edição na tabela de edições
        Database::insert('purchase_order_edits', [
            'order_id' => $id,
            'edited_by_name' => Auth::user()['name'],
            'edited_by_user_id' => Auth::id(),
            'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Registrar no histórico
        $changesSummary = [];
        if (!empty($changes['added'])) $changesSummary[] = count($changes['added']) . ' item(ns) adicionado(s)';
        if (!empty($changes['removed'])) $changesSummary[] = count($changes['removed']) . ' item(ns) removido(s)';
        if (!empty($changes['changed'])) $changesSummary[] = count($changes['changed']) . ' item(ns) alterado(s)';

        // Atualizar obra se foi alterada
        $newConstructionSiteId = $this->input('construction_site_id', '');
        $oldConstructionSiteId = (int) ($order['construction_site_id'] ?? 0);
        $newConstructionSiteIdInt = $newConstructionSiteId !== '' ? (int) $newConstructionSiteId : 0;
        if ($oldConstructionSiteId !== $newConstructionSiteIdInt) {
            PurchaseOrder::updateById($id, ['construction_site_id' => $newConstructionSiteIdInt > 0 ? $newConstructionSiteIdInt : null]);
            $newSiteName = '';
            if ($newConstructionSiteIdInt > 0) {
                $newSite = \App\Models\ConstructionSite::find($newConstructionSiteIdInt);
                $newSiteName = $newSite ? (($newSite['code'] ?? '') . ' - ' . $newSite['name']) : 'ID ' . $newConstructionSiteIdInt;
            }
            $oldSiteName = !empty($order['construction_site_name']) ? (($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']) : '(nenhuma)';
            $changesSummary[] = "obra alterada: {$oldSiteName} → " . ($newSiteName ?: '(nenhuma)');
        }

        PurchaseOrderHistory::log(
            $id,
            'items_edited',
            'Itens do pedido editados: ' . implode(', ', $changesSummary),
            Auth::user()['name'],
            Auth::id()
        );

        // Enviar notificações de edição
        $this->sendEditNotifications($id, $changes);

        $this->setFlash('success', 'Itens do pedido atualizados! Notificações reenviadas.');
        $this->redirect('/admin/orders/show/' . $id);
    }

    /**
     * Calcula diferenças entre itens antigos e novos
     */
    private function calculateItemChanges(array $oldItems, array $newItems): array
    {
        $changes = ['added' => [], 'removed' => [], 'changed' => []];

        // Indexar por nome + spec + class para comparação
        $oldByKey = [];
        foreach ($oldItems as $item) {
            // Ignorar itens de estoque (source_type != null e != purchase)
            if (!empty($item['source_type']) && $item['source_type'] !== 'purchase') continue;
            $key = $item['material_name'] . '|' . ($item['specification'] ?? '') . '|' . ($item['classification'] ?? '');
            $oldByKey[$key] = $item;
        }

        $newByKey = [];
        foreach ($newItems as $item) {
            $key = $item['material_name'] . '|' . ($item['specification'] ?? '') . '|' . ($item['classification'] ?? '');
            $newByKey[$key] = $item;
        }

        // Removidos (estava no antigo, não está no novo)
        foreach ($oldByKey as $key => $item) {
            if (!isset($newByKey[$key])) {
                $changes['removed'][] = $item;
            }
        }

        // Adicionados (está no novo, não estava no antigo)
        foreach ($newByKey as $key => $item) {
            if (!isset($oldByKey[$key])) {
                $changes['added'][] = $item;
            }
        }

        // Alterados (existe nos dois mas com quantidade diferente)
        foreach ($newByKey as $key => $item) {
            if (isset($oldByKey[$key]) && (float) $oldByKey[$key]['quantity'] !== (float) $item['quantity']) {
                $changes['changed'][] = [
                    'material_name' => $item['material_name'],
                    'specification' => $item['specification'] ?? '',
                    'old_quantity' => (float) $oldByKey[$key]['quantity'],
                    'new_quantity' => (float) $item['quantity'],
                ];
            }
        }

        return $changes;
    }

    /**
     * Enviar notificações de edição de pedido (email + webhook)
     */
    private function sendEditNotifications(int $orderId, array $changes): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $quoteUrl = "{$baseUrl}/pedido/cotacao/{$order['quote_token']}";

        // Montar resumo de alterações para webhook
        $changesText = '';
        if (!empty($changes['added'])) {
            $changesText .= "*Itens adicionados:*\n";
            foreach ($changes['added'] as $item) {
                $changesText .= "  + {$item['material_name']} - Qtd: {$item['quantity']}\n";
            }
        }
        if (!empty($changes['removed'])) {
            $changesText .= "*Itens removidos:*\n";
            foreach ($changes['removed'] as $item) {
                $changesText .= "  - {$item['material_name']} - Qtd: {$item['quantity']}\n";
            }
        }
        if (!empty($changes['changed'])) {
            $changesText .= "*Itens alterados:*\n";
            foreach ($changes['changed'] as $item) {
                $changesText .= "  • {$item['material_name']}: Qtd {$item['old_quantity']} → {$item['new_quantity']}\n";
            }
        }

        // Obra info
        $obraInfo = '';
        if (!empty($order['construction_site_name'])) {
            $obraInfo = "*Obra:* {$order['construction_site_code']} - {$order['construction_site_name']}\n";
        }

        // E-mail
        $emails = Setting::get('orders_quote_emails', '');
        if (!empty($emails)) {
            $subject = "⚠️ Pedido EDITADO - {$order['code']}";
            if (!empty($order['construction_site_name'])) {
                $subject .= " - Obra: {$order['construction_site_name']}";
            }
            $body = EmailTemplate::purchaseOrderEdited($order, $items, $quoteUrl, $changes);
            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'order_edited');
        }

        // Webhook
        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty($webhookUrl)) {
            $message = "*⚠️ PEDIDO EDITADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . $obraInfo
                . "*Editado por:* " . Auth::user()['name'] . "\n"
                . "*Data:* " . date('d/m/Y H:i') . "\n\n"
                . $changesText . "\n"
                . "*Link para cotação:*\n{$quoteUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'order_edited',
                'order_code' => $order['code'],
                'edited_by' => Auth::user()['name'],
                'changes' => $changes,
                'items_count' => count($items),
                'quote_url' => $quoteUrl,
                'phone' => Setting::get('orders_quote_phone', ''),
                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                'message' => $message,
            ], $orderId, 'order_edited');
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancel(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::findFull($id);

        if (!$order || in_array($order['status'], ['cancelled'])) {
            $this->setFlash('error', 'Pedido não encontrado ou já está cancelado.');
            $this->redirect('/admin/orders');
            return;
        }

        $userName = Auth::user()['name'] ?? 'Admin';

        PurchaseOrder::updateById($id, ['status' => 'cancelled']);
        PurchaseOrderHistory::log($id, 'cancelled', "Pedido cancelado por {$userName}", $userName, Auth::id());

        // Notificar todos os envolvidos (cotação + aprovação)
        $this->sendCancelledNotifications($id, $order, $userName);

        $this->setFlash('success', 'Pedido cancelado e notificações enviadas.');
        $this->redirect('/admin/orders');
    }

    private function sendCancelledNotifications(int $orderId, array $order, string $cancelledBy): void
    {
        // Buscar total real (com financeiros) do fornecedor aprovado
        $realTotal = (float)($order['total_estimated'] ?? 0);
        $approvedSupplier = PurchaseOrderSupplier::getApproved($orderId);
        if ($approvedSupplier && $approvedSupplier['subtotal_final'] > 0) {
            $realTotal = (float)$approvedSupplier['subtotal_final'];
        }
        $totalFmt = 'R$ ' . number_format($realTotal, 2, ',', '.');

        // E-mail para cotação
        $emails = Setting::get('orders_quote_emails', '');
        if (!empty($emails)) {
            $subject = "Pedido CANCELADO - {$order['code']}";
            $body = EmailTemplate::purchaseOrderCancelled($order, $cancelledBy);
            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'order_cancelled');
        }
        // E-mail para aprovação
        $emails2 = Setting::get('orders_approval_emails', '');
        if (!empty($emails2) && $emails2 !== $emails) {
            $subject = "Pedido CANCELADO - {$order['code']}";
            $body = EmailTemplate::purchaseOrderCancelled($order, $cancelledBy);
            NotificationService::queueEmails($emails2, $subject, $body, $orderId, 'order_cancelled');
        }

        // Webhook para cotação
        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*PEDIDO CANCELADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Valor:* {$totalFmt}\n"
                . "*Cancelado por:* {$cancelledBy}\n"
                . "*Data:* " . date('d/m/Y H:i');
            $this->sendWebhook($webhookUrl, [
                'event' => 'order_cancelled', 'order_code' => $order['code'],
                'total' => $realTotal, 'cancelled_by' => $cancelledBy,
                'phone' => Setting::get('orders_quote_phone', ''),
                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                'message' => $message,
            ], $orderId);
        }
        // Webhook para aprovação
        $webhookUrl2 = Setting::get('orders_approval_webhook', '');
        if (!empty(trim($webhookUrl2)) && $webhookUrl2 !== $webhookUrl) {
            $message = "*PEDIDO CANCELADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Valor:* {$totalFmt}\n"
                . "*Cancelado por:* {$cancelledBy}\n"
                . "*Data:* " . date('d/m/Y H:i');
            $this->sendWebhook($webhookUrl2, [
                'event' => 'order_cancelled', 'order_code' => $order['code'],
                'total' => $realTotal, 'cancelled_by' => $cancelledBy,
                'phone' => Setting::get('orders_approval_phone', ''),
                'phone_name' => Setting::get('orders_approval_phone_name', ''),
                'message' => $message,
            ], $orderId);
        }
    }

    /**
     * Deletar pedido (apenas super_admin)
     */
    public function delete(): void
    {
        if (!$this->isPost() || !Auth::isSuperAdmin()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        $orderCode = $order['code'];
        $deletedBy = Auth::user()['name'];
        $deletedAt = date('d/m/Y H:i');

        // Deletar dados relacionados
        Database::delete('purchase_order_item_prices', 'order_id = ?', [$id]);
        Database::delete('purchase_order_history', 'order_id = ?', [$id]);
        Database::delete('purchase_order_suppliers', 'order_id = ?', [$id]);
        Database::delete('purchase_order_items', 'order_id = ?', [$id]);
        Database::delete('material_price_history', 'order_id = ?', [$id]);
        PurchaseOrder::deleteById($id);

        // Enviar e-mail de notificação de exclusão
        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            try {
                $mailService = new MailService();
                $subject = "Pedido DELETADO - {$orderCode}";
                $body = EmailTemplate::purchaseOrderDeleted($orderCode, $deletedBy, $deletedAt);
                $emailList = array_map('trim', explode(',', $emails));
                foreach ($emailList as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mailService->send($email, $subject, $body, true);
                    }
                }
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail de exclusão: " . $e->getMessage());
            }
        }

        $this->setFlash('success', "Pedido {$orderCode} deletado permanentemente. Notificação enviada.");
        $this->redirect('/admin/orders');
    }

    /**
     * Limpar histórico de preços (apenas super_admin)
     */
    public function clearPriceHistory(): void
    {
        if (!$this->isPost() || !Auth::isSuperAdmin()) {
            $this->redirect('/admin/orders/price-history');
            return;
        }

        Database::query("TRUNCATE TABLE material_price_history");

        $this->setFlash('success', 'Histórico de preços limpo com sucesso.');
        $this->redirect('/admin/orders/price-history');
    }

    /**
     * Configurações de e-mail e webhook para pedidos
     */
    public function settings(): void
    {
        if (!Auth::hasPermission('orders.settings')) {
            $this->redirect('/admin/orders');
            return;
        }

        $settings = Setting::getGroup('orders_');
        $settings['spare_items_weekly_budget'] = Setting::get('spare_items_weekly_budget', '1000');

        $this->view('admin.orders.settings', [
            'settings' => $settings,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar configurações
     */
    public function updateSettings(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('orders.settings')) {
            $this->redirect('/admin/orders/settings');
            return;
        }

        $keys = [
            'orders_quote_emails',
            'orders_quote_webhook',
            'orders_quote_phone',
            'orders_quote_phone_name',
            'orders_quote_notify_mode',
            'orders_approval_emails',
            'orders_approval_webhook',
            'orders_approval_phone',
            'orders_approval_phone_name',
            'orders_approval_notify_mode',
            'orders_completed_emails',
            'orders_completed_webhook',
            'orders_completed_phone',
            'orders_completed_phone_name',
            'orders_completed_notify_mode',
            'orders_payment_emails',
            'orders_payment_webhook',
            'orders_payment_phone',
            'orders_payment_phone_name',
            'orders_payment_notify_mode',
            'orders_delivery_emails',
            'orders_delivery_webhook',
            'orders_delivery_phone',
            'orders_delivery_phone_name',
            'orders_delivery_notify_mode',
            'orders_spare_emails',
            'orders_spare_webhook',
            'orders_spare_phone',
            'orders_spare_phone_name',
            'orders_spare_notify_mode',
            'orders_transport_emails',
            'orders_transport_webhook',
            'orders_transport_phone',
            'orders_transport_phone_name',
            'orders_transport_notify_mode',
            'orders_quote_send_webhook',
            'orders_quote_send_phone',
            'orders_quote_send_phone_name',
            'orders_quote_default_message',
            'orders_pin_code',
            'orders_pin_global_active',
            'require_pin_login',
            'orders_require_pin_login',
            'orders_require_transfer_approval',
            'orders_notify_requester_delivery',
            'spare_items_weekly_budget',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = trim($_POST[$key]);
            }
        }

        // Checkbox: se não veio, é 0
        if (!isset($_POST['orders_require_pin_login'])) {
            $data['orders_require_pin_login'] = '0';
        }
        if (!isset($_POST['orders_require_transfer_approval'])) {
            $data['orders_require_transfer_approval'] = '0';
        }
        if (!isset($_POST['orders_pin_global_active'])) {
            $data['orders_pin_global_active'] = '0';
        }
        if (!isset($_POST['orders_notify_requester_delivery'])) {
            $data['orders_notify_requester_delivery'] = '0';
        }

        Setting::setMultiple($data);

        $this->setFlash('success', 'Configurações de pedidos atualizadas com sucesso!');
        $this->redirect('/admin/orders/settings');
    }

    /**
     * Testar webhook (AJAX)
     */
    public function testWebhook(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('orders.settings')) {
            $this->json(['error' => 'Sem permissão.'], 403);
            return;
        }

        $url = trim($this->input('url', ''));
        $payload = trim($this->input('payload', '{}'));

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->json(['error' => 'URL inválida.'], 400);
            return;
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Test: true',
                    'User-Agent: Brooks-Construtora-Webhook/1.0',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->json([
                    'success' => false,
                    'error' => 'Erro cURL: ' . $error,
                    'response' => '',
                    'http_code' => 0,
                ]);
            } else {
                $this->json([
                    'success' => $httpCode >= 200 && $httpCode < 400,
                    'http_code' => $httpCode,
                    'response' => mb_substr($response, 0, 2000),
                    'error' => $httpCode >= 400 ? "HTTP {$httpCode}" : null,
                ]);
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'response' => '',
            ]);
        }
    }

    /**
     * Lista de Compras — todos os itens de pedidos aprovados e não comprados
     */
    public function shoppingList(): void
    {
        // Buscar pedidos aprovados que ainda NÃO foram marcados como comprados
        $orders = Database::fetchAll(
            "SELECT po.id, po.code, po.created_at, po.created_by_name, po.description,
                    cs.name as construction_site_name, cs.code as construction_site_code,
                    s.name as supplier_name
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
             WHERE po.status = 'approved' AND po.purchased_at IS NULL
             ORDER BY po.created_at DESC"
        );

        // Buscar todos os itens desses pedidos
        $orderIds = array_column($orders, 'id');
        $items = [];
        if (!empty($orderIds)) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $items = Database::fetchAll(
                "SELECT poi.*, po.code as order_code, po.id as order_id,
                        cs.name as construction_site_name, cs.code as construction_site_code
                 FROM purchase_order_items poi
                 JOIN purchase_orders po ON poi.order_id = po.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE poi.order_id IN ({$placeholders})
                 ORDER BY poi.specification ASC, poi.material_name ASC",
                $orderIds
            );
        }

        // Agrupar por especificação (case-insensitive)
        $grouped = [];
        foreach ($items as $item) {
            $spec = trim($item['specification'] ?? '');
            $key = mb_strtolower($spec, 'UTF-8');
            if ($key === '') {
                $key = 'sem especificação';
            }
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label' => $spec !== '' ? $spec : 'Sem Especificação',
                    'items' => [],
                ];
            }
            $grouped[$key]['items'][] = $item;
        }
        ksort($grouped);

        $this->view('admin.orders.shopping_list', [
            'orders' => $orders,
            'items' => $items,
            'grouped' => $grouped,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Exportar planilha CSV
     */
    public function export(string $id = ''): void
    {
        $orderId = (int) $id;
        $order = PurchaseOrder::findFull($orderId);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
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

        // Informações do pedido
        $xlsx->addRow(['Pedido:', $order['code'], '', 'Data:', date('d/m/Y', strtotime($order['created_at']))], 'bold');
        $xlsx->addRow(['Solicitante:', $order['created_by_name'] ?? '-', '', 'Status:', $this->statusLabel($order['status'])], 'bold');
        
        if (!empty($approvedSuppliers) && count($approvedSuppliers) > 1) {
            $xlsx->addRow(['Fornecedores:', implode(', ', array_column($approvedSuppliers, 'supplier_name'))], 'bold');
        } else {
            $supplierName = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? 'Pendente');
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

        if (!empty($order['approved_by_name'])) {
            $xlsx->addRow(['Aprovado por:', $order['approved_by_name'], '', 'Data:', $order['approved_at'] ? date('d/m/Y H:i', strtotime($order['approved_at'])) : ''], 'bold');
        }

        $xlsx->addEmptyRow();

        // Header da tabela de itens
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

            $materialLabel = $item['material_name'];
            if (!empty($item['already_purchased'])) {
                $apQty = !empty($item['already_purchased_qty']) ? number_format($item['already_purchased_qty'], 2, ',', '.') . ' un' : '';
                $apPrice = $item['already_purchased_price'] ? 'R$ ' . number_format($item['already_purchased_price'], 2, ',', '.') : '';
                $materialLabel .= ' [JÁ COMPRADO' . ($apQty ? ' ' . $apQty : '') . ($apPrice ? ' - ' . $apPrice : '') . ']';
            }

            $row = [
                $i + 1,
                $materialLabel,
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

        // Subtotal e Total
        $xlsx->addRow(['', '', '', '', '', '', 'Insumos:', $subtotalInsumos], 'total');
        
        $xlsxTotal = ($approvedSupplier && $approvedSupplier['subtotal_final'] > 0) ? $approvedSupplier['subtotal_final'] : $order['total_estimated'];
        if ($xlsxTotal != $subtotalInsumos && $xlsxTotal > 0) {
            $xlsx->addRow(['', '', '', '', '', '', 'TOTAL:', $xlsxTotal], 'total');
        }

        // Financeiros do fornecedor aprovado
        if ($approvedSupplier && $approvedSupplier['subtotal_items'] > 0) {
            $xlsx->addEmptyRow();
            $finRows = [];
            if ($approvedSupplier['discount_value'] > 0) $finRows[] = 'Desconto: ' . $approvedSupplier['discount_value'] . ($approvedSupplier['discount_type'] === 'percent' ? '%' : ' R$');
            if ($approvedSupplier['surcharge_value'] > 0) $finRows[] = 'Acréscimo: ' . $approvedSupplier['surcharge_value'] . ($approvedSupplier['surcharge_type'] === 'percent' ? '%' : ' R$');
            if ($approvedSupplier['ipi_percent'] > 0) $finRows[] = 'IPI: ' . $approvedSupplier['ipi_percent'] . '%';
            if ($approvedSupplier['icms_percent'] > 0) $finRows[] = 'ICMS: ' . $approvedSupplier['icms_percent'] . '%';
            if ($approvedSupplier['freight'] > 0) $finRows[] = 'Frete: R$ ' . number_format($approvedSupplier['freight'], 2, ',', '.');
            if (!empty($finRows)) {
                $xlsx->addRow(['Detalhamento: ' . implode(' | ', $finRows)], 'bold');
            }
        }

        // Comparação de fornecedores (se houver mais de 1)
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

        // Observações
        if (!empty($order['description'])) {
            $xlsx->addEmptyRow();
            $xlsx->addRow(['Observações: ' . $order['description']]);
        }
        if (!empty($order['approval_notes'])) {
            $xlsx->addRow(['Notas da aprovação: ' . $order['approval_notes']]);
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

        // Uma linha por item
        $statusText = $this->statusLabel($order['status']);
        $approvalDate = $order['approved_at'] ? date('d/m/Y', strtotime($order['approved_at'])) : '';
        $supplierFinal = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? '');

        foreach ($items as $item) {
            $materialLabel2 = $item['material_name'] . ($item['classification'] ? ' - ' . $item['classification'] : '');
            if (!empty($item['already_purchased'])) {
                $materialLabel2 .= ' [JÁ COMPRADO]';
            }
            $xlsx->addRow([
                $order['code'],
                date('d/m/Y', strtotime($order['created_at'])),
                $materialLabel2,
                $supplierFinal,
                $item['unit_price'] ?? 0,
                $item['total_price'] ?? 0,
                $order['created_by_name'] ?? '',
                $statusText,
                $approvalDate,
                $order['description'] ?? '',
            ]);
        }

        $xlsx->download('Pedido_' . $order['code'] . '.xlsx');
    }

    private function statusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Rascunho',
            'pending_quote' => 'Aguard. Cotação',
            'quoted' => 'Cotado',
            'pending_approval' => 'Aguard. Aprovação',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'cancelled' => 'Cancelado',
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Validar CPF/CNPJ de documento de pagamento usando IA
     */
    public function validatePaymentCnpj(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $approvedCnpj = trim($this->input('approved_cnpj', ''));
        
        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Nenhum arquivo enviado.'], 400);
            return;
        }

        $file = $_FILES['file'];
        $openaiKey = Setting::get('openai_api_key', '');
        
        if (empty($openaiKey)) {
            $this->json(['error' => 'Chave da OpenAI não configurada.'], 500);
            return;
        }

        $model = Setting::get('openai_model', 'gpt-4o');
        $mediaType = $file['type'];

        try {
            // Para PDFs, usar a Responses API
            if ($mediaType === 'application/pdf') {
                $extracted = $this->extractCnpjFromPdf($file['tmp_name'], $file['name'], $openaiKey, $model);
            } else {
                // Para imagens: enviar como base64
                $content = base64_encode(file_get_contents($file['tmp_name']));
                
                $messages = [
                    ['role' => 'system', 'content' => 'Você é um assistente que analisa documentos financeiros (notas fiscais, boletos). Extraia APENAS o CPF ou CNPJ do EMITENTE/FORNECEDOR do documento. Retorne APENAS um JSON com o campo "cnpj" contendo o número formatado (ex: "10.776.149/0001-10") ou null se não encontrar. Sem explicação, sem markdown.'],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'Extraia o CPF/CNPJ do emitente/fornecedor deste documento:'],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$content}"]]
                    ]]
                ];

                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => 200,
                        'temperature' => 0,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $openaiKey,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    $this->json(['error' => 'Erro ao analisar documento.'], 500);
                    return;
                }

                $result = json_decode($response, true);
                $text = $result['choices'][0]['message']['content'] ?? '';
                $text = preg_replace('/```json\s*/', '', $text);
                $text = preg_replace('/```\s*/', '', $text);
                $text = trim($text);

                $parsed = json_decode($text, true);
                $extracted = $parsed['cnpj'] ?? null;
            }

            if ($extracted) {
                $this->json(['extracted_cnpj' => $extracted]);
            } else {
                $this->json(['extracted_cnpj' => null, 'error' => 'Não foi possível extrair CPF/CNPJ do documento.']);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Extrair CNPJ de PDF usando OpenAI
     */
    private function extractCnpjFromPdf(string $filePath, string $fileName, string $apiKey, string $model): ?string
    {
        // Upload do arquivo para OpenAI
        $ch = curl_init('https://api.openai.com/v1/files');
        $cfile = new \CURLFile($filePath, 'application/pdf', $fileName);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cfile, 'purpose' => 'assistants'],
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return null;
        
        $fileData = json_decode($response, true);
        $fileId = $fileData['id'] ?? null;
        if (!$fileId) return null;

        // Usar o arquivo no chat
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente que analisa documentos financeiros. Extraia APENAS o CPF ou CNPJ do EMITENTE/FORNECEDOR. Retorne APENAS um JSON: {"cnpj": "XX.XXX.XXX/XXXX-XX"} ou {"cnpj": null}.'],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'Extraia o CPF/CNPJ do emitente/fornecedor deste documento:'],
                        ['type' => 'file', 'file' => ['file_id' => $fileId]]
                    ]]
                ],
                'max_tokens' => 200,
                'temperature' => 0,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Deletar arquivo da OpenAI
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);

        if ($httpCode !== 200) return null;

        $result = json_decode($response, true);
        $text = $result['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);
        return $parsed['cnpj'] ?? null;
    }

    /**
     * Upload de NF ou Boleto
     */
    public function uploadPayment(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $orderId = (int) $this->input('order_id', 0);
        $type = $this->input('type', '');
        
        if (!in_array($type, ['nf', 'boleto', 'pedido'])) {
            $this->json(['error' => 'Tipo inválido.'], 400);
            return;
        }

        $filePath = null;
        $fileName = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $fileName = $_FILES['file']['name'];
            $newName = "payment_{$orderId}_{$type}_" . time() . '.' . $ext;
            $uploadDir = ROOT_PATH . '/public/uploads/payments/';
            
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $destination = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                $filePath = '/uploads/payments/' . $newName;
            }
        }

        $id = PurchaseOrderPayment::create([
            'order_id' => $orderId,
            'type' => $type,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'number' => trim($this->input('number', '')),
            'amount' => !empty($this->input('amount')) ? (float) str_replace(['.', ','], ['', '.'], $this->input('amount')) : null,
            'due_date' => $this->input('due_date') ?: null,
            'notes' => trim($this->input('notes', '')),
            'uploaded_by' => Auth::user()['name'] ?? 'Sistema',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        PurchaseOrderHistory::log($orderId, 'payment_uploaded', ucfirst($type) . ' registrado' . ($fileName ? " ({$fileName})" : ''), Auth::user()['name'] ?? 'Sistema', Auth::id());

        // Enviar notificações da Fase 4 (Pagamento/NF)
        $this->sendPaymentNotifications($orderId, $type, [
            'number' => trim($this->input('number', '')),
            'amount' => !empty($this->input('amount')) ? (float) str_replace(['.', ','], ['', '.'], $this->input('amount')) : null,
            'due_date' => $this->input('due_date') ?: null,
        ]);

        $this->setFlash('success', ucfirst($type) . ' registrado com sucesso!');
        $this->redirect('/admin/orders/show/' . $orderId);
    }

    /**
     * Marcar pagamento como pago
     */
    public function markPaid(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $payment = PurchaseOrderPayment::find($id);
        
        if (!$payment) {
            $this->setFlash('error', 'Registro não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        PurchaseOrderPayment::updateById($id, [
            'paid' => 1,
            'paid_at' => date('Y-m-d'),
        ]);

        PurchaseOrderHistory::log($payment['order_id'], 'payment_paid', ucfirst($payment['type']) . ' marcado como pago', Auth::user()['name'] ?? 'Sistema', Auth::id());

        $this->setFlash('success', 'Pagamento confirmado!');
        $this->redirect('/admin/orders/show/' . $payment['order_id']);
    }

    /**
     * Deletar pagamento (super_admin)
     */
    public function deletePayment(): void
    {
        if (!$this->isPost() || !Auth::isSuperAdmin()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $payment = PurchaseOrderPayment::find($id);
        
        if ($payment) {
            if ($payment['file_path'] && file_exists(ROOT_PATH . '/public' . $payment['file_path'])) {
                unlink(ROOT_PATH . '/public' . $payment['file_path']);
            }
            PurchaseOrderPayment::deleteById($id);
            $this->setFlash('success', 'Registro removido.');
            $this->redirect('/admin/orders/show/' . $payment['order_id']);
        } else {
            $this->redirect('/admin/orders');
        }
    }

    /**
     * Tela de pendências de NF/Boleto
     */
    public function payments(): void
    {
        $pending = PurchaseOrderPayment::getPending();
        $allNf = PurchaseOrderPayment::getByType('nf');
        $allBoleto = PurchaseOrderPayment::getByType('boleto');

        $this->view('admin.orders.payments', [
            'pending' => $pending,
            'allNf' => $allNf,
            'allBoleto' => $allBoleto,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Histórico de preços por material/fornecedor
     */
    public function priceHistory(): void
    {
        $materialId = (int) $this->input('material_id', 0);
        $supplierId = (int) $this->input('supplier_id', 0);
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 25;

        if ($materialId) {
            $records = MaterialPriceHistory::getByMaterial($materialId);
        } elseif ($supplierId) {
            $records = MaterialPriceHistory::getBySupplier($supplierId);
        } else {
            $records = MaterialPriceHistory::getAllGroupedByMaterial(10000);
        }

        $totalRecords = count($records);
        $totalPages = max(1, ceil($totalRecords / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $paginatedRecords = array_slice($records, $offset, $perPage);

        $materials = Material::allActive();
        $suppliers = Supplier::allActive();

        $this->view('admin.orders.price_history', [
            'records' => $paginatedRecords,
            'materials' => $materials,
            'suppliers' => $suppliers,
            'filterMaterial' => $materialId,
            'filterSupplier' => $supplierId,
            'paginaAtual' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $perPage,
            'offset' => $offset,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    // ===============================
    // MÉTODOS PRIVADOS
    // ===============================

    private function sendQuoteNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $quoteUrl = "{$baseUrl}/pedido/cotacao/{$token}";

        // Resolver modo de notificação (both/site_only/global_only)
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;
        $quoteMode = Setting::get('orders_quote_notify_mode', 'both');
        $siteQuoteUsers = [];
        if (!empty($constructionSiteId)) {
            $siteQuoteUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'quote');
        }
        $sendGlobal = in_array($quoteMode, ['both', 'global_only']);
        $sendSite = in_array($quoteMode, ['both', 'site_only']) && !empty($siteQuoteUsers);
        if ($quoteMode === 'site_only' && empty($siteQuoteUsers)) {
            $sendGlobal = true;
        }

        // Informação da obra
        $obraInfo = '';
        if (!empty($order['construction_site_name'])) {
            $obraInfo = "*Obra:* {$order['construction_site_code']} - {$order['construction_site_name']}";
            if (!empty($order['construction_site_address'])) {
                $obraInfo .= " ({$order['construction_site_address']}";
                if (!empty($order['construction_site_city'])) $obraInfo .= " - {$order['construction_site_city']}/{$order['construction_site_state']}";
                $obraInfo .= ")";
            }
            $obraInfo .= "\n";
        }

        // E-mail (via fila)
        $quoteItems = array_filter($items, function($item) {
            return empty($item['source_type']) || $item['source_type'] === 'purchase';
        });
        $subject = "Cotação Pendente - Pedido {$order['code']}";
        if (!empty($order['construction_site_name'])) {
            $subject .= " - Obra: {$order['construction_site_name']}";
        }
        $body = EmailTemplate::purchaseOrderQuote($order, array_values($quoteItems), $quoteUrl, $orderSuppliers);

        // E-mails globais
        if ($sendGlobal) {
            $emails = Setting::get('orders_quote_emails', '');
            if (!empty($emails)) {
                NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'quote_requested');
            }
        }

        // E-mails dos responsáveis da obra
        if ($sendSite) {
            $siteEmails = implode(',', array_filter(array_column($siteQuoteUsers, 'email')));
            if (!empty($siteEmails)) {
                NotificationService::queueEmails($siteEmails, $subject, $body, $order['id'], 'quote_requested');
            }
        }

        // Webhook
        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty($webhookUrl)) {
            $itemsList = '';
            $itemNum = 0;
            foreach ($items as $item) {
                // Só incluir itens que precisam de cotação
                if (!empty($item['source_type']) && $item['source_type'] !== 'purchase') continue;
                $itemNum++;
                $itemsList .= $itemNum . ". {$item['material_name']}";
                if ($item['classification']) $itemsList .= " ({$item['classification']})";
                $qty = (float) $item['quantity'];
                $qtyFmt = $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2, ',', '.');
                $itemsList .= " - Qtd: {$qtyFmt} {$item['unit']}\n";
            }

            $suppliersList = '';
            $suppliersArray = [];
            if (!empty($orderSuppliers)) {
                $supplierNames = array_map(fn($s) => $s['supplier_name'], $orderSuppliers);
                $suppliersList = "*Fornecedores para cotação:*\n" . implode("\n", array_map(fn($n) => "- {$n}", $supplierNames)) . "\n\n";
                $suppliersArray = array_map(fn($s) => ['id' => $s['supplier_id'], 'name' => $s['supplier_name']], $orderSuppliers);
            }

            $message = "*NOVO PEDIDO - COTAÇÃO PENDENTE*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . $obraInfo
                . "*Solicitado por:* {$order['created_by_name']}\n"
                . "*Data:* " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n"
                . "*Itens:* {$itemNum}\n\n"
                . $suppliersList
                . "*Lista de materiais:*\n{$itemsList}\n"
                . (!empty($order['description']) ? "*Obs:* {$order['description']}\n\n" : "\n")
                . "*Link para informar cotação:*\n{$quoteUrl}";

            // Webhook para responsáveis da obra
            if ($sendSite) {
                $sitePhones = implode(',', array_filter(array_column($siteQuoteUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($siteQuoteUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($webhookUrl, [
                        'event' => 'quote_requested',
                        'order_code' => $order['code'],
                        'construction_site' => !empty($order['construction_site_name']) ? [
                            'id' => $order['construction_site_id'],
                            'code' => $order['construction_site_code'],
                            'name' => $order['construction_site_name'],
                        ] : null,
                        'suppliers' => $suppliersArray,
                        'items_count' => $itemNum,
                        'quote_url' => $quoteUrl,
                        'created_by' => $order['created_by_name'],
                        'created_at' => $order['created_at'],
                        'description' => $order['description'],
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ], $order['id'], 'quote_requested');
                }
            }

            // Webhook para globais
            if ($sendGlobal) {
                $this->sendWebhook($webhookUrl, [
                    'event' => 'quote_requested',
                    'order_code' => $order['code'],
                    'construction_site' => !empty($order['construction_site_name']) ? [
                        'id' => $order['construction_site_id'],
                        'code' => $order['construction_site_code'],
                        'name' => $order['construction_site_name'],
                    ] : null,
                    'suppliers' => $suppliersArray,
                    'items_count' => $itemNum,
                    'quote_url' => $quoteUrl,
                    'created_by' => $order['created_by_name'],
                    'created_at' => $order['created_at'],
                    'description' => $order['description'],
                    'phone' => Setting::get('orders_quote_phone', ''),
                    'phone_name' => Setting::get('orders_quote_phone_name', ''),
                    'message' => $message,
                ], $order['id'], 'quote_requested');
            }
        }
    }

    private function sendApprovalNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$token}";

        // Resolver modo de notificação (both/site_only/global_only)
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;
        $notifyMode = Setting::get('orders_approval_notify_mode', 'both');
        $siteApprovers = [];
        if (!empty($constructionSiteId)) {
            $siteApprovers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'approval');
        }
        $sendGlobal = in_array($notifyMode, ['both', 'global_only']);
        $sendSite = in_array($notifyMode, ['both', 'site_only']) && !empty($siteApprovers);
        // Fallback: se modo é site_only mas não tem aprovadores na obra, usa global
        if ($notifyMode === 'site_only' && empty($siteApprovers)) {
            $sendGlobal = true;
        }

        // Informação da obra
        $obraInfo = '';
        if (!empty($order['construction_site_name'])) {
            $obraInfo = "*Obra:* {$order['construction_site_code']} - {$order['construction_site_name']}\n";
        }

        $subject = "Aprovação Pendente - Pedido {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
        if (!empty($order['construction_site_name'])) {
            $subject .= " - Obra: {$order['construction_site_name']}";
        }
        $body = EmailTemplate::purchaseOrderApproval($order, $items, $approvalUrl, $orderSuppliers);

        // E-mails globais
        if ($sendGlobal) {
            $emails = Setting::get('orders_approval_emails', '');
            if (!empty($emails)) {
                NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'approval_requested');
            }
        }

        // E-mails dos aprovadores da obra
        if ($sendSite) {
            $approverEmails = array_filter(array_column($siteApprovers, 'email'));
            if (!empty($approverEmails)) {
                $extraEmails = implode(',', $approverEmails);
                NotificationService::queueEmails($extraEmails, $subject, $body, $order['id'], 'approval_requested');
            }
        }

        // Webhook
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
                . $obraInfo
                . "*Itens:* " . count($items) . "\n"
                . "*Cotado por:* {$order['quoted_by_name']}\n"
                . "*Data cotação:* " . date('d/m/Y H:i', strtotime($order['quoted_at'])) . "\n\n"
                . $supplierComparison
                . "*Link para aprovar/rejeitar:*\n{$approvalUrl}";

            // Webhook para aprovadores da obra
            if ($sendSite) {
                $approverPhones = array_filter(array_column($siteApprovers, 'phone'));
                $approverNames = array_filter(array_column($siteApprovers, 'name'));
                if (!empty($approverPhones)) {
                    $this->sendWebhook($webhookUrl, [
                        'event' => 'approval_requested',
                        'order_code' => $order['code'],
                        'construction_site' => !empty($order['construction_site_name']) ? [
                            'id' => $order['construction_site_id'],
                            'code' => $order['construction_site_code'],
                            'name' => $order['construction_site_name'],
                        ] : null,
                        'suppliers' => $suppliersData,
                        'total' => $order['total_estimated'],
                        'items_count' => count($items),
                        'approval_url' => $approvalUrl,
                        'quoted_by' => $order['quoted_by_name'],
                        'phone' => implode(',', $approverPhones),
                        'phone_name' => implode(',', $approverNames),
                        'message' => $message,
                    ], $order['id']);
                }
            }

            // Webhook para globais
            if ($sendGlobal) {
                $this->sendWebhook($webhookUrl, [
                    'event' => 'approval_requested',
                    'order_code' => $order['code'],
                    'construction_site' => !empty($order['construction_site_name']) ? [
                        'id' => $order['construction_site_id'],
                        'code' => $order['construction_site_code'],
                        'name' => $order['construction_site_name'],
                    ] : null,
                    'suppliers' => $suppliersData,
                    'total' => $order['total_estimated'],
                    'items_count' => count($items),
                    'approval_url' => $approvalUrl,
                    'quoted_by' => $order['quoted_by_name'],
                    'quoted_at' => $order['quoted_at'],
                    'phone' => Setting::get('orders_approval_phone', ''),
                    'phone_name' => Setting::get('orders_approval_phone_name', ''),
                    'message' => $message,
                ], $order['id']);
            }
        }
    }

    private function sendCompletedNotifications(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
        $baseUrl = $this->getBaseUrl();
        $viewUrl = "{$baseUrl}/pedido/pdf/{$orderId}";

        // Resolver modo de notificação (both/site_only/global_only)
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;
        $completedMode = Setting::get('orders_completed_notify_mode', 'both');
        $siteCompletedUsers = [];
        if (!empty($constructionSiteId)) {
            $siteCompletedUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'completed');
        }
        $sendGlobal = in_array($completedMode, ['both', 'global_only']);
        $sendSite = in_array($completedMode, ['both', 'site_only']) && !empty($siteCompletedUsers);
        if ($completedMode === 'site_only' && empty($siteCompletedUsers)) {
            $sendGlobal = true;
        }

        // E-mail
        $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
        $body = EmailTemplate::purchaseOrderCompleted($order, $items, $viewUrl, '', $approvedSuppliers);

        if ($sendGlobal) {
            $emails = Setting::get('orders_completed_emails', '');
            if (!empty($emails)) {
                NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'order_approved');
            }
        }

        if ($sendSite) {
            $siteEmails = implode(',', array_filter(array_column($siteCompletedUsers, 'email')));
            if (!empty($siteEmails)) {
                NotificationService::queueEmails($siteEmails, $subject, $body, $order['id'], 'order_approved');
            }
        }

        // Webhook
        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($order['total_estimated'], 2, ',', '.');
            $approvedNames = !empty($approvedSuppliers) ? array_column($approvedSuppliers, 'supplier_name') : [];
            $approvedSupplierDisplay = !empty($approvedNames) ? implode(', ', $approvedNames) : ($order['supplier_name'] ?? 'N/A');

            // Informação da obra
            $obraInfo = '';
            if (!empty($order['construction_site_name'])) {
                $obraInfo = "*Obra:* {$order['construction_site_code']} - {$order['construction_site_name']}\n";
            }

            $suppliersComparison = '';
            if (!empty($orderSuppliers) && count($orderSuppliers) > 1) {
                $suppliersComparison = "\n*Comparação de fornecedores:*\n";
                foreach ($orderSuppliers as $os) {
                    $mark = $os['approved'] ? ' [APROVADO]' : '';
                    $osFmt = $os['total'] ? 'R$ ' . number_format($os['total'], 2, ',', '.') : '-';
                    $suppliersComparison .= "- {$os['supplier_name']}: {$osFmt}{$mark}\n";
                }
            }

            $message = "*PEDIDO APROVADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . $obraInfo
                . "*Fornecedor(es) aprovado(s):* {$approvedSupplierDisplay}\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Aprovado por:* {$order['approved_by_name']}\n"
                . "*Data:* " . date('d/m/Y H:i', strtotime($order['approved_at'])) . "\n"
                . $suppliersComparison . "\n"
                . "*PDF do pedido:*\n{$viewUrl}";

            // Webhook para responsáveis da obra
            if ($sendSite) {
                $sitePhones = implode(',', array_filter(array_column($siteCompletedUsers, 'phone')));
                $siteNames = implode(',', array_filter(array_column($siteCompletedUsers, 'name')));
                if (!empty($sitePhones)) {
                    $this->sendWebhook($webhookUrl, [
                        'event' => 'order_approved',
                        'order_code' => $order['code'],
                        'construction_site' => !empty($order['construction_site_name']) ? [
                            'id' => $order['construction_site_id'],
                            'code' => $order['construction_site_code'],
                            'name' => $order['construction_site_name'],
                        ] : null,
                        'approved_suppliers' => $approvedNames,
                        'suppliers' => array_map(fn($s) => ['name' => $s['supplier_name'], 'total' => $s['total'], 'approved' => (bool)$s['approved']], $orderSuppliers),
                        'total' => $order['total_estimated'],
                        'approved_by' => $order['approved_by_name'],
                        'approved_at' => $order['approved_at'],
                        'pdf_url' => $viewUrl,
                        'phone' => $sitePhones,
                        'phone_name' => $siteNames,
                        'message' => $message,
                    ]);
                }
            }

            // Webhook para globais
            if ($sendGlobal) {
                $this->sendWebhook($webhookUrl, [
                    'event' => 'order_approved',
                    'order_code' => $order['code'],
                    'construction_site' => !empty($order['construction_site_name']) ? [
                        'id' => $order['construction_site_id'],
                        'code' => $order['construction_site_code'],
                        'name' => $order['construction_site_name'],
                    ] : null,
                    'approved_suppliers' => $approvedNames,
                    'suppliers' => array_map(fn($s) => ['name' => $s['supplier_name'], 'total' => $s['total'], 'approved' => (bool)$s['approved']], $orderSuppliers),
                    'total' => $order['total_estimated'],
                    'approved_by' => $order['approved_by_name'],
                    'approved_at' => $order['approved_at'],
                    'pdf_url' => $viewUrl,
                    'phone' => Setting::get('orders_completed_phone', ''),
                    'phone_name' => Setting::get('orders_completed_phone_name', ''),
                    'message' => $message,
                ]);
            }
        }
    }

    private function sendWebhook(string $url, array $data, ?int $orderId = null, ?string $eventType = null): void
    {
        if (!$eventType && isset($data['event'])) {
            $eventType = $data['event'];
        }
        if (!$orderId && isset($data['_order_id'])) {
            $orderId = (int) $data['_order_id'];
            unset($data['_order_id']);
        }

        error_log("[BROOKS_WEBHOOK] sendWebhook: url={$url} event={$eventType} orderId={$orderId} phone=" . ($data['phone'] ?? 'N/A'));

        NotificationService::queueWebhook($url, $data, $orderId, $eventType);
    }

    /**
     * Notificar responsável pelo transporte sobre movimentação de estoque
     */
    private function notifyTransportMovement(int $movementId): void
    {
        $movement = \App\Models\StockMovement::find($movementId);
        if (!$movement) return;

        $material = Material::find($movement['material_id']);
        
        // Buscar nomes: primeiro tenta location, depois site
        $fromName = 'N/A';
        $toName = 'N/A';
        if ($movement['from_location_id']) {
            $fromLoc = \App\Models\StockLocation::findFull($movement['from_location_id']);
            $fromName = $fromLoc['name'] ?? 'N/A';
        } elseif ($movement['from_site_id']) {
            $fromSite = \App\Models\ConstructionSite::find($movement['from_site_id']);
            $fromName = $fromSite['name'] ?? 'N/A';
        }
        if ($movement['to_location_id']) {
            $toLoc = \App\Models\StockLocation::findFull($movement['to_location_id']);
            $toName = $toLoc['name'] ?? 'N/A';
        } elseif ($movement['to_site_id']) {
            $toSite = \App\Models\ConstructionSite::find($movement['to_site_id']);
            $toName = $toSite['name'] ?? 'N/A';
        }

        $materialName = $material['name'] ?? 'Material';
        $qty = (float) $movement['quantity'];
        $qtyFmt = $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2, ',', '.');
        $type = $movement['type'] === 'transfer' ? 'TRANSFERÊNCIA' : 'SAÍDA DE ESTOQUE';
        $hasDestination = ($movement['to_location_id'] || $movement['to_site_id']);

        $emails = Setting::get('orders_transport_emails', '');
        if (!empty($emails)) {
            $subject = "{$type} - {$materialName}";
            $body = "<h2>{$type} de Material</h2>"
                . "<p><strong>Material:</strong> {$materialName}</p>"
                . "<p><strong>Quantidade:</strong> {$qtyFmt}</p>"
                . "<p><strong>Origem:</strong> {$fromName}</p>"
                . ($hasDestination ? "<p><strong>Destino:</strong> {$toName}</p>" : '')
                . "<p><strong>Solicitado por:</strong> {$movement['requested_by']}</p>"
                . "<p><strong>Data:</strong> " . date('d/m/Y H:i') . "</p>";
            NotificationService::queueEmails($emails, $subject, $body, $movement['order_id'], 'stock_transport');
        }

        $webhookUrl = Setting::get('orders_transport_webhook', '');
        if (!empty(trim($webhookUrl))) {
            $message = "*{$type}*\n\n"
                . "*Material:* {$materialName}\n"
                . "*Quantidade:* {$qtyFmt}\n"
                . "*Origem:* {$fromName}\n"
                . ($hasDestination ? "*Destino:* {$toName}\n" : '')
                . "*Solicitado por:* {$movement['requested_by']}\n"
                . "*Data:* " . date('d/m/Y H:i');

            NotificationService::queueWebhook($webhookUrl, [
                'event' => 'stock_movement',
                'type' => $movement['type'],
                'material' => $materialName,
                'quantity' => $qtyFmt,
                'from_location' => $fromName,
                'to_location' => $toName,
                'requested_by' => $movement['requested_by'],
                'message' => $message,
                'phone' => Setting::get('orders_transport_phone', ''),
                'phone_name' => Setting::get('orders_transport_phone_name', ''),
            ], $movement['order_id'], 'stock_transport');
        }
    }

    private function sendPaymentNotifications(int $orderId, string $type, array $docData): void
    {
        $order = PurchaseOrder::findFull($orderId);
        if (!$order) return;

        // Total real (com financeiros)
        $approvedSup = PurchaseOrderSupplier::getApproved($orderId);
        $realTotal = ($approvedSup && $approvedSup['subtotal_final'] > 0) ? (float)$approvedSup['subtotal_final'] : (float)($order['total_estimated'] ?? 0);

        $baseUrl = $this->getBaseUrl();
        $panelUrl = "{$baseUrl}/pedidos";
        $orderUrl = "{$baseUrl}/admin/orders/show/{$orderId}";
        $typeLabel = strtoupper($type);
        $uploadedBy = Auth::user()['name'] ?? 'Sistema';
        $amount = $docData['amount'] ?? 0;
        $amountFmt = $amount ? 'R$ ' . number_format((float)$amount, 2, ',', '.') : 'N/A';
        $dueDateFmt = !empty($docData['due_date']) ? date('d/m/Y', strtotime($docData['due_date'])) : 'N/A';

        // E-mail
        $emails = Setting::get('orders_payment_emails', '');
        if (!empty($emails)) {
            $subject = "{$typeLabel} Enviado - Pedido {$order['code']}";
            $body = EmailTemplate::purchaseOrderPayment($order, $typeLabel, $docData, $uploadedBy, $panelUrl);
            NotificationService::queueEmails($emails, $subject, $body, (int)$order['id'], 'payment_uploaded');
        }

        // Webhook
        $webhookUrl = Setting::get('orders_payment_webhook', '');
        error_log("[BROOKS_WEBHOOK] sendPaymentNotifications: webhook_url='{$webhookUrl}' phone='" . Setting::get('orders_payment_phone', '') . "' order={$orderId}");
        
        if (!empty(trim($webhookUrl))) {
            $message = "*{$typeLabel} RECEBIDO ✓*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                . "*Tipo:* {$typeLabel}\n"
                . (!empty($docData['number']) ? "*Número:* {$docData['number']}\n" : '')
                . "*Valor:* {$amountFmt}\n"
                . (!empty($docData['due_date']) ? "*Vencimento:* {$dueDateFmt}\n" : '')
                . "*Enviado por:* {$uploadedBy}\n"
                . "*Data:* " . date('d/m/Y H:i') . "\n\n"
                . "*Acesse o painel para conferir:*\n{$panelUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'payment_uploaded',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $realTotal,
                'document_type' => $typeLabel,
                'document_number' => $docData['number'] ?? '',
                'amount' => $amount,
                'due_date' => $docData['due_date'] ?? '',
                'uploaded_by' => $uploadedBy,
                'panel_url' => $panelUrl,
                'phone' => Setting::get('orders_payment_phone', ''),
                'phone_name' => Setting::get('orders_payment_phone_name', ''),
                'message' => $message,
            ], (int)$order['id']);
        }
    }

    private function sendDeliveryNotifications(int $orderId, string $deliveryToken): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
        $baseUrl = $this->getBaseUrl();
        $checklistUrl = "{$baseUrl}/pedido/entrega/{$deliveryToken}";

        $supplierNames = !empty($approvedSuppliers) ? array_column($approvedSuppliers, 'supplier_name') : [];
        $supplierDisplay = !empty($supplierNames) ? implode(', ', $supplierNames) : ($order['supplier_name'] ?? 'N/A');

        // E-mail
        $emails = Setting::get('orders_delivery_emails', '');
        if (!empty($emails)) {
            $subject = "Checklist de Entrega - Pedido {$order['code']}";
            $body = EmailTemplate::purchaseOrderDelivery($order, $items, $checklistUrl, $supplierDisplay);
            NotificationService::queueEmails($emails, $subject, $body, $order['id'], 'delivery_ready');
        }

        // Webhook
        $webhookUrl = Setting::get('orders_delivery_webhook', '');
        if (!empty($webhookUrl)) {
            $message = "*CHECKLIST DE ENTREGA DISPONÍVEL*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Fornecedor(es):* {$supplierDisplay}\n"
                . "*Itens:* " . count($items) . "\n"
                . "*Valor:* R$ " . number_format($order['total_estimated'], 2, ',', '.') . "\n\n"
                . "*Acesse o checklist para conferir as entregas:*\n{$checklistUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'delivery_checklist_ready',
                'order_code' => $order['code'],
                'suppliers' => $supplierNames,
                'items_count' => count($items),
                'checklist_url' => $checklistUrl,
                'phone' => Setting::get('orders_delivery_phone', ''),
                'phone_name' => Setting::get('orders_delivery_phone_name', ''),
                'message' => $message,
            ]);

            // Notificar o solicitante do pedido (se configuração ativa e tiver telefone)
            if (Setting::get('orders_notify_requester_delivery', '0') === '1' && !empty($order['created_by'])) {
                $requester = PinUser::find((int) $order['created_by']);
                if ($requester && $requester['active']) {
                    if (!empty($requester['phone'])) {
                        $this->sendWebhook($webhookUrl, [
                            'event' => 'delivery_checklist_ready',
                            'order_code' => $order['code'],
                            'suppliers' => $supplierNames,
                            'items_count' => count($items),
                            'checklist_url' => $checklistUrl,
                            'phone' => $requester['phone'],
                            'phone_name' => $requester['name'],
                            'message' => $message,
                        ]);
                    }
                    if (!empty($requester['email'])) {
                        $subject = "Checklist de Entrega - Pedido {$order['code']}";
                        $body = EmailTemplate::purchaseOrderDelivery($order, $items, $checklistUrl, $supplierDisplay);
                        NotificationService::queueEmails($requester['email'], $subject, $body, $order['id'], 'delivery_ready');
                    }
                }
            }
        }
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        return $scheme . '://' . $host;
    }

    /**
     * Buscar valor unitário de um material no estoque
     */
    private function getStockUnitPrice(?int $materialId, ?int $locationId = null, ?int $siteId = null): ?float
    {
        if (!$materialId) return null;

        if ($locationId) {
            $stockItem = \App\Models\StockItem::findByMaterialAndLocation($materialId, $locationId);
        } elseif ($siteId) {
            $stockItem = \App\Models\StockItem::findByMaterialAndSite($materialId, $siteId);
        } else {
            return null;
        }

        return $stockItem && !empty($stockItem['unit_price']) ? (float) $stockItem['unit_price'] : null;
    }

    /**
     * Executar movimentações de estoque pendentes ao aprovar o pedido
     * Debita do estoque de origem imediatamente
     */
    private function executeStockMovementsOnApproval(int $orderId): void
    {
        $items = PurchaseOrderItem::getByOrder($orderId);

        foreach ($items as $item) {
            if (empty($item['stock_movement_id'])) continue;

            $movement = \App\Models\StockMovement::find((int) $item['stock_movement_id']);
            if (!$movement || $movement['status'] !== \App\Models\StockMovement::STATUS_PENDING) continue;

            // Debitar do estoque de origem (material saiu para uso na obra)
            if ($movement['from_location_id']) {
                $stockItem = \App\Models\StockItem::findByMaterialAndLocation($movement['material_id'], $movement['from_location_id']);
                if ($stockItem) {
                    \App\Models\StockItem::debit($stockItem['id'], $movement['quantity']);
                }
            } elseif ($movement['from_site_id']) {
                $stockItem = \App\Models\StockItem::findByMaterialAndSite($movement['material_id'], $movement['from_site_id']);
                if ($stockItem) {
                    \App\Models\StockItem::debit($stockItem['id'], $movement['quantity']);
                }
            }

            // NÃO creditar no destino — pedido aprovado = material consumido/usado na obra

            // Marcar movimentação como executada
            \App\Models\StockMovement::updateById((int) $item['stock_movement_id'], [
                'status' => \App\Models\StockMovement::STATUS_DELIVERED,
                'delivered_by' => 'Sistema (aprovação)',
                'delivered_at' => date('Y-m-d H:i:s'),
                'notes' => ($movement['notes'] ?? '') ? $movement['notes'] . ' | Baixa automática na aprovação do pedido.' : 'Baixa automática na aprovação do pedido.',
            ]);
        }
    }

    /**
     * Inicializar checklist de entrega ao aprovar automaticamente
     */
    private function initDeliveryOnApproval(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        if (!$order) return;

        // Inicializar checklist
        \App\Models\PurchaseOrderDelivery::initializeForOrder($orderId);

        // Gerar delivery token se não tem
        $deliveryToken = $order['delivery_token'] ?? '';
        if (empty($deliveryToken)) {
            $deliveryToken = bin2hex(random_bytes(32));
            PurchaseOrder::updateById($orderId, ['delivery_token' => $deliveryToken]);
        }

        PurchaseOrderHistory::log($orderId, 'delivery_init', 'Checklist de entrega criado automaticamente na aprovação', $order['approved_by_name'] ?? 'Sistema');
    }

    // ============================
    // CHECKLIST DE ENTREGA
    // ============================

    /**
     * Inicializa o checklist de entrega para um pedido aprovado
     */
    public function deliveryInit(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders'); return; }

        $orderId = (int) $this->input('order_id');
        $order = PurchaseOrder::findFull($orderId);

        if (!$order || $order['status'] !== 'approved') {
            $this->setFlash('error', 'Pedido não encontrado ou não está aprovado.');
            $this->redirect('/admin/orders');
            return;
        }

        // Se já existe checklist, recria (deleta e refaz para corrigir fornecedores)
        $existing = PurchaseOrderDelivery::getByOrder($orderId);
        if (!empty($existing)) {
            Database::query("DELETE FROM purchase_order_deliveries WHERE order_id = ?", [$orderId]);
        }

        PurchaseOrderDelivery::initializeForOrder($orderId);

        // Gerar token de acesso público se não tem
        if (empty($order['delivery_token'])) {
            $deliveryToken = bin2hex(random_bytes(32));
            PurchaseOrder::updateById($orderId, ['delivery_token' => $deliveryToken]);
        } else {
            $deliveryToken = $order['delivery_token'];
        }

        // Enviar notificação apenas na primeira vez
        if (empty($existing)) {
            $this->sendDeliveryNotifications($orderId, $deliveryToken);
        }

        PurchaseOrderHistory::log($orderId, 'delivery_init', 'Checklist de entrega ' . (empty($existing) ? 'criado' : 'recriado'), Auth::user()['name'] ?? 'Sistema', Auth::id());
        
        $this->setFlash('success', 'Checklist de entrega ' . (empty($existing) ? 'criado' : 'recriado') . ' com sucesso!');
        $this->redirect('/admin/orders/show/' . $orderId);
    }

    /**
     * Atualiza o status de entrega de um item
     */
    public function deliveryUpdate(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $id = (int) $this->input('id');
        $delivery = PurchaseOrderDelivery::find($id);

        if (!$delivery) {
            $this->json(['error' => 'Registro não encontrado.'], 404);
            return;
        }

        $action = $this->input('delivery_action', '');
        $performedBy = trim($this->input('performed_by', Auth::user()['name'] ?? 'Sistema'));
        $now = date('Y-m-d H:i:s');
        $description = '';

        switch ($action) {
            case 'mark_delivered':
                $receivedQty = $this->input('received_quantity', '');
                $updateData = ['status' => 'delivered', 'delivered_at' => $now];
                if ($receivedQty !== '') $updateData['received_quantity'] = (float) $receivedQty;
                PurchaseOrderDelivery::updateById($id, $updateData);
                $description = "Marcado como entregue por {$performedBy}" . ($receivedQty ? " (qty: {$receivedQty})" : '');
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
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'checked',
                    'checked_by' => $performedBy,
                ]);
                $description = "Conferido OK por {$performedBy}";
                break;

            case 'mark_divergence':
                $notes = trim($this->input('divergence_notes', ''));
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'divergence',
                    'divergence_notes' => $notes,
                ]);
                $description = "Divergência registrada por {$performedBy}: {$notes}";
                break;

            case 'request_replacement':
                $expectedDate = $this->input('replacement_expected_date', '');
                $notes = trim($this->input('replacement_notes', ''));
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'replacement_requested',
                    'replacement_requested_at' => $now,
                    'replacement_expected_date' => $expectedDate ?: null,
                    'replacement_notes' => $notes,
                ]);
                $description = "Troca solicitada por {$performedBy}" . ($expectedDate ? " - previsão: {$expectedDate}" : '');
                break;

            case 'mark_replacement_delivered':
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'replacement_delivered',
                    'replacement_delivered_at' => $now,
                ]);
                $description = "Troca entregue - conferido por {$performedBy}";
                break;

            case 'reset':
                PurchaseOrderDelivery::updateById($id, [
                    'status' => 'pending',
                    'delivered_at' => null,
                    'checked_by' => null,
                    'divergence_notes' => null,
                ]);
                $description = "Resetado para pendente por {$performedBy}";
                break;

            case 'add_notes':
                $notes = trim($this->input('notes', ''));
                PurchaseOrderDelivery::updateById($id, ['notes' => $notes]);
                $description = "Observação adicionada por {$performedBy}: {$notes}";
                break;

            default:
                $this->json(['error' => 'Ação inválida.'], 400);
                return;
        }

        // Registrar no histórico
        if ($description) {
            Database::insert('purchase_order_delivery_history', [
                'delivery_id' => $id,
                'order_id' => $delivery['order_id'],
                'action' => $action,
                'description' => $description,
                'performed_by' => $performedBy,
                'created_at' => $now,
            ]);
        }

        $this->json(['success' => true, 'timestamp' => $now]);
    }

    // ============================
    // ITENS SOBRESSALENTES
    // ============================

    /**
     * Tela de itens sobressalentes (visão semanal)
     */
    public function spareItems(): void
    {
        $weeklyBudget = (float) Setting::get('spare_items_weekly_budget', 1000);
        $thisWeekTotal = PurchaseOrderSpareItem::totalThisWeek();
        $thisWeekItems = PurchaseOrderSpareItem::getThisWeek();
        $allItems = PurchaseOrderSpareItem::getAllGroupedByWeek(200);
        $orders = Database::fetchAll("SELECT id, code FROM purchase_orders WHERE status = 'approved' ORDER BY code DESC");

        $this->view('admin.orders.spare_items', [
            'weeklyBudget' => $weeklyBudget,
            'thisWeekTotal' => $thisWeekTotal,
            'thisWeekItems' => $thisWeekItems,
            'allItems' => $allItems,
            'orders' => $orders,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Adicionar item sobressalente
     */
    public function spareItemAdd(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/spare-items'); return; }

        $orderId = (int) $this->input('order_id');
        $description = trim($this->input('description', ''));
        $quantity = (float) str_replace(',', '.', $this->input('quantity', '1'));
        $unit = trim($this->input('unit', ''));
        $unitPrice = (float) str_replace(',', '.', str_replace('.', '', $this->input('unit_price', '0')));
        $supplierName = trim($this->input('supplier_name', ''));
        $paymentMethod = $this->input('payment_method', '') ?: null;
        $purchasedBy = trim($this->input('purchased_by', ''));
        $purchasedAt = $this->input('purchased_at', date('Y-m-d'));
        $notes = trim($this->input('notes', ''));
        $justification = trim($this->input('justification', ''));

        if (empty($description) || $orderId <= 0) {
            $this->setFlash('error', 'Preencha a descrição e selecione o pedido.');
            $redirect = $this->input('redirect', '/admin/orders/spare-items');
            $this->redirect($redirect);
            return;
        }

        if (empty($justification)) {
            $this->setFlash('error', 'A justificativa é obrigatória para itens sobressalentes.');
            $redirect = $this->input('redirect', '/admin/orders/spare-items');
            $this->redirect($redirect);
            return;
        }

        $totalPrice = $quantity * $unitPrice;

        // Upload do comprovante
        $receiptPath = null;
        $receiptName = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $receiptName = $_FILES['receipt']['name'];
            $newName = "spare_{$orderId}_" . time() . '.' . $ext;
            $uploadDir = ROOT_PATH . '/public/uploads/spare-items/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destination = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $destination)) {
                $receiptPath = '/uploads/spare-items/' . $newName;
            }
        }

        $itemId = PurchaseOrderSpareItem::create([
            'order_id' => $orderId,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'supplier_name' => $supplierName,
            'payment_method' => $paymentMethod,
            'purchased_by' => $purchasedBy ?: (Auth::user()['name'] ?? 'Sistema'),
            'purchased_at' => $purchasedAt,
            'notes' => $notes,
            'justification' => $justification,
            'receipt_path' => $receiptPath,
            'receipt_name' => $receiptName,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Registrar no histórico do pedido
        $order = PurchaseOrder::find($orderId);
        if ($order) {
            PurchaseOrderHistory::log($orderId, 'spare_item_added',
                "Item sobressalente adicionado: {$description} (R$ " . number_format($totalPrice, 2, ',', '.') . ")",
                $purchasedBy ?: (Auth::user()['name'] ?? 'Sistema'), Auth::id());
        }

        // Enviar notificação
        $this->sendSpareItemNotification($orderId, $description, $totalPrice, $purchasedBy ?: (Auth::user()['name'] ?? 'Sistema'));

        $this->setFlash('success', "Item \"{$description}\" adicionado com sucesso!");
        $redirect = $this->input('redirect', '/admin/orders/spare-items');
        $this->redirect($redirect);
    }

    /**
     * Excluir item sobressalente
     */
    public function spareItemDelete(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/spare-items'); return; }

        $id = (int) $this->input('id');
        $item = PurchaseOrderSpareItem::find($id);

        if ($item) {
            PurchaseOrderSpareItem::deleteById($id);
            PurchaseOrderHistory::log($item['order_id'], 'spare_item_removed',
                "Item sobressalente removido: {$item['description']} (R$ " . number_format($item['total_price'], 2, ',', '.') . ")",
                Auth::user()['name'] ?? 'Sistema', Auth::id());
            $this->setFlash('success', 'Item removido.');
        }

        $redirect = $this->input('redirect', '/admin/orders/spare-items');
        $this->redirect($redirect);
    }

    /**
     * Gerar link de convite para cadastro de PIN user
     */
    public function generateInvite(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }

        $role = $this->input('role', 'all');
        $validRoles = ['buyer', 'quoter', 'approver', 'payment', 'delivery', 'epi', 'stock', 'all'];
        if (!in_array($role, $validRoles)) $role = 'all';

        $token = bin2hex(random_bytes(32));

        Database::insert('pin_invite_links', [
            'token' => $token,
            'role' => $role,
            'description' => trim($this->input('description', '')),
            'max_uses' => $this->input('max_uses', '') ?: null,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Convite criado com sucesso!');
        $this->redirect('/admin/orders/pin-users');
    }

    /**
     * Tela de gerenciamento de usuários PIN e convites
     */
    public function pinUsers(): void
    {
        $users = Database::fetchAll("SELECT * FROM pin_users ORDER BY created_at DESC");
        $invites = Database::fetchAll("SELECT * FROM pin_invite_links ORDER BY created_at DESC");
        $baseUrl = $this->getBaseUrl();

        $this->view('admin.orders.pin_users', [
            'users' => $users,
            'invites' => $invites,
            'baseUrl' => $baseUrl,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Excluir convite
     */
    public function deleteInvite(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }
        $id = (int) $this->input('id');
        Database::delete('pin_invite_links', 'id = ?', [$id]);
        $this->setFlash('success', 'Convite removido.');
        $this->redirect('/admin/orders/pin-users');
    }

    /**
     * Excluir/desativar usuário PIN
     */
    public function deletePinUser(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }
        $id = (int) $this->input('id');
        Database::update('pin_users', ['active' => 0, 'session_token' => null], 'id = ?', [$id]);
        $this->setFlash('success', 'Usuário desativado.');
        $this->redirect('/admin/orders/pin-users');
    }

    /**
     * Enviar link de convite via webhook para os números da fase correspondente
     */
    public function sendInviteWebhook(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $token = $this->input('token', '');
        $role = $this->input('role', '');
        $baseUrl = $this->getBaseUrl();
        $inviteUrl = "{$baseUrl}/pin/cadastro/{$token}";

        // Mapear role para qual bloco de webhook usar
        // buyer/delivery = mesmas pessoas (passo 5 - entrega/checklist)
        // quoter = cotação (passo 1)
        // approver = aprovação (passo 2)
        // payment = pagamento (passo 4)
        $webhookMap = [
            'buyer' => ['orders_delivery_webhook', 'orders_delivery_phone', 'orders_delivery_phone_name'],
            'quoter' => ['orders_quote_webhook', 'orders_quote_phone', 'orders_quote_phone_name'],
            'approver' => ['orders_approval_webhook', 'orders_approval_phone', 'orders_approval_phone_name'],
            'payment' => ['orders_payment_webhook', 'orders_payment_phone', 'orders_payment_phone_name'],
            'delivery' => ['orders_delivery_webhook', 'orders_delivery_phone', 'orders_delivery_phone_name'],
            'all' => ['orders_delivery_webhook', 'orders_delivery_phone', 'orders_delivery_phone_name'],
        ];

        $config = $webhookMap[$role] ?? $webhookMap['all'];
        $webhookUrl = Setting::get($config[0], '');
        $phone = Setting::get($config[1], '');
        $phoneName = Setting::get($config[2], '');

        if (empty(trim($webhookUrl))) {
            $this->json(['error' => 'Webhook não configurado para esta permissão.'], 400);
            return;
        }

        $roleLabels = ['buyer'=>'Comprador','quoter'=>'Cotador','approver'=>'Aprovador','payment'=>'Financeiro','delivery'=>'Entrega','stock'=>'Estoque','all'=>'Completo'];
        $roleLabel = $roleLabels[$role] ?? $role;

        $message = "*CADASTRO NO SISTEMA*\n\n"
            . "Acesse o link abaixo para criar sua conta no sistema Brooks Construtora:\n\n"
            . "*Perfil:* {$roleLabel}\n\n"
            . "*Link de cadastro:*\n{$inviteUrl}\n\n"
            . "Crie um PIN de 4 dígitos para acessar o sistema.";

        $this->sendWebhook($webhookUrl, [
            'event' => 'invite_link',
            'invite_url' => $inviteUrl,
            'role' => $role,
            'phone' => $phone,
            'phone_name' => $phoneName,
            'message' => $message,
        ]);

        $this->json(['success' => true, 'message' => 'Link enviado via webhook!']);
    }

    /**
     * Atualizar permissão do usuário PIN
     */
    public function updatePinUser(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }
        $id = (int) $this->input('id');
        $role = $this->input('role', 'all');
        $validRoles = ['buyer', 'quoter', 'approver', 'payment', 'delivery', 'epi', 'stock', 'all'];
        if (!in_array($role, $validRoles)) $role = 'all';
        Database::update('pin_users', ['role' => $role], 'id = ?', [$id]);
        $this->setFlash('success', 'Permissão atualizada.');
        $this->redirect('/admin/orders/pin-users');
    }

    /**
     * Atualizar telefone do usuário PIN
     */
    public function updatePinUserPhone(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }
        $id = (int) $this->input('id');
        $phone = preg_replace('/[^0-9]/', '', $this->input('phone', ''));
        Database::update('pin_users', ['phone' => $phone ?: null], 'id = ?', [$id]);
        $this->setFlash('success', 'Telefone atualizado.');
        $this->redirect('/admin/orders/pin-users');
    }

    public function updatePinUserEmail(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/orders/pin-users'); return; }
        $id = (int) $this->input('id');
        $email = trim($this->input('email', ''));

        // Valida formato se preenchido
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'E-mail inválido.');
            $this->redirect('/admin/orders/pin-users');
            return;
        }

        Database::update('pin_users', ['email' => $email ?: null], 'id = ?', [$id]);
        $this->setFlash('success', 'E-mail atualizado.');
        $this->redirect('/admin/orders/pin-users');
    }

    // ============================
    // REENVIO DE NOTIFICAÇÕES
    // ============================

    /**
     * Reenviar uma notificação individual (coloca de volta na fila)
     */
    public function resendNotification(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $id = (int) $this->input('id');
        $notification = \App\Models\NotificationQueue::find($id);

        if (!$notification) {
            $this->json(['error' => 'Notificação não encontrada.'], 404);
            return;
        }

        // Resetar para pendente para ser reenviada
        \App\Models\NotificationQueue::updateById($id, [
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'sent_at' => null,
            'scheduled_at' => date('Y-m-d H:i:s'),
        ]);

        // Tentar processar imediatamente
        NotificationService::processImmediate();

        $this->json(['success' => true, 'message' => 'Notificação colocada na fila para reenvio.']);
    }

    /**
     * Reenviar todas as notificações de uma fase do pedido
     * Também funciona para pedidos sem histórico — regenera e envia as notificações da fase
     */
    public function resendAllPhase(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $orderId = (int) $this->input('order_id');
        $phase = $this->input('phase', '');
        $sendEmail = $this->input('send_email', '1') === '1';
        $sendWebhook = $this->input('send_webhook', '1') === '1';

        error_log("[BROOKS_WEBHOOK] resendAllPhase CHAMADO: orderId={$orderId} phase={$phase} sendEmail={$sendEmail} sendWebhook={$sendWebhook}");

        $order = PurchaseOrder::findFull($orderId);
        if (!$order) {
            $this->json(['error' => 'Pedido não encontrado.'], 404);
            return;
        }

        $validPhases = ['quote_requested', 'approval_requested', 'order_approved', 'order_rejected', 'payment_uploaded', 'delivery_ready', 'spare_item', 'stock_transport'];

        if (!in_array($phase, $validPhases)) {
            $this->json(['error' => 'Fase inválida.'], 400);
            return;
        }

        // Helper para enviar só o que foi selecionado
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);

        // Resolver modos de notificação por fase
        $constructionSiteId = !empty($order['construction_site_id']) ? (int) $order['construction_site_id'] : null;

        switch ($phase) {
            case 'quote_requested':
                $quoteMode = Setting::get('orders_quote_notify_mode', 'both');
                $siteQuoteUsers = [];
                if (!empty($constructionSiteId)) {
                    $siteQuoteUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'quote');
                }
                $sendGlobal = in_array($quoteMode, ['both', 'global_only']);
                $sendSiteMode = in_array($quoteMode, ['both', 'site_only']) && !empty($siteQuoteUsers);
                if ($quoteMode === 'site_only' && empty($siteQuoteUsers)) $sendGlobal = true;

                if ($sendEmail) {
                    $baseUrl = $this->getBaseUrl();
                    $quoteUrl = "{$baseUrl}/pedido/cotacao/{$order['quote_token']}";
                    $subject = "Cotação Pendente - Pedido {$order['code']}";
                    $body = EmailTemplate::purchaseOrderQuote($order, $items, $quoteUrl, $orderSuppliers);

                    if ($sendGlobal) {
                        $emails = Setting::get('orders_quote_emails', '');
                        if (!empty($emails)) {
                            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'quote_requested');
                        }
                    }
                    if ($sendSiteMode) {
                        $siteEmails = implode(',', array_filter(array_column($siteQuoteUsers, 'email')));
                        if (!empty($siteEmails)) {
                            NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'quote_requested');
                        }
                    }
                }
                if ($sendWebhook) {
                    $webhookUrl = Setting::get('orders_quote_webhook', '');
                    if (!empty($webhookUrl)) {
                        $baseUrl = $this->getBaseUrl();
                        $quoteUrl = "{$baseUrl}/pedido/cotacao/{$order['quote_token']}";
                        $itemsList = '';
                        $quoteItemCount = 0;
                        foreach ($items as $item) {
                            if (!empty($item['source_type']) && $item['source_type'] !== 'purchase') continue;
                            $quoteItemCount++;
                            $qty = (float) $item['quantity'];
                            $qtyFmt = $qty == (int) $qty ? number_format($qty, 0) : number_format($qty, 2, ',', '.');
                            $itemsList .= $quoteItemCount . ". {$item['material_name']} - Qtd: {$qtyFmt} {$item['unit']}\n";
                        }
                        $message = "*NOVO PEDIDO - COTAÇÃO PENDENTE*\n\n*Pedido:* {$order['code']}\n*Itens:* " . $quoteItemCount . "\n\n*Link:*\n{$quoteUrl}";

                        if ($sendSiteMode) {
                            $sitePhones = implode(',', array_filter(array_column($siteQuoteUsers, 'phone')));
                            $siteNames = implode(',', array_filter(array_column($siteQuoteUsers, 'name')));
                            if (!empty($sitePhones)) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'quote_requested', 'order_code' => $order['code'],
                                    'items_count' => $quoteItemCount, 'quote_url' => $quoteUrl,
                                    'phone' => $sitePhones,
                                    'phone_name' => $siteNames,
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                        if ($sendGlobal) {
                            $this->sendWebhook($webhookUrl, [
                                'event' => 'quote_requested', 'order_code' => $order['code'],
                                'items_count' => $quoteItemCount, 'quote_url' => $quoteUrl,
                                'phone' => Setting::get('orders_quote_phone', ''),
                                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                                'message' => $message,
                            ], $orderId);
                        }
                    }
                }
                break;

            case 'approval_requested':
                $approvalMode = Setting::get('orders_approval_notify_mode', 'both');
                $siteApprovalUsers = [];
                if (!empty($constructionSiteId)) {
                    $siteApprovalUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'approval');
                }
                $sendGlobal = in_array($approvalMode, ['both', 'global_only']);
                $sendSiteMode = in_array($approvalMode, ['both', 'site_only']) && !empty($siteApprovalUsers);
                if ($approvalMode === 'site_only' && empty($siteApprovalUsers)) $sendGlobal = true;

                if ($sendEmail) {
                    $baseUrl = $this->getBaseUrl();
                    $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$order['approval_token']}";
                    $subject = "Aprovação Pendente - Pedido {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
                    $body = EmailTemplate::purchaseOrderApproval($order, $items, $approvalUrl, $orderSuppliers);

                    if ($sendGlobal) {
                        $emails = Setting::get('orders_approval_emails', '');
                        if (!empty($emails)) {
                            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'approval_requested');
                        }
                    }
                    if ($sendSiteMode) {
                        $siteEmails = implode(',', array_filter(array_column($siteApprovalUsers, 'email')));
                        if (!empty($siteEmails)) {
                            NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'approval_requested');
                        }
                    }
                }
                if ($sendWebhook) {
                    $webhookUrl = Setting::get('orders_approval_webhook', '');
                    if (!empty($webhookUrl)) {
                        $baseUrl = $this->getBaseUrl();
                        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$order['approval_token']}";
                        $message = "*APROVAÇÃO PENDENTE*\n\n*Pedido:* {$order['code']}\n*Valor:* R$ " . number_format($order['total_estimated'], 2, ',', '.') . "\n\n*Link:*\n{$approvalUrl}";

                        if ($sendSiteMode) {
                            $sitePhones = implode(',', array_filter(array_column($siteApprovalUsers, 'phone')));
                            $siteNames = implode(',', array_filter(array_column($siteApprovalUsers, 'name')));
                            if (!empty($sitePhones)) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'approval_requested', 'order_code' => $order['code'],
                                    'total' => $order['total_estimated'], 'approval_url' => $approvalUrl,
                                    'phone' => $sitePhones,
                                    'phone_name' => $siteNames,
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                        if ($sendGlobal) {
                            $this->sendWebhook($webhookUrl, [
                                'event' => 'approval_requested', 'order_code' => $order['code'],
                                'total' => $order['total_estimated'], 'approval_url' => $approvalUrl,
                                'phone' => Setting::get('orders_approval_phone', ''),
                                'phone_name' => Setting::get('orders_approval_phone_name', ''),
                                'message' => $message,
                            ], $orderId);
                        }
                    }
                }
                break;

            case 'order_approved':
                $completedMode = Setting::get('orders_completed_notify_mode', 'both');
                $siteCompletedUsers = [];
                if (!empty($constructionSiteId)) {
                    $siteCompletedUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'completed');
                }
                $sendGlobal = in_array($completedMode, ['both', 'global_only']);
                $sendSiteMode = in_array($completedMode, ['both', 'site_only']) && !empty($siteCompletedUsers);
                if ($completedMode === 'site_only' && empty($siteCompletedUsers)) $sendGlobal = true;

                if ($sendEmail) {
                    $baseUrl = $this->getBaseUrl();
                    $viewUrl = "{$baseUrl}/pedido/pdf/{$orderId}";
                    $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
                    $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
                    $body = EmailTemplate::purchaseOrderCompleted($order, $items, $viewUrl, '', $approvedSuppliers);

                    if ($sendGlobal) {
                        $emails = Setting::get('orders_completed_emails', '');
                        if (!empty($emails)) {
                            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'order_approved');
                        }
                    }
                    if ($sendSiteMode) {
                        $siteEmails = implode(',', array_filter(array_column($siteCompletedUsers, 'email')));
                        if (!empty($siteEmails)) {
                            NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'order_approved');
                        }
                    }
                }
                if ($sendWebhook) {
                    $webhookUrl = Setting::get('orders_completed_webhook', '');
                    if (!empty($webhookUrl)) {
                        $baseUrl = $this->getBaseUrl();
                        $viewUrl = "{$baseUrl}/pedido/pdf/{$orderId}";
                        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
                        $approvedNames = !empty($approvedSuppliers) ? array_column($approvedSuppliers, 'supplier_name') : [];
                        $supplierDisplay = !empty($approvedNames) ? implode(', ', $approvedNames) : ($order['supplier_name'] ?? 'N/A');
                        $message = "*PEDIDO APROVADO*\n\n*Pedido:* {$order['code']}\n*Fornecedor(es):* {$supplierDisplay}\n*Valor:* R$ " . number_format($order['total_estimated'], 2, ',', '.') . "\n\n*PDF:*\n{$viewUrl}";

                        if ($sendSiteMode) {
                            $sitePhones = implode(',', array_filter(array_column($siteCompletedUsers, 'phone')));
                            $siteNames = implode(',', array_filter(array_column($siteCompletedUsers, 'name')));
                            if (!empty($sitePhones)) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'order_approved', 'order_code' => $order['code'],
                                    'approved_suppliers' => $approvedNames, 'total' => $order['total_estimated'],
                                    'pdf_url' => $viewUrl,
                                    'phone' => $sitePhones,
                                    'phone_name' => $siteNames,
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                        if ($sendGlobal) {
                            $this->sendWebhook($webhookUrl, [
                                'event' => 'order_approved', 'order_code' => $order['code'],
                                'approved_suppliers' => $approvedNames, 'total' => $order['total_estimated'],
                                'pdf_url' => $viewUrl,
                                'phone' => Setting::get('orders_completed_phone', ''),
                                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                                'message' => $message,
                            ], $orderId);
                        }
                    }
                }
                break;

            case 'order_rejected':
                $completedMode = Setting::get('orders_completed_notify_mode', 'both');
                $siteCompletedUsers = [];
                if (!empty($constructionSiteId)) {
                    $siteCompletedUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'completed');
                }
                $sendGlobal = in_array($completedMode, ['both', 'global_only']);
                $sendSiteMode = in_array($completedMode, ['both', 'site_only']) && !empty($siteCompletedUsers);
                if ($completedMode === 'site_only' && empty($siteCompletedUsers)) $sendGlobal = true;

                if ($sendEmail) {
                    $subject = "Pedido REJEITADO - {$order['code']}";
                    $body = EmailTemplate::purchaseOrderRejected($order, $order['rejected_by_name'] ?? 'N/A', $order['approval_notes'] ?? '');

                    if ($sendGlobal) {
                        $emails = Setting::get('orders_completed_emails', '');
                        if (!empty($emails)) {
                            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'order_rejected');
                        }
                    }
                    if ($sendSiteMode) {
                        $siteEmails = implode(',', array_filter(array_column($siteCompletedUsers, 'email')));
                        if (!empty($siteEmails)) {
                            NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'order_rejected');
                        }
                    }
                }
                if ($sendWebhook) {
                    $webhookUrl = Setting::get('orders_completed_webhook', '');
                    if (!empty($webhookUrl)) {
                        $supplierNames = !empty($orderSuppliers) ? array_column($orderSuppliers, 'supplier_name') : [];
                        $supplierDisplay = !empty($supplierNames) ? implode(', ', $supplierNames) : ($order['supplier_name'] ?? 'N/A');
                        $message = "*PEDIDO REJEITADO*\n\n*Pedido:* {$order['code']}\n*Fornecedores:* {$supplierDisplay}\n*Rejeitado por:* " . ($order['rejected_by_name'] ?? 'N/A') . "\n*Motivo:* " . ($order['approval_notes'] ?? '-');

                        if ($sendSiteMode) {
                            $sitePhones = implode(',', array_filter(array_column($siteCompletedUsers, 'phone')));
                            $siteNames = implode(',', array_filter(array_column($siteCompletedUsers, 'name')));
                            if (!empty($sitePhones)) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'order_rejected', 'order_code' => $order['code'],
                                    'suppliers' => $supplierNames, 'rejected_by' => $order['rejected_by_name'] ?? '',
                                    'reason' => $order['approval_notes'] ?? '',
                                    'phone' => $sitePhones,
                                    'phone_name' => $siteNames,
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                        if ($sendGlobal) {
                            $this->sendWebhook($webhookUrl, [
                                'event' => 'order_rejected', 'order_code' => $order['code'],
                                'suppliers' => $supplierNames, 'rejected_by' => $order['rejected_by_name'] ?? '',
                                'reason' => $order['approval_notes'] ?? '',
                                'phone' => Setting::get('orders_completed_phone', ''),
                                'phone_name' => Setting::get('orders_completed_phone_name', ''),
                                'message' => $message,
                            ], $orderId);
                        }
                    }
                }
                break;

            case 'payment_uploaded':
                $paymentMode = Setting::get('orders_payment_notify_mode', 'both');
                $sitePaymentUsers = [];
                if (!empty($constructionSiteId)) {
                    $sitePaymentUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'payment');
                }
                $sendGlobal = in_array($paymentMode, ['both', 'global_only']);
                $sendSiteMode = in_array($paymentMode, ['both', 'site_only']) && !empty($sitePaymentUsers);
                if ($paymentMode === 'site_only' && empty($sitePaymentUsers)) $sendGlobal = true;

                $payments = PurchaseOrderPayment::getByOrder($orderId);
                $hasPayments = !empty($payments);
                $baseUrl = $this->getBaseUrl();
                $panelUrl = "{$baseUrl}/pedidos";
                
                if ($hasPayments) {
                    $lastPayment = $payments[0];
                    $typeLabel = strtoupper($lastPayment['type']);
                    $docData = ['number' => $lastPayment['number'] ?? '', 'amount' => $lastPayment['amount'] ?? 0, 'due_date' => $lastPayment['due_date'] ?? ''];
                    
                    if ($sendEmail) {
                        $subject = "{$typeLabel} Enviado - Pedido {$order['code']}";
                        $body = EmailTemplate::purchaseOrderPayment($order, $typeLabel, $docData, $lastPayment['uploaded_by'] ?? 'Sistema', $panelUrl);

                        if ($sendGlobal) {
                            $emails = Setting::get('orders_payment_emails', '');
                            if (!empty($emails)) {
                                NotificationService::queueEmails($emails, $subject, $body, $orderId, 'payment_uploaded');
                            }
                        }
                        if ($sendSiteMode) {
                            $siteEmails = implode(',', array_filter(array_column($sitePaymentUsers, 'email')));
                            if (!empty($siteEmails)) {
                                NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'payment_uploaded');
                            }
                        }
                    }
                    if ($sendWebhook) {
                        $webhookUrl = Setting::get('orders_payment_webhook', '');
                        if (!empty(trim($webhookUrl))) {
                            $amountFmt = $docData['amount'] ? 'R$ ' . number_format((float)$docData['amount'], 2, ',', '.') : 'N/A';
                            $message = "*{$typeLabel} RECEBIDO*\n\n"
                                . "*Pedido:* {$order['code']}\n"
                                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                                . "*Tipo:* {$typeLabel}\n"
                                . (!empty($docData['number']) ? "*Número:* {$docData['number']}\n" : '')
                                . "*Valor:* {$amountFmt}\n"
                                . "*Enviado por:* " . ($lastPayment['uploaded_by'] ?? '-') . "\n\n"
                                . "*Ver pedido:*\n{$panelUrl}";

                            if ($sendSiteMode) {
                                $sitePhones = implode(',', array_filter(array_column($sitePaymentUsers, 'phone')));
                                $siteNames = implode(',', array_filter(array_column($sitePaymentUsers, 'name')));
                                if (!empty($sitePhones)) {
                                    $this->sendWebhook($webhookUrl, [
                                        'event' => 'payment_uploaded', 'order_code' => $order['code'],
                                        'supplier' => $order['supplier_name'] ?? 'N/A',
                                        'document_type' => $typeLabel, 'amount' => $docData['amount'],
                                        'phone' => $sitePhones,
                                        'phone_name' => $siteNames,
                                        'message' => $message,
                                    ], $orderId);
                                }
                            }
                            if ($sendGlobal) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'payment_uploaded', 'order_code' => $order['code'],
                                    'supplier' => $order['supplier_name'] ?? 'N/A',
                                    'document_type' => $typeLabel, 'amount' => $docData['amount'],
                                    'phone' => Setting::get('orders_payment_phone', ''),
                                    'phone_name' => Setting::get('orders_payment_phone_name', ''),
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                    }
                } else {
                    $totalFmt = 'R$ ' . number_format((float)$order['total_estimated'], 2, ',', '.');
                    
                    if ($sendEmail) {
                        $subject = "NF/Boleto Pendente - Pedido {$order['code']} - {$totalFmt}";
                        $body = EmailTemplate::purchaseOrderPaymentPending($order, $panelUrl);

                        if ($sendGlobal) {
                            $emails = Setting::get('orders_payment_emails', '');
                            if (!empty($emails)) {
                                NotificationService::queueEmails($emails, $subject, $body, $orderId, 'payment_pending');
                            }
                        }
                        if ($sendSiteMode) {
                            $siteEmails = implode(',', array_filter(array_column($sitePaymentUsers, 'email')));
                            if (!empty($siteEmails)) {
                                NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'payment_pending');
                            }
                        }
                    }
                    if ($sendWebhook) {
                        $webhookUrl = Setting::get('orders_payment_webhook', '');
                        if (!empty(trim($webhookUrl))) {
                            $message = "*NF/BOLETO PENDENTE*\n\n"
                                . "*Pedido:* {$order['code']}\n"
                                . "*Fornecedor:* " . ($order['supplier_name'] ?? 'N/A') . "\n"
                                . "*Valor:* {$totalFmt}\n"
                                . "*Aprovado por:* " . ($order['approved_by_name'] ?? '-') . "\n\n"
                                . "Acesse o painel para enviar a NF ou boleto:\n{$panelUrl}";

                            if ($sendSiteMode) {
                                $sitePhones = implode(',', array_filter(array_column($sitePaymentUsers, 'phone')));
                                $siteNames = implode(',', array_filter(array_column($sitePaymentUsers, 'name')));
                                if (!empty($sitePhones)) {
                                    $this->sendWebhook($webhookUrl, [
                                        'event' => 'payment_pending', 'order_code' => $order['code'],
                                        'supplier' => $order['supplier_name'] ?? 'N/A',
                                        'total' => $order['total_estimated'],
                                        'panel_url' => $panelUrl,
                                        'phone' => $sitePhones,
                                        'phone_name' => $siteNames,
                                        'message' => $message,
                                    ], $orderId);
                                }
                            }
                            if ($sendGlobal) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'payment_pending', 'order_code' => $order['code'],
                                    'supplier' => $order['supplier_name'] ?? 'N/A',
                                    'total' => $order['total_estimated'],
                                    'panel_url' => $panelUrl,
                                    'phone' => Setting::get('orders_payment_phone', ''),
                                    'phone_name' => Setting::get('orders_payment_phone_name', ''),
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                    }
                }
                break;

            case 'delivery_ready':
                $deliveryMode = Setting::get('orders_delivery_notify_mode', 'both');
                $siteDeliveryUsers = [];
                if (!empty($constructionSiteId)) {
                    $siteDeliveryUsers = \App\Models\ConstructionSite::getApprovers($constructionSiteId, 'delivery');
                }
                $sendGlobal = in_array($deliveryMode, ['both', 'global_only']);
                $sendSiteMode = in_array($deliveryMode, ['both', 'site_only']) && !empty($siteDeliveryUsers);
                if ($deliveryMode === 'site_only' && empty($siteDeliveryUsers)) $sendGlobal = true;

                if (!empty($order['delivery_token'])) {
                    if ($sendEmail) {
                        $baseUrl = $this->getBaseUrl();
                        $checklistUrl = "{$baseUrl}/pedido/entrega/{$order['delivery_token']}";
                        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
                        $supplierDisplay = !empty($approvedSuppliers) ? implode(', ', array_column($approvedSuppliers, 'supplier_name')) : ($order['supplier_name'] ?? 'N/A');
                        $subject = "Checklist de Entrega - Pedido {$order['code']}";
                        $body = EmailTemplate::purchaseOrderDelivery($order, $items, $checklistUrl, $supplierDisplay);

                        if ($sendGlobal) {
                            $emails = Setting::get('orders_delivery_emails', '');
                            if (!empty($emails)) {
                                NotificationService::queueEmails($emails, $subject, $body, $orderId, 'delivery_ready');
                            }
                        }
                        if ($sendSiteMode) {
                            $siteEmails = implode(',', array_filter(array_column($siteDeliveryUsers, 'email')));
                            if (!empty($siteEmails)) {
                                NotificationService::queueEmails($siteEmails, $subject, $body, $orderId, 'delivery_ready');
                            }
                        }
                    }
                    if ($sendWebhook) {
                        $webhookUrl = Setting::get('orders_delivery_webhook', '');
                        if (!empty($webhookUrl)) {
                            $baseUrl = $this->getBaseUrl();
                            $checklistUrl = "{$baseUrl}/pedido/entrega/{$order['delivery_token']}";
                            $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
                            $supplierNames = !empty($approvedSuppliers) ? array_column($approvedSuppliers, 'supplier_name') : [];
                            $message = "*CHECKLIST DE ENTREGA*\n\n*Pedido:* {$order['code']}\n*Itens:* " . count($items) . "\n\n*Link:*\n{$checklistUrl}";

                            if ($sendSiteMode) {
                                $sitePhones = implode(',', array_filter(array_column($siteDeliveryUsers, 'phone')));
                                $siteNames = implode(',', array_filter(array_column($siteDeliveryUsers, 'name')));
                                if (!empty($sitePhones)) {
                                    $this->sendWebhook($webhookUrl, [
                                        'event' => 'delivery_checklist_ready', 'order_code' => $order['code'],
                                        'suppliers' => $supplierNames, 'checklist_url' => $checklistUrl,
                                        'phone' => $sitePhones,
                                        'phone_name' => $siteNames,
                                        'message' => $message,
                                    ], $orderId);
                                }
                            }
                            if ($sendGlobal) {
                                $this->sendWebhook($webhookUrl, [
                                    'event' => 'delivery_checklist_ready', 'order_code' => $order['code'],
                                    'suppliers' => $supplierNames, 'checklist_url' => $checklistUrl,
                                    'phone' => Setting::get('orders_delivery_phone', ''),
                                    'phone_name' => Setting::get('orders_delivery_phone_name', ''),
                                    'message' => $message,
                                ], $orderId);
                            }
                        }
                    }
                }
                break;

            case 'spare_item':
                break;

            case 'stock_transport':
                // Reenviar notificações de transporte para os itens de estoque deste pedido
                $stockMovements = \App\Models\StockMovement::getByOrder($orderId);
                foreach ($stockMovements as $mov) {
                    $this->notifyTransportMovement($mov['id']);
                }
                break;
        }

        $channels = [];
        if ($sendEmail) $channels[] = 'e-mail';
        if ($sendWebhook) $channels[] = 'webhook';

        PurchaseOrderHistory::log($orderId, 'notifications_resent',
            "Notificações reenviadas ({$phase}): " . implode(', ', $channels),
            Auth::user()['name'] ?? 'Sistema', Auth::id());

        $this->json(['success' => true, 'message' => ucfirst(implode(' e ', $channels)) . " reenviado(s)!"]);
    }

    private function sendSpareItemNotification(int $orderId, string $description, float $total, string $purchasedBy): void
    {
        $order = PurchaseOrder::find($orderId);
        if (!$order) return;

        $weeklyBudget = (float) Setting::get('spare_items_weekly_budget', 1000);
        $weekTotal = PurchaseOrderSpareItem::totalThisWeek();
        $remaining = $weeklyBudget - $weekTotal;

        $baseUrl = $this->getBaseUrl();

        // E-mail
        $emails = Setting::get('orders_spare_emails', '');
        if (!empty($emails)) {
            $subject = "Item Sobressalente - Pedido {$order['code']} - R$ " . number_format($total, 2, ',', '.');
            $body = \App\Services\EmailTemplate::spareItemAdded($order, $description, $total, $purchasedBy, $weekTotal, $weeklyBudget);
            NotificationService::queueEmails($emails, $subject, $body, $orderId, 'spare_item');
        }

        // Webhook
        $webhookUrl = Setting::get('orders_spare_webhook', '');
        if (!empty($webhookUrl)) {
            $message = "*ITEM SOBRESSALENTE ADICIONADO*\n\n"
                . "*Pedido:* {$order['code']}\n"
                . "*Item:* {$description}\n"
                . "*Valor:* R$ " . number_format($total, 2, ',', '.') . "\n"
                . "*Comprado por:* {$purchasedBy}\n\n"
                . "*Saldo semanal:*\n"
                . "Gasto: R$ " . number_format($weekTotal, 2, ',', '.') . " / R$ " . number_format($weeklyBudget, 2, ',', '.') . "\n"
                . "Restante: R$ " . number_format(max(0, $remaining), 2, ',', '.')
                . ($remaining < 0 ? " ⚠️ *EXCEDIDO!*" : '');

            $this->sendWebhook($webhookUrl, [
                'event' => 'spare_item_added',
                'order_code' => $order['code'],
                'item' => $description,
                'total' => $total,
                'purchased_by' => $purchasedBy,
                'week_total' => $weekTotal,
                'weekly_budget' => $weeklyBudget,
                'remaining' => max(0, $remaining),
                'exceeded' => $remaining < 0,
                'phone' => Setting::get('orders_spare_phone', ''),
                'phone_name' => Setting::get('orders_spare_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    /**
     * Visão de acompanhamento de entregas de todos os pedidos aprovados
     */
    public function tracking(): void
    {
        // Buscar todos os pedidos aprovados que têm checklist de entrega
        $hasCS = false;
        try {
            $chk = Database::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1");
            $hasCS = !empty($chk);
        } catch (\Exception $e) {}

        if ($hasCS) {
            $orders = Database::fetchAll(
                "SELECT po.id, po.code, po.total_estimated, po.approved_by_name, po.approved_at, po.delivery_token,
                        s.name as supplier_name,
                        cs.name as construction_site_name, cs.code as construction_site_code
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.status = 'approved'
                 AND EXISTS (SELECT 1 FROM purchase_order_deliveries pod WHERE pod.order_id = po.id)
                 ORDER BY po.approved_at DESC"
            );
        } else {
            $orders = Database::fetchAll(
                "SELECT po.id, po.code, po.total_estimated, po.approved_by_name, po.approved_at, po.delivery_token,
                        s.name as supplier_name
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 WHERE po.status = 'approved'
                 AND EXISTS (SELECT 1 FROM purchase_order_deliveries pod WHERE pod.order_id = po.id)
                 ORDER BY po.approved_at DESC"
            );
        }

        // Para cada pedido, buscar itens de entrega com dados completos
        $trackingData = [];
        $today = date('Y-m-d');

        foreach ($orders as $order) {
            $deliveries = Database::fetchAll(
                "SELECT pod.*, poi.material_name, poi.quantity, poi.unit, poi.approved_supplier_id,
                        s.name as supplier_name,
                        pos.payment_method, pos.payment_condition, pos.payment_first_due, pos.delivery_days
                 FROM purchase_order_deliveries pod
                 JOIN purchase_order_items poi ON pod.item_id = poi.id
                 LEFT JOIN suppliers s ON pod.supplier_id = s.id
                 LEFT JOIN purchase_order_suppliers pos ON pos.order_id = pod.order_id AND pos.supplier_id = pod.supplier_id
                 WHERE pod.order_id = ?
                 ORDER BY pod.expected_date ASC, pod.id ASC",
                [$order['id']]
            );

            $lateCount = 0;
            $pendingCount = 0;
            $doneCount = 0;

            foreach ($deliveries as &$d) {
                $d['is_late'] = false;
                if ($d['status'] !== 'checked' && $d['status'] !== 'delivered' && $d['status'] !== 'replacement_delivered') {
                    if ($d['expected_date'] && $d['expected_date'] < $today) { $d['is_late'] = true; $lateCount++; }
                    elseif ($d['status'] === 'replacement_requested' && $d['replacement_expected_date'] && $d['replacement_expected_date'] < $today) { $d['is_late'] = true; $lateCount++; }
                    $pendingCount++;
                } else {
                    $doneCount++;
                }
            }
            unset($d);

            $trackingData[] = [
                'order' => $order,
                'deliveries' => $deliveries,
                'late_count' => $lateCount,
                'pending_count' => $pendingCount,
                'done_count' => $doneCount,
                'total_count' => count($deliveries),
            ];
        }

        $this->view('admin.orders.tracking', [
            'trackingData' => $trackingData,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Retorna dados atualizados do checklist (para polling AJAX no admin)
     */
    public function deliveryData(): void
    {
        $orderId = (int) $this->input('order_id', 0);
        if (!$orderId) { $this->json(['error' => 'order_id obrigatório'], 400); return; }

        $deliveries = PurchaseOrderDelivery::getByOrder($orderId);
        $history = Database::fetchAll(
            "SELECT * FROM purchase_order_delivery_history WHERE order_id = ? ORDER BY created_at DESC LIMIT 50",
            [$orderId]
        );

        $this->json(['deliveries' => $deliveries, 'history' => $history]);
    }

    /**
     * Define a data esperada de entrega (por item ou por fornecedor)
     */
    public function deliveryExpectedDate(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $orderId = (int) $this->input('order_id');
        $supplierId = (int) $this->input('supplier_id', 0);
        $itemId = (int) $this->input('item_id', 0);
        $expectedDate = $this->input('expected_date', '');

        if (empty($expectedDate)) {
            $this->json(['error' => 'Data não informada.'], 400);
            return;
        }

        if ($supplierId > 0) {
            // Atualiza todos os itens deste fornecedor
            Database::query(
                "UPDATE purchase_order_deliveries SET expected_date = ? WHERE order_id = ? AND supplier_id = ?",
                [$expectedDate, $orderId, $supplierId]
            );
        } elseif ($itemId > 0) {
            // Atualiza apenas este item
            Database::query(
                "UPDATE purchase_order_deliveries SET expected_date = ? WHERE order_id = ? AND item_id = ?",
                [$expectedDate, $orderId, $itemId]
            );
        } else {
            $this->json(['error' => 'Informe supplier_id ou item_id.'], 400);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Extrair texto bruto de um PDF (sem bibliotecas externas)
     */
    private function extractTextFromPdf(string $rawContent): string
    {
        $text = '';
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $rawContent, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/\((.*?)\)/s', $block, $strings)) {
                    $text .= implode(' ', $strings[1]) . "\n";
                }
            }
        }
        if (strlen($text) < 50) {
            if (preg_match_all('/\(([^)]+)\)\s*Tj/s', $rawContent, $tjMatches)) {
                $text .= implode("\n", $tjMatches[1]);
            }
        }
        $text = preg_replace('/[^\x20-\x7E\xC0-\xFF\n]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Fallback: Upload PDF via Files API e usar Responses API
     */
    private function parsePdfViaResponsesApi(string $tmpPath, string $fileName, string $apiKey, string $model): ?array
    {
        // 1. Upload do arquivo com purpose user_data
        $ch = curl_init('https://api.openai.com/v1/files');
        $cFile = new \CURLFile($tmpPath, 'application/pdf', $fileName);
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

        if ($uploadCode !== 200) {
            $err = json_decode($uploadResp, true);
            return ['error' => 'Erro no upload do PDF: ' . ($err['error']['message'] ?? "HTTP {$uploadCode}")];
        }

        $uploadData = json_decode($uploadResp, true);
        $fileId = $uploadData['id'] ?? null;
        if (!$fileId) return ['error' => 'Falha ao obter ID do arquivo.'];

        // 2. Usar Responses API com input_file
        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_file',
                                'file_id' => $fileId,
                            ],
                            [
                                'type' => 'input_text',
                                'text' => 'Analise este PDF e extraia TODOS os materiais/produtos listados. Retorne APENAS um JSON array (sem markdown, sem explicação). Cada item deve ter: name (nome do material), specification (tipo como "mat. Hidraulica", "mat. Civil", "madeira", "MATERIAL", "SERVICOS", etc), classification (medida como "100mm", "3/4", "50x40", etc), unit (unidade como "UN", "M", "KG", "M2", "M3", "L", etc), quantity (quantidade numérica, use 1 se não especificado). Retorne APENAS o JSON array.'
                            ]
                        ]
                    ]
                ],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 3. Deletar arquivo da OpenAI
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            return ['error' => 'Erro na API OpenAI: ' . ($err['error']['message'] ?? "HTTP {$httpCode}")];
        }

        // 4. Extrair resposta
        $result = json_decode($response, true);
        
        // Responses API retorna em formato diferente do Chat Completions
        $responseText = '';
        if (isset($result['output'])) {
            foreach ($result['output'] as $output) {
                if (isset($output['content'])) {
                    foreach ($output['content'] as $content) {
                        if (isset($content['text'])) {
                            $responseText .= $content['text'];
                        }
                    }
                }
            }
        } elseif (isset($result['choices'][0]['message']['content'])) {
            $responseText = $result['choices'][0]['message']['content'];
        }

        if (empty($responseText)) return ['error' => 'Resposta vazia da IA.'];

        // Limpar markdown
        $responseText = preg_replace('/```json\s*/', '', $responseText);
        $responseText = preg_replace('/```\s*/', '', $responseText);
        $materials = json_decode(trim($responseText), true);

        if (!is_array($materials)) return ['error' => 'Não foi possível interpretar o documento. Resposta: ' . mb_substr($responseText, 0, 200)];

        return ['success' => true, 'materials' => $materials];
    }

    /**
     * Parsear PDF de fornecedor de serviço via IA (AJAX) - salva PDF e analisa materiais
     */
    public function parseServicePdf(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $orderId = (int) $this->input('order_id', 0);
        $supplierId = (int) $this->input('supplier_id', 0);

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
                $this->json(['success' => true, 'pdf_id' => $pdfId, 'file_path' => '/uploads/orders/service_pdfs/' . $filename, 'materials' => [], 'warning' => 'Chave API OpenAI não configurada. PDF salvo, mas análise não realizada.']);
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
            } elseif ($result && isset($result['error'])) {
                $this->json([
                    'success' => true,
                    'pdf_id' => $pdfId,
                    'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                    'materials' => [],
                    'warning' => $result['error'],
                ]);
            } else {
                $this->json([
                    'success' => true,
                    'pdf_id' => $pdfId,
                    'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                    'materials' => [],
                    'warning' => 'Não foi possível extrair materiais do documento.',
                ]);
            }
        } catch (\Exception $e) {
            $this->json([
                'success' => true,
                'pdf_id' => $pdfId,
                'file_path' => '/uploads/orders/service_pdfs/' . $filename,
                'materials' => [],
                'warning' => 'Erro na análise: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Salvar materiais do PDF do fornecedor de serviço (AJAX)
     */
    public function saveServiceMaterials(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $orderId = (int) $this->input('order_id', 0);
        $supplierId = (int) $this->input('supplier_id', 0);
        $pdfId = (int) $this->input('pdf_id', 0);
        $materials = json_decode($this->input('materials', '[]'), true);

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
                'unit_price' => !empty($mat['unit_price']) ? (float) str_replace(['.', ','], ['', '.'], $mat['unit_price']) : null,
                'total_price' => !empty($mat['total_price']) ? (float) str_replace(['.', ','], ['', '.'], $mat['total_price']) : null,
                'subtotal' => !empty($mat['subtotal']) ? (float) str_replace(['.', ','], ['', '.'], $mat['subtotal']) : null,
                'discount' => !empty($mat['discount']) ? (float) str_replace(['.', ','], ['', '.'], $mat['discount']) : null,
                'freight' => !empty($mat['freight']) ? (float) str_replace(['.', ','], ['', '.'], $mat['freight']) : null,
                'ipi' => !empty($mat['ipi']) ? (float) str_replace(['.', ','], ['', '.'], $mat['ipi']) : null,
                'icms_st' => !empty($mat['icms_st']) ? (float) str_replace(['.', ','], ['', '.'], $mat['icms_st']) : null,
                'grand_total' => !empty($mat['grand_total']) ? (float) str_replace(['.', ','], ['', '.'], $mat['grand_total']) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $saved++;
        }

        $this->json(['success' => true, 'saved' => $saved]);
    }

    /**
     * Analisar PDF de serviço via OpenAI Responses API
     */
    private function parseServicePdfViaApi(string $filePath, string $fileName, string $apiKey, string $model): ?array
    {
        // 1. Upload do arquivo
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

        if ($uploadCode !== 200) {
            $err = json_decode($uploadResp, true);
            return ['error' => 'Erro no upload: ' . ($err['error']['message'] ?? "HTTP {$uploadCode}")];
        }

        $uploadData = json_decode($uploadResp, true);
        $fileId = $uploadData['id'] ?? null;
        if (!$fileId) return ['error' => 'Falha ao obter ID do arquivo.'];

        // 2. Analisar com Responses API
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
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_file', 'file_id' => $fileId],
                            ['type' => 'input_text', 'text' => $prompt]
                        ]
                    ]
                ],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 3. Deletar arquivo
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            return ['error' => 'Erro na API: ' . ($err['error']['message'] ?? "HTTP {$httpCode}")];
        }

        // 4. Extrair resposta
        $result = json_decode($response, true);
        $responseText = '';
        if (isset($result['output'])) {
            foreach ($result['output'] as $output) {
                if (isset($output['content'])) {
                    foreach ($output['content'] as $content) {
                        if (isset($content['text'])) {
                            $responseText .= $content['text'];
                        }
                    }
                }
            }
        }

        if (empty($responseText)) return ['error' => 'Resposta vazia da IA.'];

        $responseText = preg_replace('/```json\s*/', '', $responseText);
        $responseText = preg_replace('/```\s*/', '', $responseText);
        $parsed = json_decode(trim($responseText), true);

        if (!is_array($parsed)) return ['error' => 'Não foi possível interpretar. Resposta: ' . mb_substr($responseText, 0, 200)];

        // Pode vir como {materials: [...], totals: {...}} ou como array direto
        if (isset($parsed['materials'])) {
            return ['success' => true, 'materials' => $parsed['materials'], 'totals' => $parsed['totals'] ?? null];
        }

        // Assume que é array direto de materiais
        return ['success' => true, 'materials' => $parsed, 'totals' => null];
    }

    /**
     * Analisar imagem de serviço via OpenAI Chat Completions
     */
    private function parseServiceImageViaApi(string $filePath, string $mimeType, string $apiKey, string $model): ?array
    {
        $content = base64_encode(file_get_contents($filePath));

        $prompt = 'Analise esta imagem de orçamento/nota de materiais de construção civil. '
            . 'Extraia APENAS os itens da TABELA DE MATERIAIS/PRODUTOS. '
            . "REGRA CRÍTICA: O campo 'name' DEVE conter a DESCRIÇÃO DO MATERIAL (texto descritivo como 'CA 50 10,0 MMR PA'), "
            . "NUNCA o CÓDIGO do produto (como '10050ROL_PA'). Códigos vão no campo 'code'. "
            . 'NÃO extraia dados do cabeçalho (Ref. Pedido, Cliente, Endereço, OBS PED). '
            . 'Retorne JSON: {"materials": [{name (DESCRIÇÃO textual), code (CÓDIGO alfanumérico), description, specification, classification, unit, quantity, weight, unit_price, total_price}], '
            . '"totals": {subtotal, discount, freight, ipi, icms_st, grand_total}}. '
            . 'Valores monetários numéricos (1500.50). Se indisponível, null. APENAS JSON.';

        $messages = [
            ['role' => 'system', 'content' => 'Você é um assistente que analisa orçamentos de prestadores de serviço de construção civil. Extraia os materiais e valores. Retorne APENAS JSON válido.'],
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$content}"]]
            ]]
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 4000,
                'temperature' => 0.1,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            return ['error' => 'Erro na API: ' . ($err['error']['message'] ?? "HTTP {$httpCode}")];
        }

        $result = json_decode($response, true);
        $text = $result['choices'][0]['message']['content'] ?? '';

        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $parsed = json_decode(trim($text), true);

        if (!is_array($parsed)) return ['error' => 'Não foi possível interpretar o documento.'];

        if (isset($parsed['materials'])) {
            return ['success' => true, 'materials' => $parsed['materials'], 'totals' => $parsed['totals'] ?? null];
        }

        return ['success' => true, 'materials' => $parsed, 'totals' => null];
    }

    /**
     * Split Order — Dividir pedido em múltiplos pedidos por fornecedor/estoque
     */
    public function splitOrder(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::findFull($id);

        if (!$order) {
            $this->setFlash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/orders');
            return;
        }

        if ($order['status'] !== 'pending_approval') {
            $this->setFlash('error', 'Apenas pedidos aguardando aprovação podem ser divididos.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        $items = PurchaseOrderItem::getByOrder($id);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($id);
        $itemPrices = PurchaseOrderItemPrice::getByOrder($id);

        // Construir mapa de fornecedores
        $supplierMap = [];
        foreach ($orderSuppliers as $os) {
            $supplierMap[$os['supplier_id']] = $os;
        }

        // Construir mapa de preços por item/fornecedor
        $pricesByItemSupplier = [];
        foreach ($itemPrices as $ip) {
            $pricesByItemSupplier[$ip['item_id']][$ip['supplier_id']] = $ip;
        }

        // Receber a atribuição do usuário (item_id => supplier_id)
        $itemAssignments = $_POST['item_assignments'] ?? [];

        // Agrupar itens
        $splitGroups = [];

        // Itens de estoque (sempre ficam separados)
        $stockItems = array_filter($items, fn($i) => !empty($i['source_type']) && $i['source_type'] !== 'purchase');
        if (!empty($stockItems)) {
            $splitGroups['stock'] = [
                'supplier_id' => null,
                'supplier_data' => null,
                'items' => array_values($stockItems),
                'source' => 'stock',
            ];
        }

        // Itens de compra — agrupar conforme escolha do usuário
        $purchaseItems = array_filter($items, fn($i) => empty($i['source_type']) || $i['source_type'] === 'purchase');
        if (!empty($purchaseItems) && !empty($orderSuppliers)) {
            foreach ($purchaseItems as $pi) {
                // Verificar se o usuário atribuiu este item a um fornecedor
                $assignedSid = isset($itemAssignments[$pi['id']]) ? (int)$itemAssignments[$pi['id']] : null;

                if (!$assignedSid) {
                    // Fallback: usar menor preço
                    $bestSid = null;
                    $bestPrice = PHP_FLOAT_MAX;
                    if (isset($pricesByItemSupplier[$pi['id']])) {
                        foreach ($pricesByItemSupplier[$pi['id']] as $sid => $priceInfo) {
                            $up = (float)($priceInfo['unit_price'] ?? 0);
                            if ($up > 0 && $up < $bestPrice) {
                                $bestPrice = $up;
                                $bestSid = $sid;
                            }
                        }
                    }
                    $assignedSid = $bestSid ?: ($orderSuppliers[0]['supplier_id'] ?? null);
                }

                if ($assignedSid) {
                    $key = 'supplier_' . $assignedSid;
                    if (!isset($splitGroups[$key])) {
                        $splitGroups[$key] = [
                            'supplier_id' => $assignedSid,
                            'supplier_data' => $supplierMap[$assignedSid] ?? null,
                            'items' => [],
                            'source' => 'purchase',
                        ];
                    }
                    $splitGroups[$key]['items'][] = $pi;
                }
            }
        } elseif (!empty($purchaseItems)) {
            $splitGroups['purchase'] = [
                'supplier_id' => null,
                'supplier_data' => null,
                'items' => array_values($purchaseItems),
                'source' => 'purchase',
            ];
        }

        // Validar que há pelo menos 2 grupos para dividir
        if (count($splitGroups) < 2) {
            $this->setFlash('error', 'Este pedido não pode ser dividido — todos os itens pertencem ao mesmo grupo. Atribua itens a fornecedores diferentes.');
            $this->redirect('/admin/orders/show/' . $id);
            return;
        }

        // Criar novos pedidos
        $newOrderIds = [];
        $constructionSiteId = $order['construction_site_id'] ?? null;
        $hasConstructionSiteCol = (bool) Database::fetch(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1"
        );

        foreach ($splitGroups as $gKey => $group) {
            $code = PurchaseOrder::generateCode();
            $quoteToken = PurchaseOrder::generateToken();
            $approvalToken = PurchaseOrder::generateToken();

            $newOrderData = [
                'code' => $code,
                'order_type' => $order['order_type'] ?? 'material',
                'supplier_id' => $group['supplier_id'],
                'status' => 'pending_approval',
                'description' => ($order['description'] ? $order['description'] . ' ' : '') . '[Split de ' . $order['code'] . ']',
                'created_by' => $order['created_by'],
                'created_by_name' => $order['created_by_name'],
                'quote_token' => $quoteToken,
                'approval_token' => $approvalToken,
                'quoted_by_name' => $order['quoted_by_name'],
                'quoted_at' => $order['quoted_at'],
                'quote_notes' => $order['quote_notes'],
                'quote_started_at' => $order['quote_started_at'] ?? null,
                'quote_started_by' => $order['quote_started_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($hasConstructionSiteCol && $constructionSiteId) {
                $newOrderData['construction_site_id'] = $constructionSiteId;
            }

            $newOrderId = PurchaseOrder::create($newOrderData);
            $newOrderIds[] = ['id' => $newOrderId, 'code' => $code, 'group' => $gKey];

            // Copiar itens para o novo pedido e calcular total
            $groupTotal = 0;
            foreach ($group['items'] as $item) {
                // Para itens de compra, buscar preço do fornecedor atribuído
                $itemUnitPrice = $item['unit_price'] ?? null;
                $itemTotalPrice = $item['total_price'] ?? null;

                if ($group['source'] === 'purchase' && $group['supplier_id']) {
                    $priceRecord = $pricesByItemSupplier[$item['id']][$group['supplier_id']] ?? null;
                    if ($priceRecord) {
                        $itemUnitPrice = $priceRecord['unit_price'];
                        $itemTotalPrice = $priceRecord['total_price'];
                    }
                }

                $newItemData = [
                    'order_id' => $newOrderId,
                    'material_id' => $item['material_id'],
                    'material_name' => $item['material_name'],
                    'specification' => $item['specification'] ?? '',
                    'classification' => $item['classification'] ?? '',
                    'unit' => $item['unit'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $itemUnitPrice,
                    'total_price' => $itemTotalPrice,
                    'source_type' => $item['source_type'] ?? null,
                    'stock_from_site_id' => $item['stock_from_site_id'] ?? null,
                    'stock_movement_id' => $item['stock_movement_id'] ?? null,
                    'already_purchased' => $item['already_purchased'] ?? 0,
                    'already_purchased_qty' => $item['already_purchased_qty'] ?? null,
                    'already_purchased_price' => $item['already_purchased_price'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $newItemId = PurchaseOrderItem::create($newItemData);
                $groupTotal += (float)($itemTotalPrice ?? 0);

                // Copiar preços de TODOS os fornecedores para este item (mantém histórico de cotação)
                if ($group['source'] === 'purchase' && isset($pricesByItemSupplier[$item['id']])) {
                    foreach ($pricesByItemSupplier[$item['id']] as $sid => $priceInfo) {
                        PurchaseOrderItemPrice::create([
                            'order_id' => $newOrderId,
                            'item_id' => $newItemId,
                            'supplier_id' => $sid,
                            'unit_price' => $priceInfo['unit_price'],
                            'total_price' => $priceInfo['total_price'],
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                // Atualizar stock_movement para apontar para o novo pedido
                if (!empty($item['stock_movement_id'])) {
                    Database::update('stock_movements', ['order_id' => $newOrderId], 'id = ?', [$item['stock_movement_id']]);
                }
            }

            // Copiar fornecedor do pedido original para o novo (com total correto)
            if ($group['supplier_id'] && $group['supplier_data']) {
                $sd = $group['supplier_data'];
                PurchaseOrderSupplier::create([
                    'order_id' => $newOrderId,
                    'supplier_id' => $group['supplier_id'],
                    'status' => 'quoted',
                    'quoted_by_name' => $sd['quoted_by_name'] ?? $order['quoted_by_name'],
                    'quoted_at' => $sd['quoted_at'] ?? $order['quoted_at'],
                    'quote_notes' => $sd['quote_notes'] ?? null,
                    'total' => $groupTotal,
                    'subtotal_items' => $groupTotal,
                    'subtotal_final' => $groupTotal,
                    'discount_type' => $sd['discount_type'] ?? 'percent',
                    'discount_value' => $sd['discount_value'] ?? 0,
                    'surcharge_type' => $sd['surcharge_type'] ?? 'percent',
                    'surcharge_value' => $sd['surcharge_value'] ?? 0,
                    'ipi_percent' => $sd['ipi_percent'] ?? 0,
                    'icms_percent' => $sd['icms_percent'] ?? 0,
                    'freight' => $sd['freight'] ?? 0,
                    'vendor_name' => $sd['vendor_name'] ?? '',
                    'vendor_phone' => $sd['vendor_phone'] ?? '',
                    'vendor_email' => $sd['vendor_email'] ?? '',
                    'delivery_days' => $sd['delivery_days'] ?? null,
                    'payment_method' => $sd['payment_method'] ?? null,
                    'payment_condition' => $sd['payment_condition'] ?? null,
                    'payment_first_due' => $sd['payment_first_due'] ?? null,
                    'payment_notes' => $sd['payment_notes'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Atualizar total_estimated do novo pedido
            PurchaseOrder::updateById($newOrderId, [
                'total_estimated' => $groupTotal,
            ]);

            // Registrar histórico no novo pedido
            PurchaseOrderHistory::log(
                $newOrderId,
                'created',
                "Pedido criado via Split Order do pedido {$order['code']}.",
                Auth::user()['name']
            );
        }

        // Cancelar pedido original
        PurchaseOrder::updateById($id, [
            'status' => 'cancelled',
        ]);

        // Registrar histórico no pedido original
        $newCodes = array_map(fn($o) => $o['code'], $newOrderIds);
        PurchaseOrderHistory::log(
            $id,
            'cancelled',
            "Pedido cancelado via Split Order. Novos pedidos criados: " . implode(', ', $newCodes) . ".",
            Auth::user()['name']
        );

        // Enviar notificações de aprovação para os novos pedidos
        foreach ($newOrderIds as $newOrder) {
            $newOrderFull = PurchaseOrder::find($newOrder['id']);
            if ($newOrderFull && !empty($newOrderFull['approval_token'])) {
                try {
                    $this->sendApprovalNotifications($newOrder['id'], $newOrderFull['approval_token']);
                } catch (\Exception $e) {
                    // Não bloquear se notificação falhar
                }
            }
        }

        $this->setFlash('success', 'Pedido dividido com sucesso! ' . count($newOrderIds) . ' novo(s) pedido(s) criado(s): ' . implode(', ', $newCodes));
        $this->redirect('/admin/orders');
    }

    /**
     * Visualizar logs de debug das cotações
     */
    public function quoteLogs(): void
    {
        $orderId = (int) $this->input('order_id', 0);

        if ($orderId > 0) {
            $result = \App\Services\QuoteLogger::getLogByOrder($orderId);
            $logs = $result;
            $total = count($result);
        } else {
            $result = \App\Services\QuoteLogger::listLogs(50);
            $logs = $result['logs'] ?? [];
            $total = $result['total'] ?? 0;
        }

        $this->view('admin.orders.quote_logs', [
            'logs' => $logs,
            'total' => $total,
            'filterOrderId' => $orderId,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Visualizar um log específico de cotação (JSON completo)
     */
    public function quoteLogView(): void
    {
        $filename = $this->input('file', '');

        if (empty($filename)) {
            $this->redirect('/admin/orders/quote-logs');
            return;
        }

        $log = \App\Services\QuoteLogger::getLog($filename);

        if (!$log) {
            $this->setFlash('error', 'Log não encontrado.');
            $this->redirect('/admin/orders/quote-logs');
            return;
        }

        $this->view('admin.orders.quote_log_detail', [
            'log' => $log,
            'filename' => $filename,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }
}
