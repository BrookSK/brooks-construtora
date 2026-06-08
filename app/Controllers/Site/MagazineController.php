<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Magazine;
use App\Models\Setting;

class MagazineController extends Controller
{
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

        include ROOT_PATH . '/app/Views/site/magazine/index.php';
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

        include ROOT_PATH . '/app/Views/site/magazine/show.php';
    }
}
