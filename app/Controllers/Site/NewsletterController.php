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
     * Página pra assinante atualizar o WhatsApp (via token único ou link global)
     */
    public function updatePhone(string $token = ''): void
    {
        // Se não tem token, é o link global
        if (empty($token)) {
            $subscriber = null;
            $success = false;
            $errorMsg = '';
            $isGlobal = true;

            if ($this->isPost()) {
                $email = trim($this->input('email', ''));
                $phone = trim($this->input('phone', ''));
                $name = trim($this->input('name', ''));

                if (!empty($email) && !empty($phone)) {
                    $phone = preg_replace('/\D/', '', $phone);
                    if (strlen($phone) < 10) {
                        $errorMsg = 'Número inválido. Use DDD + número.';
                    } else {
                        $subscriber = Newsletter::findByEmail($email);
                        if ($subscriber) {
                            // Atualizar telefone do assinante existente
                            \App\Core\Database::update('newsletter_subscribers', ['phone' => $phone], 'id = ?', [$subscriber['id']]);
                            $subscriber['phone'] = $phone;
                            $success = true;
                        } else {
                            // Cadastro novo (email não encontrado)
                            Newsletter::subscribe($email, $name, $phone);
                            $subscriber = Newsletter::findByEmail($email);
                            $success = true;
                        }
                    }
                } elseif (!empty($email)) {
                    $errorMsg = 'Informe seu WhatsApp.';
                }
            }

            include ROOT_PATH . '/app/Views/site/newsletter/update_phone.php';
            return;
        }

        // Com token — identifica direto
        $subscriber = Newsletter::findByToken($token);
        $success = false;
        $errorMsg = '';
        $isGlobal = false;

        if ($subscriber && $this->isPost()) {
            $phone = trim($this->input('phone', ''));
            if (empty($phone)) {
                $errorMsg = 'Informe seu WhatsApp.';
            } else {
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

    /**
     * Verificar se email existe na newsletter (AJAX)
     */
    public function checkEmail(): void
    {
        $email = trim($this->input('email', ''));
        if (empty($email)) {
            $this->json(['found' => false]);
            return;
        }
        $subscriber = Newsletter::findByEmail($email);
        if ($subscriber) {
            $this->json(['found' => true, 'name' => $subscriber['name'] ?? '']);
        } else {
            $this->json(['found' => false]);
        }
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
                $result = Newsletter::unsubscribe($email);
                $success = true;
                $message = 'Sua inscrição foi cancelada com sucesso. Você não receberá mais nossos e-mails.';
            }
        }

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
