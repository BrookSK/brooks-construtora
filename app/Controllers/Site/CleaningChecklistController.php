<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\CleaningChecklist;

class CleaningChecklistController extends Controller
{
    /**
     * Middleware: exige usuário logado (PIN ou Admin) com permissão de EPI.
     */
    private function requireUser(): array
    {
        $pinUser = PinAuthController::getLoggedUser();
        if ($pinUser) {
            if ($pinUser['role'] !== 'epi' && $pinUser['role'] !== 'all') {
                http_response_code(403);
                echo '<h1>Sem permissão</h1>';
                exit;
            }
            $_SESSION['user_id'] = $pinUser['id'];
            $_SESSION['user_name'] = $pinUser['name'];
            $_SESSION['user_email'] = $pinUser['email'] ?? '';
            $_SESSION['user_role'] = 'epi';
            $_SESSION['pin_auth'] = true;
            $_SESSION['pin_user_id'] = $pinUser['id'];
            $_SESSION['pin_user_role'] = $pinUser['role'];

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

        $this->redirect('/pin/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/checklist-limpeza'));
        exit;
    }

    /**
     * Histórico de checklists
     */
    public function index(): void
    {
        $user = $this->requireUser();
        $checklists = CleaningChecklist::allRecent();

        $this->view('site.cleaning.index', [
            'user' => $user,
            'checklists' => $checklists,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Formulário de novo checklist
     */
    public function create(): void
    {
        $user = $this->requireUser();
        $defaultItems = CleaningChecklist::getDefaultItems();

        $this->view('site.cleaning.create', [
            'user' => $user,
            'defaultItems' => $defaultItems,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Salvar checklist
     */
    public function store(): void
    {
        $user = $this->requireUser();
        if (!$this->isPost()) {
            $this->redirect('/checklist-limpeza/novo');
            return;
        }

        $performedAt = trim($this->input('performed_at', date('Y-m-d')));
        $responsibleName = trim($this->input('responsible_name', ''));
        $inspectorName = trim($this->input('inspector_name', ''));
        $sectors = $_POST['sectors'] ?? [];
        $observations = trim($this->input('observations', ''));
        $signatureData = $this->input('signature_data', '');

        // Validações
        $errors = [];
        if ($responsibleName === '') $errors[] = 'Responsável pela atividade';
        if (empty($sectors)) $errors[] = 'Ao menos um setor';

        if (!empty($errors)) {
            $this->setFlash('error', 'Preencha: ' . implode(', ', $errors));
            $this->redirect('/checklist-limpeza/novo');
            return;
        }

        // Coletar itens do checklist
        $items = [];
        $defaultItems = CleaningChecklist::getDefaultItems();
        foreach ($defaultItems as $sectorKey => $sector) {
            if (!in_array($sectorKey, $sectors)) continue;

            $sectorItems = [];
            foreach ($sector['items'] as $idx => $itemLabel) {
                $fieldName = "item_{$sectorKey}_{$idx}";
                $obsFieldName = "obs_{$sectorKey}_{$idx}";
                $status = $this->input($fieldName, 'na'); // c, nc, na
                $obs = trim($this->input($obsFieldName, ''));

                $sectorItems[] = [
                    'label' => $itemLabel,
                    'status' => $status,
                    'obs' => $obs,
                ];
            }
            $items[$sectorKey] = [
                'label' => $sector['label'],
                'items' => $sectorItems,
            ];
        }

        // Salvar assinatura
        $signaturePath = null;
        if ($signatureData && str_starts_with($signatureData, 'data:image')) {
            $signaturePath = $this->saveDataUrlImage($signatureData, 'cleaning_sign');
        }

        CleaningChecklist::create([
            'performed_at' => $performedAt,
            'responsible_name' => $responsibleName,
            'inspector_name' => $inspectorName ?: null,
            'sectors' => json_encode($sectors),
            'items' => json_encode($items),
            'signature_data' => $signaturePath,
            'user_id' => $user['id'],
            'observations' => $observations ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Checklist de limpeza registrado com sucesso!');
        $this->redirect('/checklist-limpeza');
    }

    /**
     * Visualizar checklist
     */
    public function show(string $id = ''): void
    {
        $user = $this->requireUser();
        $checklist = CleaningChecklist::find((int) $id);

        if (!$checklist) {
            $this->setFlash('error', 'Checklist não encontrado.');
            $this->redirect('/checklist-limpeza');
            return;
        }

        $this->view('site.cleaning.show', [
            'user' => $user,
            'checklist' => $checklist,
        ]);
    }

    /**
     * Helper para salvar imagem base64
     */
    private function saveDataUrlImage(string $dataUrl, string $prefix): ?string
    {
        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $dataUrl, $m)) {
            return null;
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $data = base64_decode($m[2]);
        if (!$data) return null;

        $dir = ROOT_PATH . '/public/uploads/cleaning';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        file_put_contents($dir . '/' . $filename, $data);

        return '/uploads/cleaning/' . $filename;
    }
}
