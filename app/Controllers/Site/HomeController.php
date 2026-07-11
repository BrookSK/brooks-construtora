<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\Magazine;
use App\Services\MailService;

class HomeController extends Controller
{
    public function index(): void
    {
        try {
            $magazines = Magazine::getLatest(3);
        } catch (\Exception $e) {
            $magazines = [];
        }

        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        // Novo site institucional (antigo usa ANTIGO_PREFIX)
        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/home/index.php';
        } else {
            include ROOT_PATH . '/app/Views/site/home/new-index.php';
        }
    }

    public function sobre(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/home/sobre.php';
        } else {
            include ROOT_PATH . '/app/Views/site/home/new-sobre.php';
        }
    }

    public function contato(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        $flash = $this->getFlash();
        if (defined('ANTIGO_PREFIX')) {
            include ROOT_PATH . '/app/Views/site/home/contato.php';
        } else {
            include ROOT_PATH . '/app/Views/site/home/new-contato.php';
        }
    }

    public function enviarContato(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/contato');
            return;
        }

        $name = trim($this->input('name'));
        $email = trim($this->input('email'));
        $phone = trim($this->input('phone'));
        $message = trim($this->input('message'));

        if (empty($name) || empty($email) || empty($message)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/contato');
            return;
        }

        try {
            $mailService = new MailService();
            $siteEmail = Setting::get('site_email', '');

            if (empty($siteEmail)) {
                error_log('[Contato] E-mail de contato (site_email) não configurado nas settings.');
                $this->setFlash('success', 'Mensagem recebida! Entraremos em contato em breve.');
                $this->redirect('/contato');
                return;
            }

            $htmlBody = \App\Services\EmailTemplate::contactReceived($name, $email, $phone, nl2br(htmlspecialchars($message)));
            $mailService->send($siteEmail, 'Novo contato - Site Brooks Construtora', $htmlBody, true);

            $this->setFlash('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
        } catch (\Exception $e) {
            error_log('[Contato] Erro ao enviar e-mail: ' . $e->getMessage());
            $this->setFlash('error', 'Não foi possível enviar sua mensagem. Tente novamente ou entre em contato pelo WhatsApp.');
        }

        $this->redirect('/contato');
    }

    public function vetriks(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/vetriks.php';
    }

    public function forcaEstrutural(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/forca-estrutural.php';
    }

    public function academy(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/academy.php';
    }

    public function politicaPrivacidade(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/politica-privacidade.php';
    }

    public function termos(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/termos.php';
    }
}
