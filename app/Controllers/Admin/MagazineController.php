<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Magazine;
use App\Models\MagazineTopic;
use App\Models\Setting;
use App\Services\OpenAIService;
use App\Services\MailService;

class MagazineController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('magazines')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        // Busca revistas com o tema associado
        $magazines = Database::fetchAll(
            "SELECT m.*, mt.title as topic_title, mt.description as topic_description 
             FROM magazines m 
             LEFT JOIN magazine_topics mt ON m.topic_id = mt.id 
             ORDER BY m.created_at DESC"
        );

        $this->view('admin.magazines.index', [
            'magazines' => $magazines,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function topics(): void
    {
        $topics = MagazineTopic::all('created_at DESC');

        $this->view('admin.magazines.topics', [
            'topics' => $topics,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function generateTopics(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines/topics');
            return;
        }

        $quantity = (int) $this->input('quantity', 10);
        $customPrompt = trim($this->input('custom_prompt', ''));
        $sourceUrls = trim($this->input('source_urls', ''));

        // Salva prompt complementar como configuração para próxima vez
        if ($customPrompt) {
            Setting::set('magazine_custom_prompt', $customPrompt);
        }

        try {
            $openai = new OpenAIService();
            $topics = $openai->generateTopics($quantity, $customPrompt, $sourceUrls);

            foreach ($topics as $topic) {
                MagazineTopic::create([
                    'title' => $topic['title'],
                    'description' => $topic['description'],
                    'source_urls' => $sourceUrls ?: null,
                    'custom_prompt' => $customPrompt ?: null,
                    'created_by' => 'ai',
                    'used' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->setFlash('success', "{$quantity} temas gerados com sucesso!");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao gerar temas: ' . $e->getMessage());
        }

        $this->redirect('/admin/magazines/topics');
    }

    /**
     * Adicionar tema manualmente
     */
    public function addTopic(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/magazines/topics'); return; }

        $title = trim($this->input('title', ''));
        if (empty($title)) {
            $this->setFlash('error', 'Título é obrigatório.');
            $this->redirect('/admin/magazines/topics');
            return;
        }

        MagazineTopic::create([
            'title' => $title,
            'description' => trim($this->input('description', '')),
            'source_urls' => trim($this->input('source_urls', '')) ?: null,
            'created_by' => 'manual',
            'used' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Tema adicionado com sucesso!');
        $this->redirect('/admin/magazines/topics');
    }

    /**
     * Tela para criar revista manualmente (do zero)
     */
    public function createManual(): void
    {
        $this->view('admin.magazines.create_manual', [
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar revista criada manualmente
     */
    public function storeManual(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/magazines/topics'); return; }

        $title = trim($this->input('title', ''));
        $subtitle = trim($this->input('subtitle', ''));

        if (empty($title)) {
            $this->setFlash('error', 'Título é obrigatório.');
            $this->redirect('/admin/magazines/create-manual');
            return;
        }

        // Cria a revista em modo draft
        $defaultCover = \App\Models\Setting::get('magazine_default_cover', null);
        $magazineId = Magazine::create([
            'title' => $title,
            'subtitle' => $subtitle,
            'topic_id' => null,
            'status' => Magazine::STATUS_GENERATED,
            'cover_image' => $defaultCover,
            'generated_by' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Cria as 11 páginas padrão em branco (inclui coluna do convidado)
        $layouts = ['cover', 'subcover', 'guest_column', 'internal_01', 'internal_02', 'internal_03', 'internal_04', 'internal_05', 'internal_06', 'internal_07', 'backcover'];
        foreach ($layouts as $i => $layout) {
            $pageData = [
                'page_number' => $i + 1,
                'title' => '',
                'subtitle' => '',
                'content' => '',
                'layout_type' => $layout,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            // Preenche defaults para cover/subcover/backcover/guest_column
            if ($layout === 'cover') { $pageData['title'] = 'ALICERCE'; $pageData['subtitle'] = 'CONSTRUÇÃO — ALTO PADRÃO'; }
            if ($layout === 'subcover') { $pageData['title'] = 'ECO BROOKS'; $pageData['subtitle'] = 'CONSTRUÇÃO — SUSTENTÁVEL'; }
            if ($layout === 'guest_column') { $pageData['caption'] = 'Coluna do Convidado'; }
            if ($layout === 'backcover') { $pageData['content'] = 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.'; }
            
            Magazine::addPage($magazineId, $pageData);
        }

        // Grava fontes coladas em massa (uma por linha, "Título | URL" opcional)
        $this->storeBulkSources($magazineId, $this->input('sources_bulk', ''));

        $this->setFlash('success', 'Revista criada! Preencha o conteúdo das páginas.');
        $this->redirect('/admin/magazines/edit/' . $magazineId);
    }

    /**
     * Parseia um texto de fontes coladas (uma por linha) e grava em magazine_sources.
     * Formato aceito por linha: "Título" ou "Título | https://url".
     * Também extrai uma URL solta no fim da linha quando não há separador "|".
     */
    private function storeBulkSources(int $magazineId, string $bulk): void
    {
        $bulk = trim($bulk);
        if ($bulk === '') {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', $bulk);
        $order = 0;

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') continue;

            $title = $line;
            $url = null;

            if (strpos($line, '|') !== false) {
                $parts = explode('|', $line, 2);
                $title = trim($parts[0]);
                $url = trim($parts[1] ?? '') ?: null;
            } elseif (preg_match('/\s(https?:\/\/\S+)$/i', $line, $m, PREG_OFFSET_CAPTURE)) {
                $url = trim($m[1][0]);
                $title = trim(substr($line, 0, $m[1][1]));
            }

            if ($title === '') continue;

            Database::insert('magazine_sources', [
                'magazine_id' => $magazineId,
                'title' => $title,
                'url' => $url,
                'author' => null,
                'accessed_at' => null,
                'sort_order' => $order,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $order++;
        }
    }

    /**
     * Estrutura uma lista de fontes coladas usando IA (separa autor/título,
     * sugere URL oficial e data de acesso). Retorna JSON sem gravar no banco —
     * o frontend preenche os campos e o usuário salva depois.
     */
    public function enrichSources(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $bulk = trim($this->input('sources_text', ''));
        if ($bulk === '') {
            $this->json(['error' => 'Nenhuma fonte informada.'], 400);
            return;
        }

        try {
            $openai = new OpenAIService();
            $sources = $openai->enrichSources($bulk);

            $this->json([
                'success' => true,
                'total' => count($sources),
                'sources' => $sources,
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao processar fontes com IA: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualizar fontes de uma revista (AJAX)
     */
    public function updateSources(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'POST only'], 405); return; }

        $magazineId = (int) $this->input('magazine_id');
        $sources = $_POST['sources'] ?? [];

        // Remove fontes existentes
        Database::delete('magazine_sources', 'magazine_id = ?', [$magazineId]);

        // Insere novas
        foreach ($sources as $i => $src) {
            $title = trim($src['title'] ?? '');
            if (empty($title)) continue;
            Database::insert('magazine_sources', [
                'magazine_id' => $magazineId,
                'title' => $title,
                'url' => trim($src['url'] ?? '') ?: null,
                'author' => trim($src['author'] ?? '') ?: null,
                'accessed_at' => !empty($src['accessed_at']) ? $src['accessed_at'] : null,
                'sort_order' => $i,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->json(['success' => true]);
    }

    /**
     * Inicia a geração de revista em background (dispara processo no servidor)
     */
    public function generate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $topicId = (int) $this->input('topic_id');
        $topic = MagazineTopic::find($topicId);

        if (!$topic) {
            $this->json(['error' => 'Tema não encontrado.'], 404);
            return;
        }

        // Verifica se já tem um job em andamento
        $activeJob = Database::fetch(
            "SELECT * FROM generation_jobs WHERE status IN ('pending', 'processing') ORDER BY created_at DESC LIMIT 1"
        );

        if ($activeJob) {
            $this->json(['error' => 'Já existe uma geração em andamento. Aguarde a conclusão.'], 409);
            return;
        }

        try {
            // Cria o job no banco
            $jobId = Database::insert('generation_jobs', [
                'type' => 'magazine_full',
                'status' => 'pending',
                'total_steps' => 0,
                'current_step' => 0,
                'current_step_label' => 'Aguardando início...',
                'started_by' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $userId = Auth::id();

            // IMPORTANTE: Fecha a sessão ANTES de enviar a resposta
            // Isso permite que requests de polling funcionem em paralelo
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Envia a resposta JSON ao navegador imediatamente
            $response = json_encode([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Geração iniciada em segundo plano!',
            ]);

            // Limpa qualquer output buffering acumulado
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            http_response_code(200);
            header('Content-Type: application/json');
            header('Content-Length: ' . strlen($response));
            header('Connection: close');
            echo $response;
            flush();

            // Se PHP-FPM, desconecta completamente o request
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // Continua executando sem timeout
            set_time_limit(0);
            ignore_user_abort(true);

            // Executa a geração no mesmo processo
            $this->executeBackgroundGeneration($jobId, $topicId, $userId);
            exit;

        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao iniciar geração: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retorna o status de um job específico (polling endpoint)
     */
    public function jobStatus(): void
    {
        $jobId = (int) $this->input('job_id');

        if (!$jobId) {
            $job = Database::fetch(
                "SELECT * FROM generation_jobs WHERE status IN ('pending', 'processing') ORDER BY created_at DESC LIMIT 1"
            );
        } else {
            $job = Database::fetch("SELECT * FROM generation_jobs WHERE id = ?", [$jobId]);
        }

        if (!$job) {
            $this->json(['active' => false]);
            return;
        }

        $this->json([
            'active' => in_array($job['status'], ['pending', 'processing']),
            'job_id' => (int) $job['id'],
            'magazine_id' => $job['magazine_id'] ? (int) $job['magazine_id'] : null,
            'status' => $job['status'],
            'total_steps' => (int) $job['total_steps'],
            'current_step' => (int) $job['current_step'],
            'current_step_label' => $job['current_step_label'],
            'error_message' => $job['error_message'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
        ]);
    }

    /**
     * Retorna se existe algum job ativo (para o indicador global no layout)
     */
    public function activeJob(): void
    {
        $job = Database::fetch(
            "SELECT * FROM generation_jobs WHERE status IN ('pending', 'processing') ORDER BY created_at DESC LIMIT 1"
        );

        // Detectar job travado: se está em processing há mais de 15 minutos desde o início, marca como falho
        if ($job) {
            $startedAt = $job['started_at'] ?? $job['created_at'];
            $elapsed = time() - strtotime($startedAt);
            $maxDuration = 15 * 60; // 15 minutos

            if ($elapsed > $maxDuration) {
                Database::update('generation_jobs', [
                    'status' => 'failed',
                    'error_message' => 'Processo encerrado por timeout (mais de 10 minutos sem resposta). Tente gerar novamente.',
                    'completed_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$job['id']]);

                // Trata como job recente falho
                $this->json([
                    'active' => false,
                    'recent' => true,
                    'job_id' => (int) $job['id'],
                    'magazine_id' => $job['magazine_id'] ? (int) $job['magazine_id'] : null,
                    'status' => 'failed',
                    'current_step_label' => $job['current_step_label'],
                    'error_message' => 'Processo encerrado por timeout. Tente gerar novamente.',
                ]);
                return;
            }
        }

        if (!$job) {
            // Retorna o último job completado nos últimos 30 segundos (para o frontend perceber a conclusão)
            $recent = Database::fetch(
                "SELECT * FROM generation_jobs WHERE status IN ('completed', 'failed') AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND) ORDER BY completed_at DESC LIMIT 1"
            );

            if ($recent) {
                $this->json([
                    'active' => false,
                    'recent' => true,
                    'job_id' => (int) $recent['id'],
                    'magazine_id' => $recent['magazine_id'] ? (int) $recent['magazine_id'] : null,
                    'status' => $recent['status'],
                    'current_step_label' => $recent['current_step_label'],
                    'error_message' => $recent['error_message'],
                ]);
                return;
            }

            $this->json(['active' => false, 'recent' => false]);
            return;
        }

        $this->json([
            'active' => true,
            'job_id' => (int) $job['id'],
            'magazine_id' => $job['magazine_id'] ? (int) $job['magazine_id'] : null,
            'status' => $job['status'],
            'total_steps' => (int) $job['total_steps'],
            'current_step' => (int) $job['current_step'],
            'current_step_label' => $job['current_step_label'],
        ]);
    }

    /**
     * Retorna a lista de imagens pendentes para gerar de uma revista (usado pelo botão Regenerar)
     */
    public function pendingImages(): void
    {
        $magazineId = (int) $this->input('magazine_id');
        $magazine = Magazine::find($magazineId);

        if (!$magazine) {
            $this->json(['error' => 'Revista não encontrada.'], 404);
            return;
        }

        $pages = Magazine::getPages($magazineId);
        $pending = [];

        foreach ($pages as $page) {
            if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column', 'construction_stories'])) {
                continue;
            }

            $suggestion = $page['image_suggestion'] ?? null;
            $suggestion2 = $page['image_suggestion_2'] ?? null;
            $oneImageLayouts = ['internal_04', 'internal_07'];
            $threeImageLayouts = ['internal_05', 'internal_06'];

            // Descrição de fallback quando a página não tem image_suggestion salvo
            // (ex.: revistas criadas manualmente). Usa título > conteúdo > título da revista.
            $fallbackParts = array_filter([
                trim($page['title'] ?? ''),
                trim($page['subtitle'] ?? ''),
            ]);
            $fallbackDesc = trim(implode(' — ', $fallbackParts));
            if ($fallbackDesc === '') {
                $content = trim(strip_tags($page['content'] ?? ''));
                $fallbackDesc = $content !== '' ? mb_substr($content, 0, 200) : '';
            }
            if ($fallbackDesc === '') {
                $fallbackDesc = $magazine['title'] ?: 'Construção de alto padrão';
            }

            // Imagem 1
            $desc1 = $suggestion ?: $fallbackDesc;
            if ($desc1 && empty($page['image_url'])) {
                $pending[] = [
                    'page_id' => $page['id'],
                    'page_number' => $page['page_number'],
                    'field' => 'image_url',
                    'description' => $desc1,
                    'layout_type' => $page['layout_type'],
                ];
            }

            // Imagem 2 — layouts com 2 ou 3 imagens
            if (!in_array($page['layout_type'], $oneImageLayouts) && empty($page['image_url_2'])) {
                $desc2 = $suggestion2 ?: $fallbackDesc;
                if ($desc2) {
                    $pending[] = [
                        'page_id' => $page['id'],
                        'page_number' => $page['page_number'],
                        'field' => 'image_url_2',
                        'description' => $desc2,
                        'layout_type' => $page['layout_type'],
                    ];
                }
            }

            // Imagem 3 — para layouts com 3 imagens (internal_05, internal_06)
            if (in_array($page['layout_type'], $threeImageLayouts) && empty($page['image_url_3'] ?? null)) {
                $desc3 = $suggestion2 ?: $suggestion ?: $fallbackDesc;
                if ($desc3) {
                    $pending[] = [
                        'page_id' => $page['id'],
                        'page_number' => $page['page_number'],
                        'field' => 'image_url_3',
                        'description' => $desc3 . ' (ângulo alternativo)',
                        'layout_type' => $page['layout_type'],
                    ];
                }
            }
        }

        $this->json([
            'success' => true,
            'magazine_id' => $magazineId,
            'title' => $magazine['title'],
            'total' => count($pending),
            'images' => $pending,
        ]);
    }

    /**
     * Gera UMA imagem específica (chamado pelo frontend para regenerar individualmente)
     */
    public function generateSingleImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $pageId = (int) $this->input('page_id');
        $field = $this->input('field', 'image_url');
        $description = $this->input('description', '');

        $allowedFields = ['image_url', 'image_url_2', 'image_url_3'];
        if (!in_array($field, $allowedFields)) {
            $field = 'image_url';
        }

        $page = Database::fetch("SELECT * FROM magazine_pages WHERE id = ?", [$pageId]);

        if (!$page) {
            $this->json(['error' => 'Página não encontrada.'], 404);
            return;
        }

        if (empty($description)) {
            $description = $page['title'] ?? 'Construção de alto padrão';
        }

        $layout = $page['layout_type'] ?? '';
        $orientation = 'landscape';

        if (in_array($layout, ['internal_02', 'internal_07'])) {
            if ($field === 'image_url') $orientation = 'portrait';
        }
        if ($layout === 'internal_05' || $layout === 'internal_06') {
            $orientation = 'portrait';
        }
        if ($layout === 'internal_01' && $field === 'image_url_2') {
            $orientation = 'portrait';
        }

        try {
            $openai = new OpenAIService();
            $imageUrl = $openai->generateImage($description, $orientation);

            if ($imageUrl) {
                Magazine::updatePage($pageId, [$field => $imageUrl]);
                $this->json([
                    'success' => true,
                    'url' => $imageUrl,
                    'page_id' => $pageId,
                    'field' => $field,
                ]);
            } else {
                $this->json(['error' => 'Não foi possível gerar a imagem.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    public function edit(string $id = ''): void
    {
        if (!Auth::hasPermission('magazines.edit')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) ($id ?: $this->input('id'));
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        $pages = Magazine::getPages($id);

        $this->view('admin.magazines.edit', [
            'magazine' => $magazine,
            'pages' => $pages,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.edit')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        // Atualiza dados gerais
        $generalData = [
            'title' => $this->input('title'),
            'subtitle' => $this->input('subtitle'),
        ];
        // Nome da revista (notificações) — só grava se a coluna existir.
        Magazine::ensureMagazineNameColumn();
        if (Magazine::hasMagazineNameColumn()) {
            $generalData['magazine_name'] = trim((string) $this->input('magazine_name')) ?: null;
        }
        Magazine::updateById($id, $generalData);

        // Atualiza páginas
        if (isset($_POST['pages'])) {
            foreach ($_POST['pages'] as $pageId => $pageData) {
                // Monta subtitle a partir dos campos separados (cover/subcover)
                $subtitle = $pageData['subtitle'] ?? '';
                if (isset($pageData['subtitle_left']) || isset($pageData['subtitle_right'])) {
                    $left = trim($pageData['subtitle_left'] ?? '');
                    $right = trim($pageData['subtitle_right'] ?? '');
                    $subtitle = $left . ($right ? ' — ' . $right : '');
                }

                // Para guest_column e layouts sem checkbox, manter show_images = '1'
                $currentPage = Database::fetch("SELECT layout_type FROM magazine_pages WHERE id = ?", [(int) $pageId]);
                $layoutType = $currentPage['layout_type'] ?? '';
                $keepImagesVisible = in_array($layoutType, ['guest_column', 'cover', 'subcover', 'backcover', 'construction_stories']);
                $showImagesValue = $keepImagesVisible ? '1' : (isset($pageData['show_images']) ? '1' : '0');

                // Monta content dos causos a partir dos campos individuais
                $contentToSave = $pageData['content'] ?? '';
                if ($layoutType === 'construction_stories') {
                    $storyTitles = $_POST['stories_title_' . $pageId] ?? [];
                    $storyTexts = $_POST['stories_text_' . $pageId] ?? [];
                    $stories = [];
                    for ($si = 0; $si < count($storyTitles); $si++) {
                        $sTitle = trim($storyTitles[$si] ?? '');
                        $sText = trim($storyTexts[$si] ?? '');
                        if (empty($sTitle) && empty($sText)) continue;
                        $stories[] = $sTitle . "\n" . $sText;
                    }
                    $contentToSave = implode('|||', $stories);
                }

                Magazine::updatePage((int) $pageId, [
                    'title' => $pageData['title'] ?? '',
                    'subtitle' => $subtitle,
                    'content' => $contentToSave,
                    'caption' => $pageData['caption'] ?? '',
                    'image_caption' => $pageData['image_caption'] ?? '',
                    'show_images' => $showImagesValue,
                ]);

                // Processa uploads de imagens da página
                $imageFields = [
                    'guest_photo_' . $pageId => 'image_url',
                    'page_image_' . $pageId . '_1' => 'image_url',
                    'page_image_' . $pageId . '_2' => 'image_url_2',
                    'page_image_' . $pageId . '_3' => 'image_url_3',
                ];

                $uploadDir = ROOT_PATH . '/public/uploads/magazines/pages/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($imageFields as $fileKey => $dbField) {
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$fileKey];
                        if (in_array($file['type'], $allowedTypes)) {
                            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $filename = 'magazine_page_' . $pageId . '_' . $dbField . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                                Magazine::updatePage((int) $pageId, [$dbField => '/uploads/magazines/pages/' . $filename]);
                            }
                        }
                    }
                }
            }
        }

        // Atualiza fontes
        if (isset($_POST['sources'])) {
            Database::delete('magazine_sources', 'magazine_id = ?', [$id]);
            foreach ($_POST['sources'] as $i => $src) {
                $title = trim($src['title'] ?? '');
                if (empty($title)) continue;
                Database::insert('magazine_sources', [
                    'magazine_id' => $id,
                    'title' => $title,
                    'url' => trim($src['url'] ?? '') ?: null,
                    'author' => trim($src['author'] ?? '') ?: null,
                    'accessed_at' => !empty($src['accessed_at']) ? $src['accessed_at'] : null,
                    'sort_order' => $i,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->setFlash('success', 'Revista atualizada com sucesso!');
        $this->redirect('/admin/magazines/edit/' . $id);
    }

    public function uploadCover(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->json(['error' => 'Revista não encontrada.'], 404);
            return;
        }

        if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file = $_FILES['cover'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['error' => 'Tipo de arquivo não permitido.'], 400);
            return;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'magazine_cover_' . $id . '_' . time() . '.' . $ext;
        $uploadDir = ROOT_PATH . '/public/uploads/magazines/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Remove capa antiga se existir
            if ($magazine['cover_image'] && file_exists(ROOT_PATH . '/public' . $magazine['cover_image'])) {
                unlink(ROOT_PATH . '/public' . $magazine['cover_image']);
            }

            $coverUrl = '/uploads/magazines/' . $filename;
            Magazine::updateById($id, ['cover_image' => $coverUrl]);

            $this->json(['success' => true, 'url' => $coverUrl]);
        } else {
            $this->json(['error' => 'Erro ao salvar o arquivo.'], 500);
        }
    }

    /**
     * Adicionar nova página à revista (AJAX)
     */
    public function addPage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $magazineId = (int) $this->input('magazine_id', 0);
        $layoutType = $this->input('layout_type', 'internal_01');

        $magazine = Magazine::find($magazineId);
        if (!$magazine) {
            $this->json(['error' => 'Revista não encontrada.'], 404);
            return;
        }

        // Pegar o número da última página antes da backcover
        $pages = Magazine::getPages($magazineId);
        $backcoverPage = null;
        $maxPageNum = 0;

        foreach ($pages as $p) {
            if ($p['layout_type'] === 'backcover') {
                $backcoverPage = $p;
            }
            if ($p['page_number'] > $maxPageNum) {
                $maxPageNum = $p['page_number'];
            }
        }

        // Inserir nova página antes da backcover
        $newPageNum = $backcoverPage ? $backcoverPage['page_number'] : $maxPageNum + 1;

        // Mover backcover pra frente
        if ($backcoverPage) {
            Database::update('magazine_pages', ['page_number' => $newPageNum + 1], 'id = ?', [$backcoverPage['id']]);
        }

        // Criar nova página
        $pageId = Database::insert('magazine_pages', [
            'magazine_id' => $magazineId,
            'page_number' => $newPageNum,
            'layout_type' => $layoutType,
            'title' => '',
            'subtitle' => '',
            'content' => '',
            'image_suggestion' => '',
            'image_suggestion_2' => '',
        ]);

        $this->json(['success' => true, 'page_id' => $pageId, 'page_number' => $newPageNum]);
    }

    /**
     * Deletar uma página inteira (AJAX)
     */
    public function deletePage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $pageId = (int) $this->input('page_id', 0);
        $magazineId = (int) $this->input('magazine_id', 0);

        if (!$pageId) {
            $this->json(['error' => 'Página inválida.'], 400);
            return;
        }

        // Busca a página pra validar que existe e não é estrutural
        $page = Database::fetch("SELECT * FROM magazine_pages WHERE id = ?", [$pageId]);
        if (!$page) {
            $this->json(['error' => 'Página não encontrada.'], 404);
            return;
        }

        // Não permite excluir capa, subcapa ou contracapa
        if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])) {
            $this->json(['error' => 'Esta página não pode ser excluída.'], 403);
            return;
        }

        // Remove arquivos de imagem associados
        foreach (['image_url', 'image_url_2', 'image_url_3'] as $field) {
            if (!empty($page[$field])) {
                $filePath = ROOT_PATH . '/public' . $page[$field];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // Deleta a página
        Magazine::deletePage($pageId);

        // Resequencia os page_number das páginas restantes
        $remaining = Magazine::getPages($page['magazine_id']);
        $num = 1;
        foreach ($remaining as $p) {
            Database::update('magazine_pages', ['page_number' => $num], 'id = ?', [$p['id']]);
            $num++;
        }

        $this->json(['success' => true]);
    }

    /**
     * Deletar imagem de uma página (AJAX)
     */
    public function deletePageImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $pageId = (int) $this->input('page_id', 0);
        $field = $this->input('field', '');

        $allowedFields = ['image_url', 'image_url_2', 'image_url_3'];
        if (!$pageId || !in_array($field, $allowedFields)) {
            $this->json(['error' => 'Dados inválidos.'], 400);
            return;
        }

        // Buscar imagem atual pra deletar o arquivo
        $page = Database::fetch("SELECT {$field} FROM magazine_pages WHERE id = ?", [$pageId]);
        if ($page && !empty($page[$field])) {
            $filePath = ROOT_PATH . '/public' . $page[$field];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Limpar campo no banco
        Magazine::updatePage($pageId, [$field => null]);

        $this->json(['success' => true]);
    }

    public function uploadImage(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $pageId = (int) $this->input('page_id');

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['error' => 'Tipo de arquivo não permitido.'], 400);
            return;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'magazine_page_' . $pageId . '_' . time() . '.' . $ext;
        $uploadDir = ROOT_PATH . '/public/uploads/magazines/pages/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $imageUrl = '/uploads/magazines/pages/' . $filename;
            $field = $this->input('field', 'image_url');
            $allowedFields = ['image_url', 'image_url_2', 'image_url_3'];
            if (!in_array($field, $allowedFields)) $field = 'image_url';
            Magazine::updatePage($pageId, [$field => $imageUrl]);
            $this->json(['success' => true, 'url' => $imageUrl]);
        } else {
            $this->json(['error' => 'Erro ao salvar o arquivo.'], 500);
        }
    }

    /**
     * Proxy para servir imagens locais sem problemas de CORS (usado pelo PDF export)
     */
    public function imageProxy(): void
    {
        $url = $this->input('url', '');
        
        if (empty($url)) {
            http_response_code(400);
            exit;
        }

        // Extrai o path relativo da URL (remove domínio se presente)
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            http_response_code(400);
            exit;
        }

        // Monta caminho local
        $localPath = ROOT_PATH . '/public' . $path;

        if (!file_exists($localPath)) {
            http_response_code(404);
            exit;
        }

        // Determina o mime type
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        $mime = $mimeTypes[$ext] ?? 'image/jpeg';

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Access-Control-Allow-Origin: *');
        readfile($localPath);
        exit;
    }

    public function generatePageImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $pageId = (int) $this->input('page_id');
        $page = Database::fetch("SELECT * FROM magazine_pages WHERE id = ?", [$pageId]);

        if (!$page) {
            $this->json(['error' => 'Página não encontrada.'], 404);
            return;
        }

        $description = $this->input('description', $page['title'] ?? 'Construção de alto padrão');
        $field = $this->input('field', 'image_url');
        $allowedFields = ['image_url', 'image_url_2', 'image_url_3'];
        if (!in_array($field, $allowedFields)) $field = 'image_url';

        // Determina orientação baseado no layout e posição da imagem
        $layout = $page['layout_type'] ?? '';
        $orientation = 'landscape';

        if (in_array($layout, ['internal_02', 'internal_07'])) {
            if ($field === 'image_url') $orientation = 'portrait';
        }
        if ($layout === 'internal_05' || $layout === 'internal_06') {
            $orientation = 'portrait';
        }
        if ($layout === 'internal_01' && $field === 'image_url_2') {
            $orientation = 'portrait';
        }

        try {
            $openai = new OpenAIService();
            $imageUrl = $openai->generateImage($description, $orientation);

            if ($imageUrl) {
                Magazine::updatePage($pageId, [$field => $imageUrl]);
                $this->json(['success' => true, 'url' => $imageUrl]);
            } else {
                $this->json(['error' => 'Não foi possível gerar a imagem.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    public function approve(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        Magazine::updateById($id, [
            'status' => Magazine::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Revista aprovada! Agora você pode publicá-la.');
        $this->redirect('/admin/magazines/edit/' . $id);
    }

    public function publish(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.publish')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        Magazine::updateById($id, [
            'status' => Magazine::STATUS_PUBLISHED,
            'published_at' => date('Y-m-d H:i:s'),
            'published_by' => Auth::id(),
        ]);

        // Envia newsletter para todos os inscritos
        $this->sendMagazineNewsletter($id);

        $this->setFlash('success', 'Revista publicada e enviada para os assinantes!');
        $this->redirect('/admin/magazines');
    }

    /**
     * Publicar em MODO DE TESTE: revista fica visível apenas para usuários logados
     * e a notificação (e-mail + WhatsApp) é enviada somente para os contatos de teste.
     */
    public function publishTest(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.publish')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        // MODO TESTE: marca a revista como 'test' (aparece como "Publicada (Teste)").
        // NÃO é publicação oficial: não vai para o público e os botões de publicar
        // continuam disponíveis. Envia a notificação só para os contatos de teste.
        // Protegido: garante que o ENUM aceite 'test' e então marca o status.
        try {
            Magazine::ensureTestStatusSupported();
            Magazine::updateById($id, ['status' => Magazine::STATUS_TEST]);
        } catch (\Throwable $e) {
            error_log('[MAGAZINE_TEST] Não foi possível marcar status test: ' . $e->getMessage());
        }

        try {
            $this->sendMagazineNewsletter($id, true);
            $this->setFlash('success', 'Teste enviado! A revista está marcada como "Publicada (Teste)". Notificação enviada só para os contatos de teste, com link de acesso direto e o PDF. Você ainda pode publicá-la oficialmente.');
        } catch (\Throwable $e) {
            error_log('[MAGAZINE_TEST] Falha ao notificar teste: ' . $e->getMessage());
            $this->setFlash('error', 'Revista marcada como TESTE, mas houve um problema ao enviar a notificação: ' . $e->getMessage());
        }

        $this->redirect('/admin/magazines/edit/' . $id);
    }

    /**
     * Gera (ou regenera) o PDF da revista no servidor via Browserless.
     * O PDF fica salvo e é o que os leitores veem no visualizador — idêntico
     * em qualquer dispositivo. Roda no servidor, então independe do navegador
     * de quem clica (funciona igual no MacBook/Safari do gestor).
     */
    public function generatePdf(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.edit')) {
            if ($this->isAjax()) { $this->json(['success' => false, 'error' => 'Sem permissão.'], 403); return; }
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);
        if (!$magazine) {
            if ($this->isAjax()) { $this->json(['success' => false, 'error' => 'Revista não encontrada.'], 404); return; }
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        // Trava de limite mensal (protege o plano gratuito do Browserless).
        $usage = \App\Services\BrowserlessPdfService::usageStatus();
        if (!$usage['allowed']) {
            $msg = 'Limite mensal de unidades do Browserless atingido (' . $usage['used'] . '/' . $usage['limit']
                . ' unidades). O contador zera no próximo mês. Se precisar, aumente o limite em Configurações ou faça upgrade do plano Browserless.';
            if ($this->isAjax()) { $this->json(['success' => false, 'error' => $msg], 429); return; }
            $this->setFlash('error', $msg);
            $this->redirect('/admin/magazines/edit/' . $id);
            return;
        }

        // A geração no Browserless leva ~30-50s, o que estoura o timeout do
        // nginx se o navegador ficar esperando. Por isso rodamos em BACKGROUND:
        // respondemos "iniciado" na hora, desconectamos o request e seguimos
        // gerando. O front-end acompanha por polling (pdfStatus).
        \App\Services\BrowserlessPdfService::setStatus($id, 'processing');

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $response = json_encode(['success' => true, 'started' => true]);
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(202);
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($response));
        header('Connection: close');
        echo $response;
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // A partir daqui o navegador já recebeu a resposta — nada de timeout.
        set_time_limit(0);
        ignore_user_abort(true);

        try {
            $pdfUrl = \App\Services\BrowserlessPdfService::generate($id);
            \App\Services\BrowserlessPdfService::setStatus($id, $pdfUrl ? 'done' : 'failed');
        } catch (\Throwable $e) {
            error_log('[MAGAZINE_PDF] Erro no background: ' . $e->getMessage());
            \App\Services\BrowserlessPdfService::setStatus($id, 'failed');
        }
        exit;
    }

    /**
     * Endpoint de polling: informa se a geração do PDF terminou.
     * Retorna { status: processing|done|failed|none, url? }.
     */
    public function pdfStatus(): void
    {
        $id = (int) $this->input('magazine_id');
        if (!$id) { $this->json(['status' => 'none']); return; }

        $status = \App\Services\BrowserlessPdfService::getStatus($id);
        $url = \App\Services\BrowserlessPdfService::existingPdfUrl($id);
        $this->json([
            'status' => $status,
            'url' => $url,
        ]);
    }

    /**
     * Detecta requisição AJAX (fetch/XHR) para responder JSON.
     */
    private function isAjax(): bool
    {
        $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strtolower($xrw) === 'xmlhttprequest') return true;
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    /**
     * Reverte uma revista publicada/em teste de volta para "Aprovada",
     * permitindo publicar novamente (útil para repetir o teste).
     * NÃO reenvia notificação — apenas muda o status.
     */
    public function unpublish(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.publish')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        Magazine::updateById($id, [
            'status' => Magazine::STATUS_APPROVED,
        ]);

        $this->setFlash('success', 'Revista voltou para "Aprovada". Você já pode publicar ou testar novamente.');
        $this->redirect('/admin/magazines/edit/' . $id);
    }

    public function preview(string $id = ''): void
    {
        $id = (int) ($id ?: $this->input('id'));
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        $pages = Magazine::getPages($id);

        $this->view('admin.magazines.preview', [
            'magazine' => $magazine,
            'pages' => $pages,
        ]);
    }

    public function delete(): void
    {
        if (!$this->isPost() || !Auth::isSuperAdmin()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        Magazine::deleteById($id);

        $this->setFlash('success', 'Revista excluída com sucesso!');
        $this->redirect('/admin/magazines');
    }

    public function schedule(): void
    {
        $settings = [
            'magazine_frequency' => Setting::get('magazine_frequency', 'quinzenal'),
            'magazine_times_per_period' => Setting::get('magazine_times_per_period', '1'),
            'magazine_day_of_week' => Setting::get('magazine_day_of_week', '1'),
            'magazine_day_of_month' => Setting::get('magazine_day_of_month', '1'),
        ];

        $this->view('admin.magazines.schedule', [
            'settings' => $settings,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function updateSchedule(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines/schedule');
            return;
        }

        Setting::setMultiple([
            'magazine_frequency' => $this->input('magazine_frequency'),
            'magazine_times_per_period' => $this->input('magazine_times_per_period'),
            'magazine_day_of_week' => $this->input('magazine_day_of_week'),
            'magazine_day_of_month' => $this->input('magazine_day_of_month'),
        ]);

        $this->setFlash('success', 'Agendamento atualizado com sucesso!');
        $this->redirect('/admin/magazines/schedule');
    }

    /**
     * Adiciona página de Coluna do Convidado em revistas que não possuem
     */
    public function addGuestColumn(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.edit')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        $pages = Magazine::getPages($id);

        // Verifica se já tem guest_column
        foreach ($pages as $page) {
            if ($page['layout_type'] === 'guest_column') {
                $this->setFlash('error', 'Esta revista já possui uma Coluna do Convidado.');
                $this->redirect('/admin/magazines/edit/' . $id);
                return;
            }
        }

        // Desloca page_number de todas as páginas a partir da posição 3
        Database::query(
            "UPDATE magazine_pages SET page_number = page_number + 1 WHERE magazine_id = ? AND page_number >= 3 ORDER BY page_number DESC",
            [$id]
        );

        // Insere a página guest_column na posição 3
        Magazine::addPage($id, [
            'page_number' => 3,
            'title' => '',
            'subtitle' => '',
            'content' => '',
            'caption' => 'Coluna do Convidado',
            'layout_type' => 'guest_column',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Coluna do Convidado adicionada com sucesso!');
        $this->redirect('/admin/magazines/edit/' . $id);
    }

    /**
     * Adiciona página de Causos de Obra antes da contracapa
     */
    public function addConstructionStories(): void
    {
        if (!$this->isPost() || !Auth::hasPermission('magazines.edit')) {
            $this->redirect('/admin/magazines');
            return;
        }

        $id = (int) $this->input('magazine_id');
        $magazine = Magazine::find($id);

        if (!$magazine) {
            $this->setFlash('error', 'Revista não encontrada.');
            $this->redirect('/admin/magazines');
            return;
        }

        $pages = Magazine::getPages($id);

        // Verifica se já tem construction_stories
        foreach ($pages as $page) {
            if ($page['layout_type'] === 'construction_stories') {
                $this->setFlash('error', 'Esta revista já possui uma página de Causos de Obra.');
                $this->redirect('/admin/magazines/edit/' . $id);
                return;
            }
        }

        // Encontra a posição da backcover para inserir antes dela
        $backcoverPos = null;
        foreach ($pages as $page) {
            if ($page['layout_type'] === 'backcover') {
                $backcoverPos = (int) $page['page_number'];
                break;
            }
        }

        if ($backcoverPos === null) {
            // Se não tem backcover, insere como última página
            $backcoverPos = count($pages) + 1;
        }

        // Desloca page_number de todas as páginas a partir da posição da backcover
        Database::query(
            "UPDATE magazine_pages SET page_number = page_number + 1 WHERE magazine_id = ? AND page_number >= ? ORDER BY page_number DESC",
            [$id, $backcoverPos]
        );

        // Insere a página construction_stories antes da backcover
        Magazine::addPage($id, [
            'page_number' => $backcoverPos,
            'title' => 'Causos de Obra',
            'subtitle' => 'Histórias reais (ou quase) dos bastidores da construção',
            'content' => '',
            'caption' => 'Histórias da Obra',
            'layout_type' => 'construction_stories',
            'show_images' => '1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Página de Causos de Obra adicionada com sucesso!');
        $this->redirect('/admin/magazines/edit/' . $id);
    }

    /**
     * Executa a geração completa da revista em background (após já ter respondido ao navegador)
     */
    private function executeBackgroundGeneration(int $jobId, int $topicId, int $userId): void
    {
        $updateJob = function(array $data) use ($jobId) {
            Database::update('generation_jobs', $data, 'id = ?', [$jobId]);
        };

        $updateStep = function(int $step, string $label) use ($updateJob) {
            $updateJob(['current_step' => $step, 'current_step_label' => $label]);
        };

        $failJob = function(string $error) use ($updateJob) {
            $updateJob([
                'status' => 'failed',
                'error_message' => $error,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        };

        // Marca como processando
        $updateJob([
            'status' => 'processing',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $topic = MagazineTopic::find($topicId);
        if (!$topic) {
            $failJob('Tema não encontrado.');
            return;
        }

        // PASSO 1: Gerar conteúdo
        $updateStep(1, 'Gerando conteúdo da revista com IA...');

        try {
            $openai = new OpenAIService();
            $sourceUrls = $topic['source_urls'] ?? '';
            $content = $openai->generateMagazineContent($topic['title'], $topic['description'], $sourceUrls);
        } catch (\Exception $e) {
            $failJob('Erro ao gerar conteúdo: ' . $e->getMessage());
            return;
        }

        // PASSO 2: Salvar no banco
        $updateStep(2, 'Salvando revista no banco de dados...');

        try {
            $magazineId = Magazine::create([
                'title' => $content['title'],
                'subtitle' => $content['subtitle'] ?? '',
                'topic_id' => $topicId,
                'status' => Magazine::STATUS_GENERATED,
                'cover_image' => Setting::get('magazine_default_cover', null),
                'generated_by' => 'ai',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($content['pages'] as $index => $page) {
                $pageNumber = $index + 1;
                // Desloca numeração para abrir espaço para guest_column na posição 3
                if ($pageNumber >= 3) $pageNumber++;
                
                Magazine::addPage($magazineId, [
                    'page_number' => $pageNumber,
                    'title' => $page['title'] ?? '',
                    'subtitle' => $page['subtitle'] ?? '',
                    'content' => $page['content'] ?? '',
                    'image_url' => null,
                    'image_url_2' => null,
                    'image_suggestion' => $page['image_suggestion'] ?? null,
                    'image_suggestion_2' => $page['image_suggestion_2'] ?? null,
                    'caption' => $page['caption'] ?? null,
                    'layout_type' => $page['layout'] ?? 'internal_01',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Insere página de Coluna do Convidado na posição 3 (após cover e subcover)
            Magazine::addPage($magazineId, [
                'page_number' => 3,
                'title' => '',
                'subtitle' => '',
                'content' => '',
                'caption' => 'Coluna do Convidado',
                'layout_type' => 'guest_column',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $updateJob(['magazine_id' => $magazineId]);
            MagazineTopic::markAsUsed($topicId);

            // Salvar fontes se a IA retornou
            if (!empty($content['sources'])) {
                foreach ($content['sources'] as $i => $src) {
                    Database::insert('magazine_sources', [
                        'magazine_id' => $magazineId,
                        'title' => $src['title'] ?? 'Fonte ' . ($i + 1),
                        'url' => $src['url'] ?? null,
                        'author' => $src['author'] ?? null,
                        'sort_order' => $i,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $failJob('Erro ao salvar revista: ' . $e->getMessage());
            return;
        }

        // PASSO 3: Identificar imagens
        $updateStep(3, 'Identificando imagens para gerar...');

        $pages = Magazine::getPages($magazineId);
        $imagesToGenerate = [];

        foreach ($pages as $page) {
            if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column', 'construction_stories'])) {
                continue;
            }

            $suggestion = $page['image_suggestion'] ?? null;
            $suggestion2 = $page['image_suggestion_2'] ?? null;
            $oneImageLayouts = ['internal_04', 'internal_07'];

            if ($suggestion) {
                $orientation = 'landscape';
                if (in_array($page['layout_type'], ['internal_02', 'internal_07'])) $orientation = 'portrait';
                if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

                $imagesToGenerate[] = [
                    'page_id' => $page['id'],
                    'page_number' => $page['page_number'],
                    'field' => 'image_url',
                    'description' => $suggestion,
                    'orientation' => $orientation,
                ];
            }

            if ($suggestion2 && !in_array($page['layout_type'], $oneImageLayouts)) {
                $orientation = 'landscape';
                if ($page['layout_type'] === 'internal_01') $orientation = 'portrait';
                if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

                $imagesToGenerate[] = [
                    'page_id' => $page['id'],
                    'page_number' => $page['page_number'],
                    'field' => 'image_url_2',
                    'description' => $suggestion2,
                    'orientation' => $orientation,
                ];
            }

            // Imagem 3 — para layouts que usam 3 imagens (internal_05, internal_06)
            $threeImageLayouts = ['internal_05', 'internal_06'];
            if (in_array($page['layout_type'], $threeImageLayouts) && empty($page['image_url_3'])) {
                // Usa image_suggestion_2 como base com variação, ou image_suggestion se não tiver
                $desc3 = $suggestion2 ?: $suggestion;
                if ($desc3) {
                    $imagesToGenerate[] = [
                        'page_id' => $page['id'],
                        'page_number' => $page['page_number'],
                        'field' => 'image_url_3',
                        'description' => $desc3 . ' (ângulo alternativo)',
                        'orientation' => 'portrait',
                    ];
                }
            }
        }

        $totalSteps = 3 + count($imagesToGenerate);
        $updateJob(['total_steps' => $totalSteps]);

        // PASSO 4+: Gerar cada imagem (com retry automático)
        $imageCount = 0;
        $imageErrors = 0;
        $maxRetries = 3;

        foreach ($imagesToGenerate as $i => $img) {
            $stepNum = 4 + $i;
            $imgLabel = ($img['field'] === 'image_url_2') ? 'Imagem 2' : 'Imagem 1';
            $updateStep($stepNum, "Gerando {$imgLabel} da Página {$img['page_number']}...");

            $success = false;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    if ($attempt > 1) {
                        $updateStep($stepNum, "Gerando {$imgLabel} da Página {$img['page_number']}... (tentativa {$attempt}/{$maxRetries})");
                        // Aguarda um pouco antes de tentar novamente
                        sleep(3);
                    }

                    $imageUrl = $openai->generateImage($img['description'], $img['orientation']);
                    if ($imageUrl) {
                        Magazine::updatePage($img['page_id'], [$img['field'] => $imageUrl]);
                        $imageCount++;
                        $success = true;
                        break; // Sucesso, sai do loop de retry
                    }
                } catch (\Exception $e) {
                    error_log("Job #{$jobId} - Tentativa {$attempt} falhou Pág.{$img['page_number']} ({$img['field']}): " . $e->getMessage());
                    if ($attempt === $maxRetries) {
                        // Última tentativa falhou
                        $imageErrors++;
                    }
                }
            }
        }

        // FINALIZAR
        $finalLabel = "Concluído! {$imageCount} imagens geradas.";
        if ($imageErrors > 0) {
            $finalLabel .= " ({$imageErrors} falharam)";
        }

        $updateJob([
            'status' => 'completed',
            'current_step' => $totalSteps,
            'current_step_label' => $finalLabel,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Envia notificação
        $this->sendGenerationNotification($magazineId, $content['title'], $topic['title']);
    }

    private function sendGenerationNotification(int $magazineId, string $title, string $topicTitle = ''): void
    {
        $emails = Setting::get('notification_emails', '');
        if (empty($emails)) {
            return;
        }

        try {
            $mail = new MailService();
            $emailList = array_map('trim', explode(',', $emails));
            $htmlBody = \App\Services\EmailTemplate::magazineGenerated($title, $magazineId, $topicTitle);

            foreach ($emailList as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->send($email, 'Nova Revista Gerada: ' . ($topicTitle ?: $title) . ' - Brooks Construtora', $htmlBody, true);
                }
            }
        } catch (\Exception $e) {
            error_log('Erro ao enviar notificação: ' . $e->getMessage());
        }
    }

    private function sendMagazineNewsletter(int $magazineId, bool $testMode = false): void
    {
        try {
            $magazine = Magazine::find($magazineId);

            // O que aparece nas notificações é o NOME DA REVISTA (campo próprio
            // 'magazine_name', editável em Informações Gerais). Não é o Título/
            // Subtítulo da capa nem o tema. Fallbacks: título da revista → tema.
            $magName = trim((string) ($magazine['magazine_name'] ?? ''));
            $magTitle = trim((string) ($magazine['title'] ?? ''));

            $topicTitle = '';
            if (!empty($magazine['topic_id'])) {
                $topic = MagazineTopic::find($magazine['topic_id']);
                $topicTitle = $topic['title'] ?? '';
            }

            // Em modo teste, usa apenas os contatos de teste configurados.
            // Caso contrário, todos os assinantes ativos.
            $subscribers = $testMode
                ? $this->getTestSubscribers()
                : \App\Models\Newsletter::getActiveSubscribers();

            $mail = new MailService();
            // Título exibido na notificação: nome da revista → título → tema.
            $displayTitle = $magName !== '' ? $magName : ($magTitle !== '' ? $magTitle : $topicTitle);
            $subjectPrefix = $testMode ? '[TESTE] ' : '';

            // Em modo teste: link com token (abre sem login). No oficial o link
            // é o normal (a revista já é pública).
            $previewToken = $testMode ? Magazine::ensurePreviewToken($magazineId) : '';

            // Anexa o PDF da revista (o gerado pelo Browserless, que JÁ está
            // salvo e é o mesmo que o leitor vê no site) — TANTO no teste quanto
            // no oficial. Assim o cliente recebe exatamente o que você testou.
            $pdfPath = null;
            $pdfRel = \App\Services\BrowserlessPdfService::existingPdfUrl($magazineId);
            if ($pdfRel) {
                $candidate = ROOT_PATH . '/public' . $pdfRel;
                if (is_file($candidate)) {
                    $pdfPath = $candidate;
                }
            }
            $attachments = [];
            if ($pdfPath) {
                $safeTitle = preg_replace('/[^a-zA-Z0-9]+/', '_', $displayTitle);
                $attachments[] = [
                    'path' => $pdfPath,
                    'name' => 'Revista_Brooks_' . $safeTitle . '.pdf',
                    'mime' => 'application/pdf',
                ];
            }

            // Enviar e-mails
            foreach ($subscribers as $subscriber) {
                if (empty($subscriber['email'])) continue;
                $htmlBody = \App\Services\EmailTemplate::magazinePublished(
                    $displayTitle,               // título exibido = nome editável da revista
                    $magazineId,
                    $subscriber['name'] ?? '',
                    $subscriber['email'] ?? '',
                    $displayTitle,               // mantém compat: usado como fallback interno
                    $previewToken,
                    $testMode && !$pdfPath       // se não gerou PDF, mostrar botão de download no e-mail
                );

                $mail->send(
                    $subscriber['email'],
                    $subjectPrefix . 'Nova Revista: ' . $displayTitle . ' - Brooks Construtora',
                    $htmlBody,
                    true,
                    $attachments
                );
            }

            // NÃO apagar o PDF: agora é o arquivo PERMANENTE do Browserless,
            // que o visualizador do site usa. (Antes era um PDF temporário.)

            // Enviar webhook WhatsApp
            $this->sendMagazineWebhook($magazineId, $magazine, $displayTitle, $subscribers, $testMode, $previewToken);

        } catch (\Throwable $e) {
            error_log('Erro ao enviar newsletter: ' . $e->getMessage());
            // Em modo teste, propaga para o chamador mostrar a mensagem ao usuário
            if ($testMode) {
                throw $e;
            }
        }
    }

    /**
     * Retorna a lista de contatos de teste (você, Mariana, etc.)
     * configurada em Configurações. Formato: um contato por linha "Nome|email|telefone"
     * ou apenas e-mails/telefones separados por vírgula.
     */
    private function getTestSubscribers(): array
    {
        $emailsRaw = Setting::get('magazine_test_emails', '');
        $phonesRaw = Setting::get('magazine_test_phones', '');

        $subscribers = [];

        // E-mails e telefones de teste são listas INDEPENDENTES. Cada e-mail
        // válido recebe o e-mail; cada telefone válido recebe o WhatsApp. NÃO
        // alinhamos e-mail com telefone por posição (o casamento por índice
        // antigo fazia telefones serem descartados quando as quantidades de
        // e-mails e telefones eram diferentes — por isso só alguns recebiam).

        // Aceita separação por vírgula OU quebra de linha.
        $splitList = function (string $raw): array {
            $parts = preg_split('/[,\r\n]+/', $raw) ?: [];
            return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
        };

        // E-mails de teste
        foreach ($splitList($emailsRaw) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $subscribers[] = ['name' => 'Teste', 'email' => $email, 'phone' => null];
            }
        }

        // Telefones de teste — cada um vira um destinatário próprio de WhatsApp.
        foreach ($splitList($phonesRaw) as $phone) {
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) < 10) continue;
            $subscribers[] = ['name' => 'Teste', 'email' => null, 'phone' => $digits];
        }

        return $subscribers;
    }

    /**
     * Enviar webhook de nova revista para assinantes com WhatsApp
     */
    private function sendMagazineWebhook(int $magazineId, array $magazine, string $displayTitle, array $subscribers, bool $testMode = false, string $previewToken = ''): void
    {
        $webhookUrl = \App\Models\Setting::get('magazine_webhook_url', '');
        if (empty(trim($webhookUrl))) return;

        $defaultPhone = \App\Models\Setting::get('magazine_webhook_phone', '');
        $defaultPhoneName = \App\Models\Setting::get('magazine_webhook_phone_name', '');
        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br');
        // No modo teste, o link leva o token de preview (abre sem login)
        $magazineUrl = "{$baseUrl}/revista/ver/{$magazineId}";
        if ($testMode && !empty($previewToken)) {
            $magazineUrl .= '?preview=' . $previewToken;
        }

        $titlePrefix = $testMode ? "*[TESTE] Nova Revista Brooks!*\n\n" : "*Nova Revista Brooks!*\n\n";
        $message = $titlePrefix
            . "*{$displayTitle}*\n\n"
            . "Uma nova edição da Revista Brooks acabou de ser publicada!\n\n"
            . "*Leia agora:*\n{$magazineUrl}";

        // Coletar telefones dos assinantes
        $phones = [];
        $phoneNames = [];
        foreach ($subscribers as $sub) {
            if (!empty($sub['phone'])) {
                $phone = preg_replace('/\D/', '', $sub['phone']);
                if (strlen($phone) >= 10) {
                    $phones[] = $phone;
                    $phoneNames[] = $sub['name'] ?: $phone;
                }
            }
        }

        // Se nenhum contato tem telefone, usar o padrão — EXCETO em modo teste
        // (no teste não queremos disparar para o telefone padrão da empresa)
        if (empty($phones)) {
            if (!$testMode && !empty($defaultPhone)) {
                $phones[] = $defaultPhone;
                $phoneNames[] = $defaultPhoneName ?: $defaultPhone;
            } else {
                return; // Sem telefones pra enviar
            }
        }

        // Enviar um webhook por telefone (igual ao sistema de pedidos)
        foreach ($phones as $i => $phone) {
            $recipientName = $phoneNames[$i] ?? $phone;

            \App\Services\NotificationService::queueWebhook($webhookUrl, [
                'event' => $testMode ? 'magazine_test' : 'magazine_published',
                'test_mode' => $testMode,
                'magazine_id' => $magazineId,
                'title' => $displayTitle,
                'magazine_url' => $magazineUrl,
                'phone' => $phone,
                'phone_name' => $recipientName,
                'message' => $message,
            ], null, $testMode ? 'magazine_test' : 'magazine_published');
        }
    }
}
