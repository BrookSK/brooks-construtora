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

        // Extrair texto do PDF ou usar imagem
        $content = '';
        if ($file['type'] === 'application/pdf') {
            // Ler conteúdo bruto para enviar como base64
            $content = base64_encode(file_get_contents($file['tmp_name']));
            $mediaType = 'application/pdf';
        } else {
            $content = base64_encode(file_get_contents($file['tmp_name']));
            $mediaType = $file['type'];
        }

        // Chamar OpenAI para analisar
        try {
            $openaiKey = Setting::get('openai_api_key', '');
            $model = Setting::get('openai_model', 'gpt-4o');

            if (empty($openaiKey)) {
                $this->json(['error' => 'Chave API OpenAI não configurada.'], 400);
                return;
            }

            $messages = [
                ['role' => 'system', 'content' => 'Você é um assistente que analisa documentos de listagem de materiais de construção. Extraia todos os materiais listados e retorne APENAS um JSON array. Cada item deve ter: name (nome do material), specification (tipo/especificação como "mat. Hidraulica", "mat. Civil", "madeira", etc), classification (medida como "100mm", "3/4", "50x40", etc), unit (unidade de medida como "unid", "mts", "m²", "kg", etc), quantity (quantidade numérica). Se não conseguir identificar algum campo, use string vazia. Retorne APENAS o JSON, sem markdown, sem explicação.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => 'Analise este documento e extraia a lista de materiais com quantidades:'],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$content}"]]
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
                    'Authorization: Bearer ' . $openaiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                $this->json(['error' => 'Erro na API OpenAI (HTTP ' . $httpCode . ')'], 500);
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
    public function export(): void
    {
        $orders = PurchaseOrder::allWithSupplier();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pedidos_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // BOM para UTF-8 no Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Header
        fputcsv($output, ['ID Pedido', 'Data Solicitação', 'Item/Serviço', 'Fornecedor', 'Valor Orçado', 'Valor Final', 'Solicitado Por', 'Status', 'Data Aprovação', 'Observações'], ';');
        
        foreach ($orders as $order) {
            $items = PurchaseOrderItem::getByOrder($order['id']);
            $statusLabels = [
                'draft' => 'Rascunho', 'pending_quote' => 'Aguard. Cotação',
                'quoted' => 'Cotado', 'pending_approval' => 'Aguard. Aprovação',
                'approved' => 'Aprovado', 'rejected' => 'Rejeitado', 'cancelled' => 'Cancelado',
            ];
            
            foreach ($items as $item) {
                fputcsv($output, [
                    $order['code'],
                    date('d/m/Y', strtotime($order['created_at'])),
                    $item['material_name'] . ($item['classification'] ? ' - ' . $item['classification'] : ''),
                    $order['supplier_name'] ?? '',
                    $item['unit_price'] ? number_format($item['unit_price'], 2, ',', '.') : '',
                    $item['total_price'] ? number_format($item['total_price'], 2, ',', '.') : '',
                    $order['created_by_name'] ?? '',
                    $statusLabels[$order['status']] ?? $order['status'],
                    $order['approved_at'] ? date('d/m/Y', strtotime($order['approved_at'])) : '',
                    $order['description'] ?? '',
                ], ';');
            }
        }
        
        fclose($output);
        exit;
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
}
