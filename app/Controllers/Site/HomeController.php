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

    public function matterport(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/matterport.php';
    }

    public function cultura(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/cultura.php';
    }

    public function trabalheConosco(): void
    {
        try {
            $settings = Setting::getGroup('site_');
        } catch (\Exception $e) {
            $settings = [];
        }

        include ROOT_PATH . '/app/Views/site/pages/trabalhe-conosco.php';
    }

    public function trabalheConoscoStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /trabalhe-conosco');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Upload do currículo
        $resumePath = null;
        $resumeFullPath = null;
        if (!empty($_FILES['resume']['tmp_name']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowedExt) && $_FILES['resume']['size'] <= 5 * 1024 * 1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/curriculos';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                move_uploaded_file($_FILES['resume']['tmp_name'], $uploadDir . '/' . $filename);
                $resumePath = '/uploads/curriculos/' . $filename;
                $resumeFullPath = $uploadDir . '/' . $filename;
            }
        }

        // Enviar e-mail para contato@brooksconstrutora.com.br
        try {
            $mailService = new \App\Services\MailService();
            
            $subject = "Novo currículo recebido - {$name} ({$area})";
            $resumeFullUrl = $resumePath ? (($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'brooksconstrutora.com.br') . $resumePath) : null;
            $body = \App\Services\EmailTemplate::resumeReceived($name, $email, $phone, $area, $message, $resumeFullUrl);
            
            $mailService->send('contato@brooksconstrutora.com.br', $subject, $body, true);
        } catch (\Exception $e) {
            // Se o SMTP falhar, tenta com mail() nativo
            $plainBody = "Nome: {$name}\nE-mail: {$email}\nTelefone: {$phone}\nÁrea: {$area}\nMensagem: {$message}\n";
            @mail('contato@brooksconstrutora.com.br', "Novo currículo - {$name} ({$area})", $plainBody, "From: {$email}\r\nReply-To: {$email}");
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Currículo enviado com sucesso! Analisaremos seu perfil e entraremos em contato caso surja uma oportunidade compatível.'];
        header('Location: /trabalhe-conosco');
        exit;
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
