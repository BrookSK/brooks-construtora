<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderHistory;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\Setting;
use App\Services\MailService;
use App\Services\EmailTemplate;

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

        $supplierId = (int) $this->input('supplier_id', 0);
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
            'supplier_id' => $supplierId ?: null,
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

        $this->view('admin.orders.show', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
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
            'orders_approval_emails',
            'orders_approval_webhook',
            'orders_completed_emails',
            'orders_completed_webhook',
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

    // ===============================
    // MÉTODOS PRIVADOS
    // ===============================

    private function sendQuoteNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $quoteUrl = "{$baseUrl}/pedido/cotacao/{$token}";

        // E-mail
        $emails = Setting::get('orders_quote_emails', '');
        if (!empty($emails)) {
            try {
                $mailService = new MailService();
                $subject = "Cotação Pendente - Pedido {$order['code']}";
                $body = EmailTemplate::purchaseOrderQuote($order, $items, $quoteUrl);
                
                $emailList = array_map('trim', explode(',', $emails));
                foreach ($emailList as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mailService->send($email, $subject, $body, true);
                    }
                }
            } catch (\Exception $e) {
                // Log error silenciosamente
                error_log("Erro ao enviar e-mail de cotação: " . $e->getMessage());
            }
        }

        // Webhook
        $webhookUrl = Setting::get('orders_quote_webhook', '');
        if (!empty($webhookUrl)) {
            $this->sendWebhook($webhookUrl, [
                'event' => 'quote_requested',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'items_count' => count($items),
                'quote_url' => $quoteUrl,
                'created_by' => $order['created_by_name'],
                'created_at' => $order['created_at'],
                'description' => $order['description'],
            ]);
        }
    }

    private function sendApprovalNotifications(int $orderId, string $token): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $approvalUrl = "{$baseUrl}/pedido/aprovacao/{$token}";

        // E-mail
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

        // Webhook
        $webhookUrl = Setting::get('orders_approval_webhook', '');
        if (!empty($webhookUrl)) {
            $this->sendWebhook($webhookUrl, [
                'event' => 'approval_requested',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'items_count' => count($items),
                'approval_url' => $approvalUrl,
                'quoted_by' => $order['quoted_by_name'],
                'quoted_at' => $order['quoted_at'],
            ]);
        }
    }

    private function sendCompletedNotifications(int $orderId): void
    {
        $order = PurchaseOrder::findFull($orderId);
        $items = PurchaseOrderItem::getByOrder($orderId);
        $baseUrl = $this->getBaseUrl();
        $viewUrl = "{$baseUrl}/admin/orders/show/{$orderId}";

        // E-mail
        $emails = Setting::get('orders_completed_emails', '');
        if (!empty($emails)) {
            try {
                $mailService = new MailService();
                $subject = "Pedido Aprovado - {$order['code']} - R$ " . number_format($order['total_estimated'], 2, ',', '.');
                $body = EmailTemplate::purchaseOrderCompleted($order, $items, $viewUrl);
                
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

        // Webhook
        $webhookUrl = Setting::get('orders_completed_webhook', '');
        if (!empty($webhookUrl)) {
            $this->sendWebhook($webhookUrl, [
                'event' => 'order_approved',
                'order_code' => $order['code'],
                'supplier' => $order['supplier_name'] ?? 'N/A',
                'total' => $order['total_estimated'],
                'approved_by' => $order['approved_by_name'],
                'approved_at' => $order['approved_at'],
                'view_url' => $viewUrl,
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
}
