<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Newsletter;

class NewsletterController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('newsletter')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $subscribers = Newsletter::all('subscribed_at DESC');

        $this->view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function export(): void
    {
        $subscribers = Newsletter::getActiveSubscribers();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome', 'E-mail', 'Data de Inscrição', 'Status']);

        foreach ($subscribers as $sub) {
            fputcsv($output, [
                $sub['name'],
                $sub['email'],
                $sub['subscribed_at'],
                $sub['active'] ? 'Ativo' : 'Inativo',
            ]);
        }

        fclose($output);
        exit;
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/newsletter');
            return;
        }

        $id = (int) $this->input('id');

        if ($id > 0) {
            Newsletter::deleteById($id);
            $this->setFlash('success', 'Inscrito removido com sucesso.');
        }

        $this->redirect('/admin/newsletter');
    }

    /**
     * Reenviar última revista via WhatsApp para um inscrito específico
     */
    public function resendWhatsapp(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $subscriberId = (int) $this->input('subscriber_id', 0);
        $subscriber = Newsletter::find($subscriberId);

        if (!$subscriber || empty($subscriber['phone'])) {
            $this->json(['error' => 'Inscrito não encontrado ou sem WhatsApp.'], 400);
            return;
        }

        $webhookUrl = \App\Models\Setting::get('magazine_webhook_url', '');
        if (empty($webhookUrl)) {
            $this->json(['error' => 'URL do webhook da revista não configurada. Acesse Configurações.'], 400);
            return;
        }

        // Buscar última revista publicada
        $magazine = \App\Core\Database::fetch(
            "SELECT * FROM magazines WHERE status = 'published' ORDER BY published_at DESC LIMIT 1"
        );

        if (!$magazine) {
            $this->json(['error' => 'Nenhuma revista publicada encontrada.'], 404);
            return;
        }

        $topicTitle = '';
        if ($magazine['topic_id']) {
            $topic = \App\Core\Database::fetch("SELECT title FROM magazine_topics WHERE id = ?", [$magazine['topic_id']]);
            $topicTitle = $topic['title'] ?? '';
        }
        $displayTitle = $topicTitle ?: $magazine['title'];

        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $magazineUrl = "{$baseUrl}/revista/ver/{$magazine['id']}";

        $message = "*Nova Revista Brooks!*\n\n"
            . "*{$displayTitle}*\n\n"
            . "Uma nova edição da Revista Brooks acabou de ser publicada!\n\n"
            . "*Leia agora:*\n{$magazineUrl}";

        $phone = preg_replace('/\D/', '', $subscriber['phone']);

        \App\Services\NotificationService::queueWebhook($webhookUrl, [
            'event' => 'magazine_published',
            'magazine_id' => $magazine['id'],
            'title' => $displayTitle,
            'magazine_url' => $magazineUrl,
            'phone' => $phone,
            'phone_name' => $subscriber['name'] ?: $phone,
            'message' => $message,
        ], null, 'magazine_published');

        $this->json(['success' => true]);
    }

    /**
     * Reenviar última revista via WhatsApp para TODOS os inscritos com telefone
     */
    public function resendWhatsappAll(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $webhookUrl = \App\Models\Setting::get('magazine_webhook_url', '');
        if (empty($webhookUrl)) {
            $this->json(['error' => 'URL do webhook da revista não configurada.'], 400);
            return;
        }

        $magazine = \App\Core\Database::fetch(
            "SELECT * FROM magazines WHERE status = 'published' ORDER BY published_at DESC LIMIT 1"
        );

        if (!$magazine) {
            $this->json(['error' => 'Nenhuma revista publicada.'], 404);
            return;
        }

        $topicTitle = '';
        if ($magazine['topic_id']) {
            $topic = \App\Core\Database::fetch("SELECT title FROM magazine_topics WHERE id = ?", [$magazine['topic_id']]);
            $topicTitle = $topic['title'] ?? '';
        }
        $displayTitle = $topicTitle ?: $magazine['title'];

        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $magazineUrl = "{$baseUrl}/revista/ver/{$magazine['id']}";

        $message = "*Nova Revista Brooks!*\n\n"
            . "*{$displayTitle}*\n\n"
            . "Uma nova edição da Revista Brooks acabou de ser publicada!\n\n"
            . "*Leia agora:*\n{$magazineUrl}";

        $subscribers = Newsletter::getActiveSubscribers();
        $count = 0;

        foreach ($subscribers as $sub) {
            if (empty($sub['phone'])) continue;

            $phone = preg_replace('/\D/', '', $sub['phone']);
            if (strlen($phone) < 10) continue;

            \App\Services\NotificationService::queueWebhook($webhookUrl, [
                'event' => 'magazine_published',
                'magazine_id' => $magazine['id'],
                'title' => $displayTitle,
                'magazine_url' => $magazineUrl,
                'phone' => $phone,
                'phone_name' => $sub['name'] ?: $phone,
                'message' => $message,
            ], null, 'magazine_published');

            $count++;
        }

        // Se ninguém tinha telefone, enviar pro padrão
        if ($count === 0) {
            $defaultPhone = \App\Models\Setting::get('magazine_webhook_phone', '');
            $defaultName = \App\Models\Setting::get('magazine_webhook_phone_name', '');
            if (!empty($defaultPhone)) {
                \App\Services\NotificationService::queueWebhook($webhookUrl, [
                    'event' => 'magazine_published',
                    'magazine_id' => $magazine['id'],
                    'title' => $displayTitle,
                    'magazine_url' => $magazineUrl,
                    'phone' => $defaultPhone,
                    'phone_name' => $defaultName ?: $defaultPhone,
                    'message' => $message,
                ], null, 'magazine_published');
                $count = 1;
            }
        }

        $this->json(['success' => true, 'count' => $count]);
    }
}
