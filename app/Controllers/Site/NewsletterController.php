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

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Informe um e-mail válido.'], 400);
            } else {
                $this->setFlash('error', 'Informe um e-mail válido.');
                $this->redirect('/');
            }
            return;
        }

        $result = Newsletter::subscribe($email, $name);

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

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
