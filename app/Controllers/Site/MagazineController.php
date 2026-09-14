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
        // A listagem pública mostra apenas revistas publicadas.
        // Revistas em teste são acessadas somente pelo link direto (com token).
        try {
            $magazines = \App\Core\Database::fetchAll(
                "SELECT m.*, mt.title as topic_title 
                 FROM magazines m 
                 LEFT JOIN magazine_topics mt ON m.topic_id = mt.id 
                 WHERE m.status = 'published' 
                 ORDER BY m.published_at DESC"
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

    /**
     * Verifica se a revista pode ser exibida para o visitante atual.
     * Publicada = pública. Teste = só logado ou com token de preview válido.
     */
    private function canView(?array $magazine, int $id): bool
    {
        if (!$magazine) return false;
        // Publicada = acesso público
        if ($magazine['status'] === Magazine::STATUS_PUBLISHED) return true;
        // Não publicada (ex.: aprovada em modo teste): libera com token de preview
        // válido no link, ou para usuários logados (admin/PIN).
        $previewToken = $_GET['preview'] ?? '';
        if (Magazine::isValidPreviewToken($id, $previewToken)) return true;
        if ($this->isLoggedIn()) return true;
        return false;
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

        if (!$this->canView($magazine, $id)) {
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

    /**
     * Preview isolado (renderiza a revista sem CSS do site - usado em iframe)
     */
    public function preview(string $id = ''): void
    {
        $id = (int) $id;

        try {
            $magazine = Magazine::find($id);
        } catch (\Exception $e) {
            http_response_code(404);
            echo 'Revista não encontrada.';
            return;
        }

        if (!$this->canView($magazine, $id)) {
            http_response_code(404);
            echo 'Revista não encontrada.';
            return;
        }

        $pages = Magazine::getPages($id);

        // Força modo site (sem toolbar, fundo claro)
        $isAdmin = false;

        // Renderiza o mesmo template do admin preview (isolado, sem CSS do site)
        include ROOT_PATH . '/app/Views/admin/magazines/preview.php';
    }

    /**
     * Abre a revista e dispara o download do PDF automaticamente.
     * Usado pelo botão "Baixar PDF" do e-mail quando o PDF não foi anexado.
     */
    public function pdf(string $id = ''): void
    {
        $id = (int) $id;

        try {
            $magazine = Magazine::find($id);
        } catch (\Exception $e) {
            $this->redirect('/revista');
            return;
        }

        if (!$this->canView($magazine, $id)) {
            $this->redirect('/revista');
            return;
        }

        $pages = Magazine::getPages($id);

        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        // Flag lida pela view para disparar o download automático do PDF
        $autoDownloadPdf = true;

        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/magazine/show.php';
        } else {
            include ROOT_PATH . '/app/Views/site/magazine/new-show.php';
        }
    }
}
