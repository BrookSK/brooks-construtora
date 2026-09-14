<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Magazine;
use App\Models\Setting;

class MagazineController extends Controller
{
    /**
     * Proxy para servir imagens sem CORS (usado pelo PDF export)
     */
    public function imageProxy(): void
    {
        $url = $_GET['url'] ?? '';
        if (empty($url)) { http_response_code(400); exit; }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) { http_response_code(400); exit; }

        $localPath = ROOT_PATH . '/public' . $path;
        if (!file_exists($localPath)) { http_response_code(404); exit; }

        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $mimeTypes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
        $mime = $mimeTypes[$ext] ?? 'image/jpeg';

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Access-Control-Allow-Origin: *');
        readfile($localPath);
        exit;
    }

    /**
     * Detecta se há um usuário logado (PIN do site ou admin do painel).
     * Usado para liberar revistas em modo de teste.
     */
    private function isLoggedIn(): bool
    {
        // Usuário logado no painel admin
        if (\App\Core\Auth::check()) return true;
        // Usuário logado por PIN no site
        if (\App\Controllers\Site\PinAuthController::getLoggedUser()) return true;
        return false;
    }

    public function index(): void
    {
        // Em modo teste, revistas 'test' aparecem só para usuários logados
        $allowedStatuses = $this->isLoggedIn() ? ['published', 'test'] : ['published'];
        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));

        try {
            $magazines = \App\Core\Database::fetchAll(
                "SELECT m.*, mt.title as topic_title 
                 FROM magazines m 
                 LEFT JOIN magazine_topics mt ON m.topic_id = mt.id 
                 WHERE m.status IN ({$placeholders}) 
                 ORDER BY m.published_at DESC",
                $allowedStatuses
            );
        } catch (\Exception $e) {
            $magazines = [];
        }

        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/magazine/index.php';
        } else {
            include ROOT_PATH . '/app/Views/site/magazine/new-index.php';
        }
    }

    public function show(string $id = ''): void
    {
        $id = (int) $id;

        try {
            $magazine = Magazine::find($id);
        } catch (\Exception $e) {
            $this->redirect('/revista');
            return;
        }

        // Revistas publicadas são públicas. Revistas em modo teste só abrem
        // para usuários logados (PIN do site ou admin).
        $isTest = $magazine && $magazine['status'] === Magazine::STATUS_TEST;
        $isPublished = $magazine && $magazine['status'] === Magazine::STATUS_PUBLISHED;

        if (!$magazine || (!$isPublished && !($isTest && $this->isLoggedIn()))) {
            $this->redirect('/revista');
            return;
        }

        $pages = Magazine::getPages($id);

        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/magazine/show.php';
        } else {
            include ROOT_PATH . '/app/Views/site/magazine/new-show.php';
        }
    }
}
