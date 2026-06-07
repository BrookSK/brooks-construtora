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
            'magazines/edit' => ['MagazineController', 'edit'],
            'magazines/update' => ['MagazineController', 'update'],
            'magazines/upload-cover' => ['MagazineController', 'uploadCover'],
            'magazines/upload-image' => ['MagazineController', 'uploadImage'],
            'magazines/generate-image' => ['MagazineController', 'generatePageImage'],
            'magazines/approve' => ['MagazineController', 'approve'],
            'magazines/publish' => ['MagazineController', 'publish'],
            'magazines/preview' => ['MagazineController', 'preview'],
            'magazines/delete' => ['MagazineController', 'delete'],
            'magazines/schedule' => ['MagazineController', 'schedule'],
            'magazines/schedule/update' => ['MagazineController', 'updateSchedule'],
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
            'newsletter/subscribe' => ['NewsletterController', 'subscribe'],
            'revista' => ['MagazineController', 'index'],
            'revista/ver' => ['MagazineController', 'show'],
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
