<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Newsletter;

class NewsletterController extends Controller
{
    public function subscribe(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/');
            return;
        }

        $email = trim($this->input('email'));
        $name = trim($this->input('name', ''));
        $phone = trim($this->input('phone', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Informe um e-mail válido.'], 400);
            } else {
                $this->setFlash('error', 'Informe um e-mail válido.');
                $this->redirect('/');
            }
            return;
        }

        $result = Newsletter::subscribe($email, $name, $phone);

        if ($this->isAjax()) {
            if ($result) {
                $this->json(['success' => true, 'message' => 'Inscrição realizada com sucesso!']);
            } else {
                $this->json(['success' => false, 'message' => 'Este e-mail já está inscrito.']);
            }
        } else {
            if ($result) {
                $this->setFlash('success', 'Inscrição realizada com sucesso! Você receberá nossas novidades por e-mail.');
            } else {
                $this->setFlash('info', 'Este e-mail já está inscrito na nossa newsletter.');
            }
            $this->redirect('/');
        }
    }

    /**
     * Página pra assinante atualizar o WhatsApp (via token único)
     */
    public function updatePhone(string $token = ''): void
    {
        $subscriber = Newsletter::findByToken($token);
        $success = false;
        $errorMsg = '';

        if ($subscriber && $this->isPost()) {
            $phone = trim($this->input('phone', ''));
            if (empty($phone)) {
                $errorMsg = 'Informe seu WhatsApp.';
            } else {
                // Limpar formatação
                $phone = preg_replace('/\D/', '', $phone);
                if (strlen($phone) < 10) {
                    $errorMsg = 'Número inválido. Use DDD + número.';
                } else {
                    \App\Core\Database::update('newsletter_subscribers', ['phone' => $phone], 'id = ?', [$subscriber['id']]);
                    $subscriber['phone'] = $phone;
                    $success = true;
                }
            }
        }

        include ROOT_PATH . '/app/Views/site/newsletter/update_phone.php';
    }

    public function unsubscribe(): void
    {
        $email = trim($this->input('email'));

        try {
            $settings = \App\Models\Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        $success = false;
        $message = '';

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->isPost()) {
                // Confirma o cancelamento
                $result = Newsletter::unsubscribe($email);
                $success = true;
                $message = 'Sua inscrição foi cancelada com sucesso. Você não receberá mais nossos e-mails.';
            }
        }

        // Mostra a página de confirmação
        $pageTitle = 'Cancelar Inscrição';
        $currentPage = '';
        include ROOT_PATH . '/app/Views/site/newsletter/unsubscribe.php';
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
