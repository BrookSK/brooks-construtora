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
        $magazines = Magazine::getLatest(3);
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.home.index', [
            'magazines' => $magazines,
            'settings' => $siteSettings,
        ]);
    }

    public function sobre(): void
    {
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.home.sobre', [
            'settings' => $siteSettings,
        ]);
    }

    public function contato(): void
    {
        $siteSettings = Setting::getGroup('site_');

        $this->view('site.home.contato', [
            'settings' => $siteSettings,
            'flash' => $this->getFlash(),
        ]);
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
                $body = "Nova mensagem de contato:\n\n";
                $body .= "Nome: {$name}\n";
                $body .= "E-mail: {$email}\n";
                $body .= "Telefone: {$phone}\n\n";
                $body .= "Mensagem:\n{$message}";

                $mailService->send($siteEmail, 'Novo contato - Site Brooks Construtora', $body);
            }

            $this->setFlash('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao enviar mensagem. Tente novamente mais tarde.');
        }

        $this->redirect('/contato');
    }
}
