<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;

class ProjectController extends Controller
{
    public function index(): void
    {
        $projects = Database::fetchAll("SELECT * FROM projects WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.projects.index', [
            'projects' => $projects,
            'settings' => $siteSettings,
        ]);
    }

    public function show(string $slug = ''): void
    {
        if (empty($slug)) {
            $this->redirect('/projetos');
            return;
        }

        $project = Database::fetch("SELECT * FROM projects WHERE slug = ? AND active = 1", [$slug]);

        if (!$project) {
            $this->redirect('/projetos');
            return;
        }

        $images = Database::fetchAll("SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC", [$project['id']]);
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.projects.show', [
            'project' => $project,
            'images' => $images,
            'settings' => $siteSettings,
        ]);
    }
}
