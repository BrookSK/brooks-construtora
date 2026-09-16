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
        // A listagem pública mostra apenas revistas PUBLICADAS.
        // Exceção: quando há um usuário logado (admin/PIN), as revistas em
        // modo TESTE também aparecem — assim dá para conferir como a listagem
        // e a revista aparecerão para o cliente ANTES de publicar de verdade.
        // Para o público (não logado), revistas em teste continuam invisíveis.
        $isStaff = $this->isLoggedIn();
        try {
            if ($isStaff) {
                $magazines = \App\Core\Database::fetchAll(
                    "SELECT m.*, mt.title as topic_title
                     FROM magazines m
                     LEFT JOIN magazine_topics mt ON m.topic_id = mt.id
                     WHERE m.status IN ('published', 'test')
                     ORDER BY (m.status = 'test') DESC, COALESCE(m.published_at, m.created_at) DESC"
                );
            } else {
                $magazines = \App\Core\Database::fetchAll(
                    "SELECT m.*, mt.title as topic_title
                     FROM magazines m
                     LEFT JOIN magazine_topics mt ON m.topic_id = mt.id
                     WHERE m.status = 'published'
                     ORDER BY m.published_at DESC"
                );
            }
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

        // Se existe um PDF gerado (via Browserless), o leitor vê o PDF num
        // visualizador — idêntico em qualquer dispositivo (iPhone/iPad/Safari/
        // Android/PC). É a forma definitiva que elimina a renderização por
        // navegador. Sem PDF, cai no HTML (fallback).
        $pdfUrl = \App\Services\BrowserlessPdfService::existingPdfUrl($id);
        if ($pdfUrl) {
            // Preserva o token de preview no link do PDF (modo teste, sem login).
            $previewToken = $_GET['preview'] ?? '';
            include ROOT_PATH . '/app/Views/site/magazine/pdf_viewer.php';
            return;
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

        // Se há um PDF real gerado (Browserless), entrega ele diretamente —
        // é o arquivo fiel, idêntico em qualquer dispositivo.
        $pdfUrl = \App\Services\BrowserlessPdfService::existingPdfUrl($id);
        if ($pdfUrl) {
            $this->redirect($pdfUrl);
            return;
        }

        $pages = Magazine::getPages($id);

        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        // Fallback (sem PDF gerado): exibe a revista em HTML.
        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/magazine/show.php';
        } else {
            include ROOT_PATH . '/app/Views/site/magazine/new-show.php';
        }
    }
}
