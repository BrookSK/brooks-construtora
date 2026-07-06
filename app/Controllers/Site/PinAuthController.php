<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PinUser;

class PinAuthController extends Controller
{
    /**
     * Tela de login por PIN (intercepta quando não está logado)
     */
    public function login(): void
    {
        // Se já tem sessão válida, redireciona para onde veio
        $user = self::getLoggedUser();
        if ($user) {
            $redirect = $_GET['redirect'] ?? '/pedidos';
            $this->redirect($redirect);
            return;
        }

        $this->view('site.pin.login', [
            'redirect' => $_GET['redirect'] ?? '',
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar login por PIN
     */
    public function authenticate(): void
    {
        if (!$this->isPost()) { $this->redirect('/pin/login'); return; }

        $pin = trim($this->input('pin', ''));
        $redirect = $this->input('redirect', '/pedidos');

        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            $this->setFlash('error', 'PIN deve ter 4 dígitos numéricos.');
            $this->redirect('/pin/login?redirect=' . urlencode($redirect));
            return;
        }

        $user = PinUser::findByPin($pin);
        if (!$user) {
            // Incrementar tentativas
            $_SESSION['pin_attempts'] = ($_SESSION['pin_attempts'] ?? 0) + 1;
            $attempts = $_SESSION['pin_attempts'];
            $msg = 'PIN não encontrado.';
            if ($attempts >= 3) $msg .= ' Use a recuperação por e-mail abaixo.';
            $this->setFlash('error', $msg);
            $this->redirect('/pin/login?redirect=' . urlencode($redirect));
            return;
        }

        // Login bem-sucedido — reseta tentativas
        $_SESSION['pin_attempts'] = 0;

        // Criar sessão de 30 dias
        $token = PinUser::createSession($user['id']);

        setcookie('pin_session', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);

        // Redireciona para área correta com base no role
        if ($redirect === '/pedidos' && $user['role'] === 'epi') {
            $redirect = '/registro-de-entrega';
        }

        $this->redirect($redirect);
    }

    /**
     * Recuperação de PIN por e-mail
     */
    public function recover(): void
    {
        if (!$this->isPost()) { $this->redirect('/pin/login'); return; }

        $email = trim($this->input('email', ''));
        if (empty($email)) {
            $this->setFlash('error', 'Informe seu e-mail.');
            $this->redirect('/pin/login');
            return;
        }

        $user = \App\Core\Database::fetch("SELECT * FROM pin_users WHERE email = ? AND active = 1", [$email]);

        if ($user) {
            // Envia e-mail com o PIN
            try {
                $mail = new \App\Services\MailService();
                $body = "<p>Olá <strong>{$user['name']}</strong>,</p>"
                    . "<p>Seu PIN de acesso ao sistema Brooks Construtora é:</p>"
                    . "<p style='font-size:2rem; font-weight:700; text-align:center; letter-spacing:10px; color:#3a3b4e;'>{$user['pin']}</p>"
                    . "<p>Use este PIN para acessar o sistema.</p>";
                $mail->send($email, 'Seu PIN de Acesso - Brooks Construtora', $body, true);
            } catch (\Exception $e) {
                // silencioso
            }
        }

        // Sempre mostra a mesma mensagem (segurança — não revela se e-mail existe)
        $_SESSION['pin_attempts'] = 0;
        $this->setFlash('success', 'Se o e-mail estiver cadastrado, você receberá seu PIN em instantes.');
        $this->redirect('/pin/login');
    }

    /**
     * Tela de cadastro via link de convite
     */
    public function register(string $inviteToken = ''): void
    {
        if (empty($inviteToken)) { $this->show404(); return; }

        $invite = Database::fetch("SELECT * FROM pin_invite_links WHERE token = ? AND (expires_at IS NULL OR expires_at > NOW())", [$inviteToken]);
        if (!$invite) {
            $this->view('site.pin.invite_invalid', []);
            return;
        }

        if ($invite['max_uses'] && $invite['uses'] >= $invite['max_uses']) {
            $this->view('site.pin.invite_invalid', ['message' => 'Este convite já atingiu o limite de usos.']);
            return;
        }

        $roleLabels = ['buyer'=>'Comprador (criar pedidos)','quoter'=>'Cotador (fazer orçamentos)','approver'=>'Aprovador (aprovar pedidos)','payment'=>'Financeiro (NF/Boleto)','delivery'=>'Entrega (checklist)','epi'=>'EPI (controle de EPIs)','all'=>'Acesso completo'];

        $this->view('site.pin.register', [
            'invite' => $invite,
            'roleLabel' => $roleLabels[$invite['role']] ?? $invite['role'],
            'inviteToken' => $inviteToken,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Processar cadastro
     */
    public function store(): void
    {
        if (!$this->isPost()) { $this->redirect('/'); return; }

        $inviteToken = $this->input('invite_token', '');
        $invite = Database::fetch("SELECT * FROM pin_invite_links WHERE token = ?", [$inviteToken]);
        if (!$invite) { $this->show404(); return; }

        $name = trim($this->input('name', ''));
        $email = trim($this->input('email', ''));
        $pin = trim($this->input('pin', ''));
        $recovery = trim($this->input('recovery_phrase', ''));

        if (empty($name) || strlen($pin) !== 4 || !ctype_digit($pin)) {
            $this->setFlash('error', 'Preencha o nome e um PIN de 4 dígitos numéricos.');
            $this->redirect('/pin/cadastro/' . $inviteToken);
            return;
        }

        if (!PinUser::isPinAvailable($pin)) {
            $this->setFlash('error', 'Este PIN já está em uso. Escolha outro.');
            $this->redirect('/pin/cadastro/' . $inviteToken);
            return;
        }

        $userId = PinUser::create([
            'name' => $name,
            'email' => $email ?: null,
            'pin' => $pin,
            'role' => $invite['role'],
            'recovery_phrase' => $recovery ?: null,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Incrementar uso do convite
        Database::query("UPDATE pin_invite_links SET uses = uses + 1 WHERE id = ?", [$invite['id']]);

        // Auto-login
        $token = PinUser::createSession($userId);
        setcookie('pin_session', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);

        $this->setFlash('success', "Cadastro realizado! Seu PIN é: {$pin}. Memorize-o.");
        $redirectAfter = $invite['role'] === 'epi' ? '/registro-de-entrega' : '/pedidos';
        $this->redirect($redirectAfter);
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        setcookie('pin_session', '', ['expires' => time() - 3600, 'path' => '/']);
        $this->redirect('/pin/login');
    }

    /**
     * Verifica se o usuário está logado por PIN (estático, usado como middleware)
     */
    public static function getLoggedUser(): ?array
    {
        $token = $_COOKIE['pin_session'] ?? '';
        if (empty($token)) return null;
        return PinUser::findBySessionToken($token);
    }

    /**
     * Middleware: requer login por PIN, redireciona se não logado
     */
    public static function requireAuth(?string $requiredRole = null): ?array
    {
        $user = self::getLoggedUser();
        if (!$user) {
            $redirect = $_SERVER['REQUEST_URI'] ?? '/pedidos';
            header('Location: /pin/login?redirect=' . urlencode($redirect));
            exit;
        }
        if ($requiredRole && !PinUser::hasPermission($user, $requiredRole)) {
            http_response_code(403);
            echo '<h1>Sem permissão</h1><p>Você não tem acesso a esta funcionalidade.</p>';
            exit;
        }
        return $user;
    }

    private function show404(): void
    {
        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
        exit;
    }
}
