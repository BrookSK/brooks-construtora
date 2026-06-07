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

        include ROOT_PATH . '/app/Views/site/home/index.php';
    }

    public function sobre(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/home/sobre.php';
    }

    public function contato(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        $flash = $this->getFlash();
        include ROOT_PATH . '/app/Views/site/home/contato.php';
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

            if (!empty($siteEmail)) {
                $htmlBody = \App\Services\EmailTemplate::contactReceived($name, $email, $phone, nl2br(htmlspecialchars($message)));
                $mailService->send($siteEmail, 'Novo contato - Site Brooks Construtora', $htmlBody, true);
            }

            $this->setFlash('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
        } catch (\Exception $e) {
            $this->setFlash('success', 'Mensagem recebida! Entraremos em contato em breve.');
        }

        $this->redirect('/contato');
    }
}
