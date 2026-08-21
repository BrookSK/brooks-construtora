<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\WeeklyMaterialRequest;

class WeeklyMaterialController extends Controller
{
    /**
     * Formulário público de preenchimento (via token)
     */
    public function form(string $token = ''): void
    {
        if (!$token) {
            $this->show404();
            return;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request) {
            $this->show404();
            return;
        }

        // Se já preencheu, mostrar confirmação
        if ($request['status'] === 'filled') {
            $items = WeeklyMaterialRequest::getItems($request['id']);
            require ROOT_PATH . '/app/Views/site/weekly_materials/filled.php';
            return;
        }

        // Carregar materiais para autocomplete
        $materials = \App\Models\Material::allActive();

        require ROOT_PATH . '/app/Views/site/weekly_materials/form.php';
    }

    /**
     * Processar envio do formulário
     */
    public function submit(string $token = ''): void
    {
        if (!$this->isPost() || !$token) {
            $this->redirect('/');
            return;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request || $request['status'] === 'filled') {
            $this->redirect('/');
            return;
        }

        $items = $_POST['items'] ?? [];
        $notes = trim($this->input('notes', ''));

        // Filtrar itens vazios
        $validItems = array_filter($items, fn($item) => !empty(trim($item['material_name'] ?? '')));

        if (empty($validItems)) {
            // Redireciona de volta com erro (via session flash)
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Adicione pelo menos um material.'];
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        // Salvar itens
        WeeklyMaterialRequest::saveItems($request['id'], $validItems);

        // Upload de áudio (se enviado)
        $audioFilename = null;
        if (!empty($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/public/uploads/weekly-materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = 'webm';
            $audioFilename = 'wm_' . $request['id'] . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['audio']['tmp_name'], $uploadDir . $audioFilename);
        }

        // Marcar como preenchido
        WeeklyMaterialRequest::markFilled($request['id'], $notes ?: null, $audioFilename);

        // Redirecionar para confirmação
        header('Location: /lista-semanal/' . $token);
        exit;
    }

    private function show404(): void
    {
        http_response_code(404);
        echo '<h1>Página não encontrada</h1><p>Link inválido ou expirado.</p>';
    }
}
