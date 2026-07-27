<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('settings')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $settings = [
            // SMTP
            'smtp_host' => Setting::get('smtp_host', ''),
            'smtp_port' => Setting::get('smtp_port', '587'),
            'smtp_username' => Setting::get('smtp_username', ''),
            'smtp_password' => Setting::get('smtp_password', ''),
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls'),
            'smtp_from_email' => Setting::get('smtp_from_email', ''),
            'smtp_from_name' => Setting::get('smtp_from_name', 'Brooks Construtora'),

            // OpenAI
            'openai_api_key' => Setting::get('openai_api_key', ''),
            'openai_model' => Setting::get('openai_model', 'gpt-4'),
            'openai_image_model' => Setting::get('openai_image_model', 'dall-e-3'),

            // Revista - Agendamento
            'magazine_frequency' => Setting::get('magazine_frequency', 'quinzenal'),
            'magazine_times_per_period' => Setting::get('magazine_times_per_period', '1'),
            'magazine_day_of_week' => Setting::get('magazine_day_of_week', '1'),
            'magazine_day_of_month' => Setting::get('magazine_day_of_month', '1'),

            // Cron
            'cron_token' => Setting::get('cron_token', ''),
            'cron_last_run' => Setting::get('cron_last_run', ''),
            'cron_last_generated' => Setting::get('cron_last_generated', ''),

            // Logo da Revista
            'magazine_logo' => Setting::get('magazine_logo', ''),

            // E-mails de notificação
            'notification_emails' => Setting::get('notification_emails', ''),

            // Revista - Webhook WhatsApp
            'magazine_webhook_url' => Setting::get('magazine_webhook_url', ''),
            'magazine_webhook_phone' => Setting::get('magazine_webhook_phone', ''),
            'magazine_webhook_phone_name' => Setting::get('magazine_webhook_phone_name', ''),

            // Site
            'site_title' => Setting::get('site_title', 'Brooks Construtora'),
            'site_description' => Setting::get('site_description', ''),
            'site_phone' => Setting::get('site_phone', ''),
            'site_email' => Setting::get('site_email', ''),
            'site_address' => Setting::get('site_address', ''),
            'site_instagram' => Setting::get('site_instagram', ''),
            'site_facebook' => Setting::get('site_facebook', ''),
            'site_linkedin' => Setting::get('site_linkedin', ''),
            'site_whatsapp' => Setting::get('site_whatsapp', ''),
        ];

        $this->view('admin.settings.index', [
            'settings' => $settings,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/settings');
            return;
        }

        $allowedKeys = [
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
            'openai_api_key', 'openai_model', 'openai_image_model',
            'magazine_frequency', 'magazine_times_per_period',
            'magazine_day_of_week', 'magazine_day_of_month',
            'cron_token',
            'notification_emails',
            'magazine_webhook_url', 'magazine_webhook_phone', 'magazine_webhook_phone_name',
            'site_title', 'site_description', 'site_phone', 'site_email',
            'site_address', 'site_instagram', 'site_facebook',
            'site_linkedin', 'site_whatsapp',
        ];

        $data = [];
        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = $_POST[$key];
            }
        }

        Setting::setMultiple($data);

        $this->setFlash('success', 'Configurações atualizadas com sucesso!');
        $this->redirect('/admin/settings');
    }

    public function uploadMagazineLogo(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        if (!isset($_FILES['magazine_logo']) || $_FILES['magazine_logo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Erro no upload do arquivo.'], 400);
            return;
        }

        $file = $_FILES['magazine_logo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['error' => 'Tipo não permitido. Use PNG, WEBP, JPG ou SVG.'], 400);
            return;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'magazine_logo_' . time() . '.' . $ext;
        $uploadDir = ROOT_PATH . '/public/uploads/settings/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Remove logo anterior se existir
            $oldLogo = Setting::get('magazine_logo', '');
            if (!empty($oldLogo) && file_exists(ROOT_PATH . '/public' . $oldLogo)) {
                unlink(ROOT_PATH . '/public' . $oldLogo);
            }

            $logoUrl = '/uploads/settings/' . $filename;
            Setting::set('magazine_logo', $logoUrl);
            $this->json(['success' => true, 'url' => $logoUrl]);
        } else {
            $this->json(['error' => 'Erro ao salvar arquivo.'], 500);
        }
    }

    public function removeMagazineLogo(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $oldLogo = Setting::get('magazine_logo', '');
        if (!empty($oldLogo) && file_exists(ROOT_PATH . '/public' . $oldLogo)) {
            unlink(ROOT_PATH . '/public' . $oldLogo);
        }

        Setting::set('magazine_logo', '');
        $this->json(['success' => true]);
    }
}
