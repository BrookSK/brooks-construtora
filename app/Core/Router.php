<?php

namespace App\Core;

class Router
{
    private string $url;
    private string $controller;
    private string $method;
    private array $params = [];

    public function __construct()
    {
        $this->url = $this->parseUrl();
    }

    private function parseUrl(): string
    {
        $url = $_GET['url'] ?? '';
        $url = rtrim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return $url;
    }

    public function dispatch(): void
    {
        $segments = $this->url ? explode('/', $this->url) : [];

        // Verifica se é rota administrativa
        if (!empty($segments[0]) && $segments[0] === 'admin') {
            $this->handleAdminRoute($segments);
            return;
        }

        // Rotas do site antigo (institucional preservado em /antigo)
        if (!empty($segments[0]) && $segments[0] === 'antigo') {
            array_shift($segments); // Remove 'antigo' do início
            $this->handleAntigoRoute($segments);
            return;
        }

        // Rotas do site institucional
        $this->handleSiteRoute($segments);
    }

    private function handleAdminRoute(array $segments): void
    {
        // Remove 'admin' do início
        array_shift($segments);

        // Mapeia as rotas do admin
        $routes = [
            '' => ['DashboardController', 'index'],
            'login' => ['AuthController', 'login'],
            'login/authenticate' => ['AuthController', 'authenticate'],
            'logout' => ['AuthController', 'logout'],
            'dashboard' => ['DashboardController', 'index'],
            'settings' => ['SettingsController', 'index'],
            'settings/update' => ['SettingsController', 'update'],
            'settings/upload-magazine-logo' => ['SettingsController', 'uploadMagazineLogo'],
            'settings/remove-magazine-logo' => ['SettingsController', 'removeMagazineLogo'],
            'newsletter' => ['NewsletterController', 'index'],
            'newsletter/export' => ['NewsletterController', 'export'],
            'newsletter/delete' => ['NewsletterController', 'delete'],
            'newsletter/resend-whatsapp' => ['NewsletterController', 'resendWhatsapp'],
            'newsletter/resend-whatsapp-all' => ['NewsletterController', 'resendWhatsappAll'],
            'newsletter/generate-tokens' => ['NewsletterController', 'generateTokens'],
            'users' => ['UserController', 'index'],
            'users/create' => ['UserController', 'create'],
            'users/store' => ['UserController', 'store'],
            'users/edit' => ['UserController', 'edit'],
            'users/update' => ['UserController', 'update'],
            'users/delete' => ['UserController', 'delete'],
            'magazines' => ['MagazineController', 'index'],
            'magazines/generate' => ['MagazineController', 'generate'],
            'magazines/topics' => ['MagazineController', 'topics'],
            'magazines/generate-topics' => ['MagazineController', 'generateTopics'],
            'magazines/add-topic' => ['MagazineController', 'addTopic'],
            'magazines/create-manual' => ['MagazineController', 'createManual'],
            'magazines/store-manual' => ['MagazineController', 'storeManual'],
            'magazines/sources' => ['MagazineController', 'updateSources'],
            'magazines/edit' => ['MagazineController', 'edit'],
            'magazines/update' => ['MagazineController', 'update'],
            'magazines/upload-cover' => ['MagazineController', 'uploadCover'],
            'magazines/upload-image' => ['MagazineController', 'uploadImage'],
            'magazines/delete-page-image' => ['MagazineController', 'deletePageImage'],
            'magazines/add-page' => ['MagazineController', 'addPage'],
            'magazines/generate-image' => ['MagazineController', 'generatePageImage'],
            'magazines/image-proxy' => ['MagazineController', 'imageProxy'],
            'magazines/pending-images' => ['MagazineController', 'pendingImages'],
            'magazines/generate-single-image' => ['MagazineController', 'generateSingleImage'],
            'magazines/job-status' => ['MagazineController', 'jobStatus'],
            'magazines/active-job' => ['MagazineController', 'activeJob'],
            'magazines/approve' => ['MagazineController', 'approve'],
            'magazines/publish' => ['MagazineController', 'publish'],
            'magazines/preview' => ['MagazineController', 'preview'],
            'magazines/delete' => ['MagazineController', 'delete'],
            'magazines/schedule' => ['MagazineController', 'schedule'],
            'magazines/schedule/update' => ['MagazineController', 'updateSchedule'],
            'magazines/add-guest-column' => ['MagazineController', 'addGuestColumn'],

            // Pedidos de materiais
            'orders' => ['PurchaseOrderController', 'index'],
            'orders/create' => ['PurchaseOrderController', 'create'],
            'orders/store' => ['PurchaseOrderController', 'store'],
            'orders/parse-pdf' => ['PurchaseOrderController', 'parsePdf'],
            'orders/parse-service-pdf' => ['PurchaseOrderController', 'parseServicePdf'],
            'orders/save-service-materials' => ['PurchaseOrderController', 'saveServiceMaterials'],
            'orders/show' => ['PurchaseOrderController', 'show'],
            'orders/resend-quote' => ['PurchaseOrderController', 'resendQuote'],
            'orders/resend-approval' => ['PurchaseOrderController', 'resendApproval'],
            'orders/reopen-approval' => ['PurchaseOrderController', 'reopenApproval'],
            'orders/cancel' => ['PurchaseOrderController', 'cancel'],
            'orders/delete' => ['PurchaseOrderController', 'delete'],
            'orders/financial-review' => ['PurchaseOrderController', 'financialReview'],
            'orders/financial-unreview' => ['PurchaseOrderController', 'financialUnreview'],
            'orders/financial-edit' => ['PurchaseOrderController', 'financialEdit'],
            'orders/financial-update' => ['PurchaseOrderController', 'financialUpdate'],
            'orders/clear-price-history' => ['PurchaseOrderController', 'clearPriceHistory'],
            'orders/settings' => ['PurchaseOrderController', 'settings'],
            'orders/settings/update' => ['PurchaseOrderController', 'updateSettings'],
            'orders/test-webhook' => ['PurchaseOrderController', 'testWebhook'],
            'orders/price-history' => ['PurchaseOrderController', 'priceHistory'],
            'orders/export' => ['PurchaseOrderController', 'export'],
            'orders/upload-payment' => ['PurchaseOrderController', 'uploadPayment'],
            'orders/validate-payment-cnpj' => ['PurchaseOrderController', 'validatePaymentCnpj'],
            'orders/mark-paid' => ['PurchaseOrderController', 'markPaid'],
            'orders/delete-payment' => ['PurchaseOrderController', 'deletePayment'],
            'orders/payments' => ['PurchaseOrderController', 'payments'],
            'orders/delivery-init' => ['PurchaseOrderController', 'deliveryInit'],
            'orders/delivery-update' => ['PurchaseOrderController', 'deliveryUpdate'],
            'orders/delivery-expected-date' => ['PurchaseOrderController', 'deliveryExpectedDate'],
            'orders/delivery-data' => ['PurchaseOrderController', 'deliveryData'],
            'orders/tracking' => ['PurchaseOrderController', 'tracking'],
            'orders/spare-items' => ['PurchaseOrderController', 'spareItems'],
            'orders/spare-items/add' => ['PurchaseOrderController', 'spareItemAdd'],
            'orders/spare-items/delete' => ['PurchaseOrderController', 'spareItemDelete'],
            'orders/resend-notification' => ['PurchaseOrderController', 'resendNotification'],
            'orders/resend-all-phase' => ['PurchaseOrderController', 'resendAllPhase'],
            'orders/generate-invite' => ['PurchaseOrderController', 'generateInvite'],
            'orders/pin-users' => ['PurchaseOrderController', 'pinUsers'],
            'orders/delete-invite' => ['PurchaseOrderController', 'deleteInvite'],
            'orders/delete-pin-user' => ['PurchaseOrderController', 'deletePinUser'],
            'orders/update-pin-user' => ['PurchaseOrderController', 'updatePinUser'],
            'orders/update-pin-user-phone' => ['PurchaseOrderController', 'updatePinUserPhone'],
            'orders/update-pin-user-email' => ['PurchaseOrderController', 'updatePinUserEmail'],
            'orders/send-invite-webhook' => ['PurchaseOrderController', 'sendInviteWebhook'],
            'orders/edit-items' => ['PurchaseOrderController', 'editItems'],
            'orders/update-items' => ['PurchaseOrderController', 'updateItems'],

            // Obras
            'obras' => ['ConstructionSiteController', 'index'],
            'obras/create' => ['ConstructionSiteController', 'create'],
            'obras/store' => ['ConstructionSiteController', 'store'],
            'obras/edit' => ['ConstructionSiteController', 'edit'],
            'obras/update' => ['ConstructionSiteController', 'update'],
            'obras/delete' => ['ConstructionSiteController', 'delete'],
            'obras/search' => ['ConstructionSiteController', 'search'],

            // Fornecedores
            'suppliers' => ['SupplierController', 'index'],
            'suppliers/create' => ['SupplierController', 'create'],
            'suppliers/store' => ['SupplierController', 'store'],
            'suppliers/edit' => ['SupplierController', 'edit'],
            'suppliers/update' => ['SupplierController', 'update'],
            'suppliers/delete' => ['SupplierController', 'delete'],
            'suppliers/search' => ['SupplierController', 'search'],
            'suppliers/quick-store' => ['SupplierController', 'quickStore'],

            // Materiais
            'materials' => ['MaterialController', 'index'],
            'materials/store' => ['MaterialController', 'store'],
            'materials/update' => ['MaterialController', 'update'],
            'materials/delete' => ['MaterialController', 'delete'],
            'materials/search' => ['MaterialController', 'search'],
            'materials/quick-store' => ['MaterialController', 'quickStore'],
            'materials/categories' => ['MaterialController', 'categories'],
            'materials/units' => ['MaterialController', 'units'],
            'materials/quick-store-category' => ['MaterialController', 'quickStoreCategory'],
            'materials/quick-store-unit' => ['MaterialController', 'quickStoreUnit'],
            'materials/import' => ['MaterialController', 'import'],
            'materials/import-process' => ['MaterialController', 'importProcess'],

            // Fornecedores - Contatos/Vendedores
            'suppliers/contacts' => ['SupplierController', 'contacts'],
            'suppliers/store-contact' => ['SupplierController', 'storeContact'],
            'suppliers/update-contact' => ['SupplierController', 'updateContact'],
            'suppliers/delete-contact' => ['SupplierController', 'deleteContact'],
            'suppliers/get-contacts' => ['SupplierController', 'getContacts'],
            'suppliers/import-contacts' => ['SupplierController', 'importContacts'],

            // Estoque
            'stock' => ['StockController', 'index'],
            'stock/locations' => ['StockController', 'locations'],
            'stock/store-location' => ['StockController', 'storeLocation'],
            'stock/update-location' => ['StockController', 'updateLocation'],
            'stock/delete-location' => ['StockController', 'deleteLocation'],
            'stock/create' => ['StockController', 'create'],
            'stock/store' => ['StockController', 'store'],
            'stock/edit' => ['StockController', 'edit'],
            'stock/update' => ['StockController', 'update'],
            'stock/delete' => ['StockController', 'delete'],
            'stock/transfer' => ['StockController', 'transfer'],
            'stock/process-transfer' => ['StockController', 'processTransfer'],
            'stock/movements' => ['StockController', 'movements'],
            'stock/check-stock' => ['StockController', 'checkStock'],
            'stock/search-stock' => ['StockController', 'searchStock'],
            'stock/bulk-create' => ['StockController', 'bulkCreate'],
            'stock/bulk-store' => ['StockController', 'bulkStore'],

            // Transporte (Wilton)
            'transport' => ['TransportController', 'index'],
            'transport/in-transit' => ['TransportController', 'markInTransit'],
            'transport/delivered' => ['TransportController', 'markDelivered'],
            'transport/bulk-in-transit' => ['TransportController', 'bulkInTransit'],
            'transport/bulk-delivered' => ['TransportController', 'bulkDelivered'],
            'transport/detail' => ['TransportController', 'detail'],
            'transport/orders' => ['TransportController', 'orders'],
        ];

        $path = implode('/', $segments);

        // Verifica se a rota existe diretamente
        if (isset($routes[$path])) {
            $controllerName = 'App\\Controllers\\Admin\\' . $routes[$path][0];
            $methodName = $routes[$path][1];
        } else {
            // Tenta encontrar a rota com parâmetro
            $found = false;
            for ($i = count($segments); $i > 0; $i--) {
                $testPath = implode('/', array_slice($segments, 0, $i));
                if (isset($routes[$testPath])) {
                    $controllerName = 'App\\Controllers\\Admin\\' . $routes[$testPath][0];
                    $methodName = $routes[$testPath][1];
                    $this->params = array_slice($segments, $i);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->show404();
                return;
            }
        }

        $this->executeController($controllerName, $methodName);
    }

    /**
     * Rotas do site institucional antigo (preservado em /antigo para histórico)
     */
    private function handleAntigoRoute(array $segments): void
    {
        // Redirect /antigo/projetos/slug → /antigo/projeto/slug
        if (count($segments) >= 2 && $segments[0] === 'projetos') {
            header('Location: /antigo/projeto/' . $segments[1], true, 301);
            exit;
        }

        $routes = [
            '' => ['HomeController', 'index'],
            'sobre' => ['HomeController', 'sobre'],
            'projetos' => ['ProjectController', 'index'],
            'projeto' => ['ProjectController', 'show'],
            'contato' => ['HomeController', 'contato'],
            'contato/enviar' => ['HomeController', 'enviarContato'],
            'newsletter/subscribe' => ['NewsletterController', 'subscribe'],
            'newsletter/unsubscribe' => ['NewsletterController', 'unsubscribe'],
            'newsletter/atualizar' => ['NewsletterController', 'updatePhone'],
            'newsletter/check-email' => ['NewsletterController', 'checkEmail'],
            'revista' => ['MagazineController', 'index'],
            'revista/ver' => ['MagazineController', 'show'],
            'revista/image-proxy' => ['MagazineController', 'imageProxy'],
        ];

        $path = implode('/', $segments);

        if (isset($routes[$path])) {
            $controllerName = 'App\\Controllers\\Site\\' . $routes[$path][0];
            $methodName = $routes[$path][1];
        } else {
            // Tenta encontrar com parâmetro
            $found = false;
            for ($i = count($segments); $i > 0; $i--) {
                $testPath = implode('/', array_slice($segments, 0, $i));
                if (isset($routes[$testPath])) {
                    $controllerName = 'App\\Controllers\\Site\\' . $routes[$testPath][0];
                    $methodName = $routes[$testPath][1];
                    $this->params = array_slice($segments, $i);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->show404();
                return;
            }
        }

        // Define constante para que views saibam que estão no contexto "antigo"
        if (!defined('ANTIGO_PREFIX')) {
            define('ANTIGO_PREFIX', '/antigo');
        }

        $this->executeController($controllerName, $methodName);
    }

    private function handleSiteRoute(array $segments): void
    {
        // Redirect /projetos/slug → /projeto/slug
        if (count($segments) >= 2 && $segments[0] === 'projetos') {
            header('Location: /projeto/' . $segments[1], true, 301);
            exit;
        }

        $routes = [
            '' => ['HomeController', 'index'],
            'sobre' => ['HomeController', 'sobre'],
            'projetos' => ['ProjectController', 'index'],
            'projeto' => ['ProjectController', 'show'],
            'contato' => ['HomeController', 'contato'],
            'contato/enviar' => ['HomeController', 'enviarContato'],
            'vetriks' => ['HomeController', 'vetriks'],
            'forca-estrutural' => ['HomeController', 'forcaEstrutural'],
            'academy' => ['HomeController', 'academy'],
            'matterport' => ['HomeController', 'matterport'],
            'cultura' => ['HomeController', 'cultura'],
            'trabalhe-conosco' => ['HomeController', 'trabalheConosco'],
            'trabalhe-conosco/enviar' => ['HomeController', 'trabalheConoscoStore'],
            'politica-privacidade' => ['HomeController', 'politicaPrivacidade'],
            'termos' => ['HomeController', 'termos'],
            'newsletter/subscribe' => ['NewsletterController', 'subscribe'],
            'newsletter/unsubscribe' => ['NewsletterController', 'unsubscribe'],
            'newsletter/atualizar' => ['NewsletterController', 'updatePhone'],
            'newsletter/check-email' => ['NewsletterController', 'checkEmail'],
            'revista' => ['MagazineController', 'index'],
            'revista/ver' => ['MagazineController', 'show'],
            'revista/image-proxy' => ['MagazineController', 'imageProxy'],

            // Pedidos - Links públicos
            'pedido/cotacao' => ['PurchaseOrderController', 'quote'],
            'pedido/cotacao/enviar' => ['PurchaseOrderController', 'submitQuote'],
            'pedido/cotacao/iniciar' => ['PurchaseOrderController', 'startQuote'],
            'pedido/cotacao/cancelar' => ['PurchaseOrderController', 'cancelQuote'],
            'pedido/cotacao/novo-fornecedor' => ['PurchaseOrderController', 'quickStoreSupplier'],
            'pedido/cotacao/parse-service-pdf' => ['PurchaseOrderController', 'parseServicePdfPublic'],
            'pedido/cotacao/save-service-materials' => ['PurchaseOrderController', 'saveServiceMaterialsPublic'],
            'pedido/aprovacao' => ['PurchaseOrderController', 'approval'],
            'pedido/aprovacao/enviar' => ['PurchaseOrderController', 'submitApproval'],
            'pedido/pdf' => ['PurchaseOrderController', 'pdf'],
            'pedido/xlsx' => ['PurchaseOrderController', 'xlsx'],
            'pedido/entrega' => ['PurchaseOrderController', 'deliveryPublic'],
            'pedido/entrega/update' => ['PurchaseOrderController', 'deliveryPublicUpdate'],
            'pedido/entrega/data' => ['PurchaseOrderController', 'deliveryPublicData'],
            'pedido/aprovacao/comentario' => ['PurchaseOrderController', 'approvalComment'],
            'pedido/cotacao/comentario' => ['PurchaseOrderController', 'quoteComment'],
            'pedido/cotacao/send-to-supplier' => ['PurchaseOrderController', 'sendToSupplier'],
            'pedido/cotacao/parse-ai-quote' => ['PurchaseOrderController', 'parseAiQuote'],
            'pedido/cotacao/get-contacts' => ['PurchaseOrderController', 'getSupplierContacts'],

            // PIN Auth
            'pin/login' => ['PinAuthController', 'login'],
            'pin/authenticate' => ['PinAuthController', 'authenticate'],
            'pin/cadastro' => ['PinAuthController', 'register'],
            'pin/store' => ['PinAuthController', 'store'],
            'pin/recover' => ['PinAuthController', 'recover'],
            'pin/logout' => ['PinAuthController', 'logout'],
            'pin/minha-conta' => ['PinAuthController', 'myAccount'],
            'pin/minha-conta/salvar' => ['PinAuthController', 'updateAccount'],

            // Controle de EPIs (protegido por PIN individual)
            'cadastro-de-epi' => ['EpiController', 'catalog'],
            'cadastro-de-epi/salvar' => ['EpiController', 'catalogStore'],
            'cadastro-de-epi/atualizar' => ['EpiController', 'catalogUpdate'],
            'cadastro-de-epi/excluir' => ['EpiController', 'catalogDelete'],
            'registro-de-entrega' => ['EpiController', 'deliveryForm'],
            'registro-de-entrega/salvar' => ['EpiController', 'deliveryStore'],
            'registro-de-entrega/buscar-colaborador' => ['EpiController', 'searchWorkers'],
            'substituicao-de-epi' => ['EpiController', 'replacementForm'],
            'substituicao-de-epi/itens' => ['EpiController', 'replacementItems'],
            'substituicao-de-epi/salvar' => ['EpiController', 'replacementStore'],
            'substituicao-de-epi/devolver' => ['EpiController', 'returnStore'],
            'distribuicao-terceiros' => ['EpiController', 'thirdPartyForm'],
            'distribuicao-terceiros/salvar' => ['EpiController', 'thirdPartyStore'],
            'distribuicao-terceiros/buscar' => ['EpiController', 'searchWorkers'],
            'historico-de-epi' => ['EpiController', 'history'],

            // Checklist de Limpeza
            'checklist-limpeza' => ['CleaningChecklistController', 'index'],
            'checklist-limpeza/novo' => ['CleaningChecklistController', 'create'],
            'checklist-limpeza/salvar' => ['CleaningChecklistController', 'store'],
            'checklist-limpeza/ver' => ['CleaningChecklistController', 'show'],

            // Lista de Presença
            'lista-de-presenca' => ['PresenceController', 'index'],
            'lista-de-presenca/buscar-prestador' => ['PresenceController', 'searchProviders'],
            'lista-de-presenca/salvar-prestador' => ['PresenceController', 'storeProvider'],
            'lista-de-presenca/buscar-obra' => ['PresenceController', 'searchSites'],
            'lista-de-presenca/salvar-obra' => ['PresenceController', 'storeSite'],
            'lista-de-presenca/salvar' => ['PresenceController', 'store'],
            'historico-presenca' => ['PresenceController', 'history'],

            // Painel de pedidos com PIN
            'pedidos' => ['PurchaseOrderController', 'pinPanel'],
            'pedidos/login' => ['PurchaseOrderController', 'pinLogin'],
            'pedidos/auth' => ['PurchaseOrderController', 'pinAuth'],
            'pedidos/logout' => ['PurchaseOrderController', 'pinLogout'],
        ];

        $path = implode('/', $segments);

        if (isset($routes[$path])) {
            $controllerName = 'App\\Controllers\\Site\\' . $routes[$path][0];
            $methodName = $routes[$path][1];
        } else {
            // Tenta encontrar com parâmetro
            $found = false;
            for ($i = count($segments); $i > 0; $i--) {
                $testPath = implode('/', array_slice($segments, 0, $i));
                if (isset($routes[$testPath])) {
                    $controllerName = 'App\\Controllers\\Site\\' . $routes[$testPath][0];
                    $methodName = $routes[$testPath][1];
                    $this->params = array_slice($segments, $i);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $this->show404();
                return;
            }
        }

        $this->executeController($controllerName, $methodName);
    }

    private function executeController(string $controllerName, string $methodName): void
    {
        if (!class_exists($controllerName)) {
            $this->show404();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            $this->show404();
            return;
        }

        call_user_func_array([$controller, $methodName], $this->params);
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
