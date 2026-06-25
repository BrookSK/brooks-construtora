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
            $this->setFlash('error', 'PIN não encontrado. Verifique ou cadastre-se.');
            $this->redirect('/pin/login?redirect=' . urlencode($redirect));
            return;
        }

        // Criar sessão de 30 dias
        $token = PinUser::createSession($user['id']);

        // Cookie persistente de 30 dias
        setcookie('pin_session', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);

        $this->redirect($redirect);
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

        $roleLabels = ['buyer'=>'Comprador (criar pedidos)','quoter'=>'Cotador (fazer orçamentos)','approver'=>'Aprovador (aprovar pedidos)','payment'=>'Financeiro (NF/Boleto)','delivery'=>'Entrega (checklist)','all'=>'Acesso completo'];

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
        $this->redirect('/pedidos');
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
