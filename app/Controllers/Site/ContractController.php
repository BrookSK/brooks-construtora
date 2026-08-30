<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\GeneratedContract;
use App\Services\ContractValidator;

/**
 * Edição pública do contrato via link compartilhável (sem login).
 * Usado pelo jurídico externo para ajustar e salvar o texto do contrato.
 */
class ContractController extends Controller
{
    /**
     * Abre o editor público a partir do token.
     */
    public function edit(string $token = ''): void
    {
        $token = trim($token ?: (string)$this->input('token', ''));
        $contract = $token !== '' ? GeneratedContract::findByShareToken($token) : null;

        if (!$contract) {
            http_response_code(404);
            $this->renderInvalid();
            return;
        }

        $this->view('site.contract_editor', [
            'contract'   => $contract,
            'token'      => $token,
            'validation' => json_decode($contract['validation_json'] ?? 'null', true),
        ]);
    }

    /**
     * Salva a edição feita pelo link público.
     */
    public function save(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido.'], 400); return; }

        $token = trim((string)$this->input('token', ''));
        $contract = $token !== '' ? GeneratedContract::findByShareToken($token) : null;
        if (!$contract) { $this->json(['error' => 'Link inválido ou desativado.'], 403); return; }

        $markdown = (string)$this->input('markdown', '');
        $proposal = json_decode($contract['extraction_json'] ?? '{}', true) ?: [];
        $complementary = json_decode($contract['complementary_json'] ?? '{}', true) ?: [];

        $validator = new ContractValidator();
        $validation = $validator->validate($proposal, $complementary, $markdown);

        GeneratedContract::updateById((int)$contract['id'], [
            'contract_markdown' => $markdown,
            'validation_json'   => json_encode($validation, JSON_UNESCAPED_UNICODE),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'validation' => $validation]);
    }

    private function renderInvalid(): void
    {
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Link indisponível</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light">'
            . '<div class="container" style="max-width:520px; margin-top:12vh;">'
            . '<div class="card shadow-sm"><div class="card-body text-center p-4">'
            . '<h5 class="mb-3">Link indisponível</h5>'
            . '<p class="text-muted">Este link de edição não existe, expirou ou foi desativado pelo responsável. '
            . 'Solicite um novo link a quem o compartilhou.</p>'
            . '</div></div></div></body></html>';
    }
}
