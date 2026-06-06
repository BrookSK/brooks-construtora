<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Magazine;
use App\Models\Setting;

class MagazineController extends Controller
{
    public function index(): void
    {
        $magazines = Magazine::getPublished();
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.magazine.index', [
            'magazines' => $magazines,
            'settings' => $siteSettings,
        ]);
    }

    public function show(string $id = ''): void
    {
        $id = (int) $id;
        $magazine = Magazine::find($id);

        if (!$magazine || $magazine['status'] !== 'published') {
            $this->redirect('/revista');
            return;
        }

        $pages = Magazine::getPages($id);
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.magazine.show', [
            'magazine' => $magazine,
            'pages' => $pages,
            'settings' => $siteSettings,
        ]);
    }
}
