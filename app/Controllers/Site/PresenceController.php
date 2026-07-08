<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\PresenceProvider;
use App\Models\PresenceRecord;

class PresenceController extends Controller
{
    /**
     * Middleware: mesmo padrão do módulo de EPI (PIN individual ou Admin).
     */
    private function requireUser(): array
    {
        $pinUser = PinAuthController::getLoggedUser();
        if ($pinUser) {
            if ($pinUser['role'] !== 'epi' && $pinUser['role'] !== 'all') {
                http_response_code(403);
                echo '<h1>Sem permissão</h1><p>Você não tem acesso a esta funcionalidade.</p>';
                exit;
            }
            $_SESSION['user_id'] = $pinUser['id'];
            $_SESSION['user_name'] = $pinUser['name'];
            $_SESSION['user_email'] = $pinUser['email'] ?? '';
            $_SESSION['user_role'] = 'epi';
            $_SESSION['pin_auth'] = true;
            return [
                'id' => $pinUser['id'],
                'name' => $pinUser['name'],
                'email' => $pinUser['email'] ?? '',
                'role' => 'epi',
            ];
        }
        if (\App\Core\Auth::check()) {
            return \App\Core\Auth::user();
        }
        $this->redirect('/pin/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/lista-de-presenca'));
        exit;
    }

    // ===================================================================
    // /lista-de-presenca  — registro de presença
    // ===================================================================

    public function index(): void
    {
        $user = $this->requireUser();
        $this->view('site.presence.index', [
            'user' => $user,
            'sites' => PresenceRecord::distinctSites(),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Busca dinâmica de prestadores (JSON).
     */
    public function searchProviders(): void
    {
        $this->requireUser();
        $term = trim($this->input('q', ''));
        if (strlen($term) < 1) { $this->json(['providers' => []]); return; }

        $providers = PresenceProvider::search($term);
        $this->json(['providers' => array_map(fn($p) => [
            'id' => (int) $p['id'],
            'name' => $p['name'],
            'document' => $p['document'],
            'company' => $p['company'],
            'role' => $p['role'],
            'phone' => $p['phone'],
        ], $providers)]);
    }

    /**
     * Cadastro rápido de prestador (JSON). Estrutura mínima; será ampliada
     * conforme a planilha de referência.
     */
    public function storeProvider(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido'], 405); return; }

        $name = trim($this->input('name', ''));
        if ($name === '') { $this->json(['error' => 'Informe o nome do prestador.'], 400); return; }

        $id = PresenceProvider::create([
            'name' => $name,
            'document' => trim($this->input('document', '')) ?: null,
            'company' => trim($this->input('company', '')) ?: null,
            'role' => trim($this->input('role', '')) ?: null,
            'phone' => trim($this->input('phone', '')) ?: null,
            'notes' => trim($this->input('notes', '')) ?: null,
            'active' => 1,
            'created_by' => $user['name'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $provider = PresenceProvider::find($id);
        $this->json(['success' => true, 'provider' => [
            'id' => (int) $provider['id'],
            'name' => $provider['name'],
            'document' => $provider['document'],
            'company' => $provider['company'],
            'role' => $provider['role'],
            'phone' => $provider['phone'],
        ]]);
    }

    /**
     * Registrar presença.
     */
    public function store(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) { $this->redirect('/lista-de-presenca'); return; }

        $providerId = (int) $this->input('provider_id', 0) ?: null;
        $providerName = trim($this->input('provider_name', ''));
        $company = trim($this->input('company', ''));
        $site = trim($this->input('site', ''));
        $date = trim($this->input('presence_date', '')) ?: date('Y-m-d');
        $time = trim($this->input('presence_time', '')) ?: date('H:i');
        $notes = trim($this->input('notes', ''));
        $signature = $this->saveDataUrlImage($this->input('signature_data', ''), 'presence');

        $errors = [];
        if ($providerName === '') $errors[] = 'Prestador';
        if ($site === '') $errors[] = 'Obra';
        if (!$signature) $errors[] = 'Assinatura do prestador';

        if (!empty($errors)) {
            $this->setFlash('error', 'Preencha os campos obrigatórios: ' . implode(', ', $errors) . '.');
            $this->redirect('/lista-de-presenca');
            return;
        }

        PresenceRecord::create([
            'provider_id' => $providerId,
            'provider_name' => $providerName,
            'company' => $company ?: null,
            'site' => $site,
            'presence_date' => $date,
            'presence_time' => $time,
            'notes' => $notes ?: null,
            'signature_path' => $signature,
            'status' => 'registered',
            'created_by_id' => $user['id'] ?? null,
            'created_by_name' => $user['name'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', "Presença registrada para {$providerName}.");
        $this->redirect('/lista-de-presenca');
    }

    // ===================================================================
    // /historico-presenca  — consulta/histórico
    // ===================================================================

    public function history(): void
    {
        $user = $this->requireUser();

        $filters = [
            'site' => trim($this->input('site', '')),
            'company' => trim($this->input('company', '')),
            'provider' => trim($this->input('provider', '')),
            'date_from' => trim($this->input('date_from', '')),
            'date_to' => trim($this->input('date_to', '')),
        ];

        $records = PresenceRecord::filter($filters);

        $this->view('site.presence.history', [
            'user' => $user,
            'records' => $records,
            'filters' => $filters,
            'sites' => PresenceRecord::distinctSites(),
            'flash' => $this->getFlash(),
        ]);
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function saveDataUrlImage(string $dataUrl, string $prefix): ?string
    {
        if (empty($dataUrl) || strpos($dataUrl, 'data:image') !== 0) {
            return null;
        }
        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $dataUrl, $m)) {
            return null;
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false) return null;

        $uploadDir = ROOT_PATH . '/public/uploads/presence/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, $binary) === false) return null;

        return '/uploads/presence/' . $filename;
    }
}
