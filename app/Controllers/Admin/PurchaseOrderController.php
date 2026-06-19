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
use App\Models\MaterialPriceHistory;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\Setting;
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

        $this->view('admin.orders.create', [
            'suppliers' => $suppliers,
            'materials' => $materials,
            'categories' => $categories,
            'units' => $units,
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
        $items = $_POST['items'] ?? [];

        if (empty($items)) {
            $this->setFlash('error', 'Adicione pelo menos um item ao pedido.');
            $this->redirect('/admin/orders/create');
            return;
        }

        $code = PurchaseOrder::generateCode();
        $quoteToken = PurchaseOrder::generateToken();
        $approvalToken = PurchaseOrder::generateToken();

        $orderId = PurchaseOrder::create([
            'code' => $code,
            'supplier_id' => null,
            'status' => 'pending_quote',
            'description' => $description,
            'created_by' => Auth::id(),
            'created_by_name' => Auth::user()['name'],
            'quote_token' => $quoteToken,
            'approval_token' => $approvalToken,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Salvar itens
        foreach ($items as $item) {
            if (empty($item['material_name'])) continue;

            PurchaseOrderItem::create([
                'order_id' => $orderId,
                'material_id' => !empty($item['material_id']) ? (int) $item['material_id'] : null,
                'material_name' => $item['material_name'],
                'specification' => $item['specification'] ?? '',
                'classification' => $item['classification'] ?? '',
                'unit' => $item['unit'] ?? '',
                'quantity' => (float) ($item['quantity'] ?? 1),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Log no histórico
        PurchaseOrderHistory::log(
            $orderId,
            'created',
            'Pedido criado e enviado para cotação',
            Auth::user()['name'],
            Auth::id()
        );

        // Enviar notificações (e-mail + webhook) para cotação
        $this->sendQuoteNotifications($orderId, $quoteToken);

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

        $this->view('admin.orders.show', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'orderSuppliers' => $orderSuppliers,
            'itemPrices' => $itemPrices,
            'payments' => $payments,
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
     * Cancelar pedido
     */
    public function cancel(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        $id = (int) $this->input('id', 0);
        $order = PurchaseOrder::find($id);

        if (!$order || in_array($order['status'], ['approved', 'cancelled'])) {
            $this->setFlash('error', 'Pedido não encontrado ou não pode ser cancelado.');
            $this->redirect('/admin/orders');
            return;
        }

        PurchaseOrder::updateById($id, ['status' => 'cancelled']);
        PurchaseOrderHistory::log($id, 'cancelled', 'Pedido cancelado', Auth::user()['name'], Auth::id());

        $this->setFlash('success', 'Pedido cancelado com sucesso.');
        $this->redirect('/admin/orders');
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
            'orders_approval_emails',
            'orders_approval_webhook',
            'orders_approval_phone',
            'orders_approval_phone_name',
            'orders_completed_emails',
            'orders_completed_webhook',
            'orders_completed_phone',
            'orders_completed_phone_name',
            'orders_pin_code',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = trim($_POST[$key]);
            }
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
        
        $supplierName = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? 'Pendente');
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

        if (!empty($order['approved_by_name'])) {
            $xlsx->addRow(['Aprovado por:', $order['approved_by_name'], '', 'Data:', $order['approved_at'] ? date('d/m/Y H:i', strtotime($order['approved_at'])) : ''], 'bold');
        }

        $xlsx->addEmptyRow();

        // Header da tabela de itens
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

        // Subtotal e Total
        $xlsx->addRow(['', '', '', '', '', '', 'Insumos:', $subtotalInsumos], 'total');
        
        if ($order['total_estimated'] != $subtotalInsumos && $order['total_estimated'] > 0) {
            $xlsx->addRow(['', '', '', '', '', '', 'TOTAL:', $order['total_estimated']], 'total');
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
            $xlsx->addRow([
                $order['code'],
                date('d/m/Y', strtotime($order['created_at'])),
                $item['material_name'] . ($item['classification'] ? ' - ' . $item['classification'] : ''),
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
        
        if (!in_array($type, ['nf', 'boleto'])) {
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

        if ($materialId) {
            $records = MaterialPriceHistory::getByMaterial($materialId);
        } elseif ($supplierId) {
            $records = MaterialPriceHistory::getBySupplier($supplierId);
        } else {
            $records = MaterialPriceHistory::getAllGroupedByMaterial(500);
        }

        $materials = Material::allActive();
        $suppliers = Supplier::allActive();

        $this->view('admin.orders.price_history', [
            'records' => $records,
            'materials' => $materials,
            'suppliers' => $suppliers,
            'filterMaterial' => $materialId,
            'filterSupplier' => $supplierId,
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

        // E-mail (via fila)
        $emails = Setting::get('orders_quote_emails', '');
        if (!empty($emails)) {
            $subject = "Cotação Pendente - Pedido {$order['code']}";
            $body = EmailTemplate::purchaseOrderQuote($order, $items, $quoteUrl, $orderSuppliers);
            NotificationService::queueEmails($emails, $subject, $body);
        }

        // Webhook
        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty($webhookUrl)) {
            $itemsList = '';
            foreach ($items as $i => $item) {
                $itemsList .= ($i + 1) . ". {$item['material_name']}";
                if ($item['classification']) $itemsList .= " ({$item['classification']})";
                $itemsList .= " - Qtd: {$item['quantity']} {$item['unit']}\n";
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
                . "*Solicitado por:* {$order['created_by_name']}\n"
                . "*Data:* " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n"
                . "*Itens:* " . count($items) . "\n\n"
                . $suppliersList
                . "*Lista de materiais:*\n{$itemsList}\n"
                . (!empty($order['description']) ? "*Obs:* {$order['description']}\n\n" : "\n")
                . "*Link para informar cotação:*\n{$quoteUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'quote_requested',
                'order_code' => $order['code'],
                'suppliers' => $suppliersArray,
                'items_count' => count($items),
                'quote_url' => $quoteUrl,
                'created_by' => $order['created_by_name'],
                'created_at' => $order['created_at'],
                'description' => $order['description'],
                'phone' => Setting::get('orders_quote_phone', ''),
                'phone_name' => Setting::get('orders_quote_phone_name', ''),
                'message' => $message,
            ]);
        }
    }

    private function sendApprovalNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$token}";

        // E-mail
        $emails = Setting::get('orders_approval_emails', '');
        if (!empty($emails)) {
            $subject = "Aprovação Pendente - Pedido {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
            $body = EmailTemplate::purchaseOrderApproval($order, $items, $approvalUrl, $orderSuppliers);
            NotificationService::queueEmails($emails, $subject, $body);
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
                'quoted_at' => $order['quoted_at'],
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
        $orderSuppliers = PurchaseOrderSupplier::getByOrder($orderId);
        $approvedSupplier = PurchaseOrderSupplier::getApproved($orderId);
        $baseUrl = $this->getBaseUrl();
        $viewUrl = "{$baseUrl}/pedido/pdf/{$orderId}";

        // E-mail
        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
            $body = EmailTemplate::purchaseOrderCompleted($order, $items, $viewUrl);
            NotificationService::queueEmails($emails, $subject, $body);
        }

        // Webhook
        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $totalFormatted = 'R$ ' . number_format($order['total_estimated'], 2, ',', '.');
            $approvedSupplierName = $approvedSupplier ? $approvedSupplier['supplier_name'] : ($order['supplier_name'] ?? 'N/A');

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
                . "*Fornecedor aprovado:* {$approvedSupplierName}\n"
                . "*Valor Total:* {$totalFormatted}\n"
                . "*Aprovado por:* {$order['approved_by_name']}\n"
                . "*Data:* " . date('d/m/Y H:i', strtotime($order['approved_at'])) . "\n"
                . $suppliersComparison . "\n"
                . "*PDF do pedido:*\n{$viewUrl}";

            $this->sendWebhook($webhookUrl, [
                'event' => 'order_approved',
                'order_code' => $order['code'],
                'approved_supplier' => $approvedSupplierName,
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

    private function sendWebhook(string $url, array $data): void
    {
        NotificationService::queueWebhook($url, $data);
        // Tentar processar imediatamente em background
        NotificationService::processImmediate();
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        return $scheme . '://' . $host;
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
}
