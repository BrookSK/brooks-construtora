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
        $magazineId = Magazine::create([
            'title' => $title,
            'subtitle' => $subtitle,
            'topic_id' => null,
            'status' => Magazine::STATUS_GENERATED,
            'cover_image' => null,
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
            if ($layout === 'cover') { $pageData['title'] = 'NÚCLEO'; $pageData['subtitle'] = 'CONSTRUÇÃO — SUSTENTÁVEL'; }
            if ($layout === 'subcover') { $pageData['title'] = 'ECO'; $pageData['subtitle'] = 'CONSTRUÇÃO — CONSCIENTE'; }
            if ($layout === 'guest_column') { $pageData['caption'] = 'Coluna do Convidado'; }
            if ($layout === 'backcover') { $pageData['content'] = 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.'; }
            
            Magazine::addPage($magazineId, $pageData);
        }

        $this->setFlash('success', 'Revista criada! Preencha o conteúdo das páginas.');
        $this->redirect('/admin/magazines/edit/' . $magazineId);
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
            if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column'])) {
                continue;
            }

            $suggestion = $page['image_suggestion'] ?? null;
            $suggestion2 = $page['image_suggestion_2'] ?? null;
            $oneImageLayouts = ['internal_04', 'internal_07'];

            if ($suggestion && empty($page['image_url'])) {
                $pending[] = [
                    'page_id' => $page['id'],
                    'page_number' => $page['page_number'],
                    'field' => 'image_url',
                    'description' => $suggestion,
                    'layout_type' => $page['layout_type'],
                ];
            }

            if ($suggestion2 && empty($page['image_url_2']) && !in_array($page['layout_type'], $oneImageLayouts)) {
                $pending[] = [
                    'page_id' => $page['id'],
                    'page_number' => $page['page_number'],
                    'field' => 'image_url_2',
                    'description' => $suggestion2,
                    'layout_type' => $page['layout_type'],
                ];
            }

            // Imagem 3 — para layouts com 3 imagens (internal_05, internal_06)
            $threeImageLayouts = ['internal_05', 'internal_06'];
            if (in_array($page['layout_type'], $threeImageLayouts) && empty($page['image_url_3'] ?? null)) {
                $desc3 = $suggestion2 ?: $suggestion;
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
        Magazine::updateById($id, [
            'title' => $this->input('title'),
            'subtitle' => $this->input('subtitle'),
        ]);

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

                Magazine::updatePage((int) $pageId, [
                    'title' => $pageData['title'] ?? '',
                    'subtitle' => $subtitle,
                    'content' => $pageData['content'] ?? '',
                    'caption' => $pageData['caption'] ?? '',
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
                'cover_image' => null,
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
            if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column'])) {
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

    private function sendMagazineNewsletter(int $magazineId): void
    {
        try {
            $magazine = Magazine::find($magazineId);
            
            // Busca o tema da revista
            $topicTitle = '';
            if ($magazine['topic_id']) {
                $topic = MagazineTopic::find($magazine['topic_id']);
                $topicTitle = $topic['title'] ?? '';
            }
            
            $subscribers = \App\Models\Newsletter::getActiveSubscribers();
            $mail = new MailService();
            $displayTitle = $topicTitle ?: $magazine['title'];

            foreach ($subscribers as $subscriber) {
                $htmlBody = \App\Services\EmailTemplate::magazinePublished(
                    $magazine['title'],
                    $magazineId,
                    $subscriber['name'] ?? '',
                    $subscriber['email'] ?? '',
                    $topicTitle
                );

                $mail->send(
                    $subscriber['email'],
                    'Nova Revista: ' . $displayTitle . ' - Brooks Construtora',
                    $htmlBody,
                    true
                );
            }
        } catch (\Exception $e) {
            error_log('Erro ao enviar newsletter: ' . $e->getMessage());
        }
    }
}
