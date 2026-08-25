<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Setting;
use App\Services\NiboService;

/**
 * Área de desenvolvimento/testes de integrações.
 * /admin/dev/nibo — testador da API do Nibo (request/resposta por rota).
 */
class DevController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
        // Apenas super admin acessa a área de dev
        if (!Auth::isSuperAdmin()) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Página do testador da API do Nibo.
     */
    public function nibo(): void
    {
        $catalog = NiboService::catalog();

        // Agrupa por 'group' para exibição
        $groups = [];
        foreach ($catalog as $ep) {
            $groups[$ep['group']][] = $ep;
        }

        $this->view('admin.dev.nibo', [
            'groups' => $groups,
            'baseUrl' => NiboService::baseUrl(),
            'hasToken' => NiboService::token() !== '',
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Testador API Nibo',
            'currentPage' => 'dev_nibo',
        ]);
    }

    /**
     * Salva o token da API do Nibo (settings).
     */
    public function niboSaveToken(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/dev/nibo');
            return;
        }
        Setting::set('nibo_api_token', trim($this->input('nibo_api_token', '')));
        $this->setFlash('success', 'Token do Nibo salvo.');
        $this->redirect('/admin/dev/nibo');
    }

    /**
     * Executa uma chamada de teste à API do Nibo e retorna JSON com
     * request e resposta (usado pelos botões "Testar" da página).
     */
    public function niboTest(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método inválido.'], 400);
            return;
        }

        $key = $this->input('key', '');
        $endpoint = NiboService::findEndpoint($key);
        if (!$endpoint) {
            $this->json(['ok' => false, 'error' => 'Endpoint desconhecido.'], 404);
            return;
        }

        // Token: usa o informado no teste ou o salvo em settings
        $token = trim($this->input('token', '')) ?: null;

        // Resolve os parâmetros de path: {id} e/ou {scheduleId}, {fileId}, {annotationId}
        $path = $endpoint['path'];
        if (preg_match_all('/\{(\w+)\}/', $path, $matches)) {
            $paramsInput = [];
            $paramsRaw = $this->input('params', '');
            if ($paramsRaw !== '') {
                $decoded = json_decode($paramsRaw, true);
                if (is_array($decoded)) $paramsInput = $decoded;
            }
            // compat: campo simples "id" para endpoints com {id}
            if (!isset($paramsInput['id'])) {
                $simpleId = trim($this->input('id', ''));
                if ($simpleId !== '') $paramsInput['id'] = $simpleId;
            }

            foreach ($matches[1] as $paramName) {
                $val = isset($paramsInput[$paramName]) ? trim((string) $paramsInput[$paramName]) : '';
                if ($val === '') {
                    $this->json(['ok' => false, 'error' => "Parâmetro obrigatório ausente: {$paramName}."], 422);
                    return;
                }
                $path = str_replace('{' . $paramName . '}', rawurlencode($val), $path);
            }
        }

        // Query (OData) — recebido como JSON string do front
        $query = [];
        $queryRaw = $this->input('query', '');
        if ($queryRaw !== '') {
            $decoded = json_decode($queryRaw, true);
            if (is_array($decoded)) $query = $decoded;
        }

        // Body (POST/PUT) — recebido como JSON string do front
        $body = null;
        if (in_array($endpoint['method'], ['POST', 'PUT', 'PATCH'])) {
            $bodyRaw = $this->input('body', '');
            if ($bodyRaw !== '') {
                $decoded = json_decode($bodyRaw, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    $this->json(['ok' => false, 'error' => 'JSON do corpo inválido: ' . json_last_error_msg()], 422);
                    return;
                }
                $body = $decoded;
            }
        }

        $result = NiboService::request($endpoint['method'], $path, $query, $body, $token);
        $this->json($result);
    }
}
