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

    public function index(): void
    {
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

    public function show(string $id = ''): void
    {
        $id = (int) $id;

        try {
            $magazine = Magazine::find($id);
        } catch (\Exception $e) {
            $this->redirect('/revista');
            return;
        }

        if (!$magazine || $magazine['status'] !== 'published') {
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
     * Renderiza o preview puro da revista (embed standalone)
     */
    public function embed(string $id = ''): void
    {
        $id = (int) $id;

        try {
            $magazine = Magazine::find($id);
        } catch (\Exception $e) {
            http_response_code(404);
            echo 'Revista não encontrada.';
            return;
        }

        if (!$magazine || $magazine['status'] !== 'published') {
            http_response_code(404);
            echo 'Revista não encontrada.';
            return;
        }

        $pages = Magazine::getPages($id);
        $siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
        $year = date('Y');
        try { $magazineLogo = Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
        if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
        $_renderContext = [
            'showAdminToolbar' => false,
        ];

        ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($magazine['title']) ?> — Revista Brooks</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" />
</head>
<body style="margin:0;padding:0;font-family:'Inter',sans-serif;background:#333;">
<?php include ROOT_PATH . '/app/Views/shared/_magazine_render.php'; ?>
</body>
</html><?php
    }
}
