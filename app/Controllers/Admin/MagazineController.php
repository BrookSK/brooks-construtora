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
        $magazines = Magazine::all('created_at DESC');

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

        try {
            $openai = new OpenAIService();
            $topics = $openai->generateTopics($quantity);

            foreach ($topics as $topic) {
                MagazineTopic::create([
                    'title' => $topic['title'],
                    'description' => $topic['description'],
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

    public function generate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/magazines');
            return;
        }

        $topicId = (int) $this->input('topic_id');
        $topic = MagazineTopic::find($topicId);

        if (!$topic) {
            $this->setFlash('error', 'Tema não encontrado.');
            $this->redirect('/admin/magazines/topics');
            return;
        }

        try {
            $openai = new OpenAIService();

            // Gera o conteúdo da revista
            $content = $openai->generateMagazineContent($topic['title'], $topic['description']);

            // Cria a revista
            $magazineId = Magazine::create([
                'title' => $content['title'],
                'subtitle' => $content['subtitle'],
                'topic_id' => $topicId,
                'status' => Magazine::STATUS_GENERATED,
                'cover_image' => null,
                'generated_by' => 'ai',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Cria as páginas
            foreach ($content['pages'] as $index => $page) {
                Magazine::addPage($magazineId, [
                    'page_number' => $index + 1,
                    'title' => $page['title'] ?? '',
                    'content' => $page['content'] ?? '',
                    'image_url' => $page['image_url'] ?? null,
                    'layout_type' => $page['layout'] ?? 'text_image',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Marca o tema como usado
            MagazineTopic::markAsUsed($topicId);

            // Envia e-mail de notificação
            $this->sendGenerationNotification($magazineId, $content['title']);

            $this->setFlash('success', 'Revista gerada com sucesso! Aguardando revisão.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao gerar revista: ' . $e->getMessage());
        }

        $this->redirect('/admin/magazines');
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
                Magazine::updatePage((int) $pageId, [
                    'title' => $pageData['title'] ?? '',
                    'content' => $pageData['content'] ?? '',
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
            Magazine::updatePage($pageId, ['image_url' => $imageUrl]);
            $this->json(['success' => true, 'url' => $imageUrl]);
        } else {
            $this->json(['error' => 'Erro ao salvar o arquivo.'], 500);
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

    private function sendGenerationNotification(int $magazineId, string $title): void
    {
        $emails = Setting::get('notification_emails', '');
        if (empty($emails)) {
            return;
        }

        try {
            $mail = new MailService();
            $emailList = array_map('trim', explode(',', $emails));
            $htmlBody = \App\Services\EmailTemplate::magazineGenerated($title, $magazineId);

            foreach ($emailList as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->send($email, 'Nova Revista Gerada - Brooks Construtora', $htmlBody, true);
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
            $subscribers = \App\Models\Newsletter::getActiveSubscribers();
            $mail = new MailService();

            foreach ($subscribers as $subscriber) {
                $htmlBody = \App\Services\EmailTemplate::magazinePublished(
                    $magazine['title'],
                    $magazineId,
                    $subscriber['name'] ?? ''
                );

                $mail->send(
                    $subscriber['email'],
                    'Nova Revista: ' . $magazine['title'] . ' - Brooks Construtora',
                    $htmlBody,
                    true
                );
            }
        } catch (\Exception $e) {
            error_log('Erro ao enviar newsletter: ' . $e->getMessage());
        }
    }
}
