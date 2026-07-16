<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;

class ProjectController extends Controller
{
    // Projetos com páginas estáticas (HTML do WordPress)
    private array $staticProjects = [
        'projeto-katty-kaitazoff-alphaville',
        'construcao-completa-de-mansao-no-alphaville',
        'projeto-milena-niemeyer-moema',
        'projeto-rocha-andrade',
        'projeto-norah-carneiro',
        'projeto-joia-bergamo-2',
        'projeto-joia-bergamo-rsvp',
        'construcao-corporativa-cafeteria-do-palacio-dos-bandeirantes',
        'construcao-corporativa-de-escritorio-no-itaim-bibi',
    ];

    public function index(): void
    {
        try {
            $siteSettings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $siteSettings = [];
        }

        $settings = $siteSettings;
        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/projects/index.php';
        } else {
            include ROOT_PATH . '/app/Views/site/projects/new-index.php';
        }
    }

    public function show(string $slug = ''): void
    {
        if (empty($slug)) {
            $this->redirect('/projetos');
            return;
        }

        try {
            $siteSettings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $siteSettings = [];
        }

        // Verifica se existe uma view estática para este projeto
        if (in_array($slug, $this->staticProjects)) {
            // No novo site, usa template genérico com galeria
            if (!defined('ANTIGO_PREFIX')) {
                $settings = $siteSettings;
                $projectSlug = $slug;
                include ROOT_PATH . '/app/Views/site/projects/new-show-static.php';
                return;
            }
            // Site antigo - view estática original
            $viewFile = ROOT_PATH . '/app/Views/site/projects/' . $slug . '.php';
            if (file_exists($viewFile)) {
                $settings = $siteSettings;
                include $viewFile;
                return;
            }
        }

        // Fallback: busca no banco de dados
        try {
            $project = Database::fetch("SELECT * FROM projects WHERE slug = ? AND active = 1", [$slug]);

            if (!$project) {
                $this->redirect('/projetos');
                return;
            }

            $images = Database::fetchAll("SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC", [$project['id']]);

            if (defined('ANTIGO_PREFIX')) {
                $this->view('site.projects.show', [
                    'project' => $project,
                    'images' => $images,
                    'settings' => $siteSettings,
                ]);
            } else {
                $settings = $siteSettings;
                include ROOT_PATH . '/app/Views/site/projects/new-show-db.php';
            }
        } catch (\Exception $e) {
            $this->redirect('/projetos');
        }
    }
}
