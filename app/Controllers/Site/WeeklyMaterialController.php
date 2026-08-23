<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\WeeklyMaterialRequest;
use App\Models\WeeklyMaterialLog;
use App\Models\ConstructionSite;
use App\Models\Material;
use App\Services\WeeklyMaterialService;

class WeeklyMaterialController extends Controller
{
    /**
     * Formulário público de preenchimento (via token).
     * Reutiliza os campos do Novo Pedido (obra, urgência, prazo, itens).
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

        // Já preenchido → confirmação com o pedido gerado
        if ($request['status'] === 'filled') {
            $items = WeeklyMaterialRequest::getItems($request['id']);
            $order = !empty($request['order_id']) ? \App\Models\PurchaseOrder::find((int) $request['order_id']) : null;
            require ROOT_PATH . '/app/Views/site/weekly_materials/filled.php';
            return;
        }

        // Registrar abertura do formulário (uma vez)
        WeeklyMaterialRequest::markOpened((int) $request['id']);
        WeeklyMaterialLog::record(
            WeeklyMaterialLog::ACTION_FORM_OPENED,
            (int) $request['id'],
            'Formulário aberto pelo responsável',
            $request['week_start'] ?? null
        );

        // Materiais para autocomplete e obras vinculadas ao responsável
        $materials = Material::allActive();
        $sites = WeeklyMaterialRequest::sitesForManager((int) $request['manager_id']);
        // Se o responsável não tem obras vinculadas, oferece todas as ativas
        if (empty($sites)) {
            $sites = ConstructionSite::allActive();
        }
        $preselectedSite = $request['construction_site_id'] ?? null;

        require ROOT_PATH . '/app/Views/site/weekly_materials/form.php';
    }

    /**
     * Processar envio do formulário → cria um Pedido real no sistema existente.
     */
    public function submit(string $token = ''): void
    {
        if (!$this->isPost() || !$token) {
            $this->redirect('/');
            return;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request) {
            $this->redirect('/');
            return;
        }

        // IDEMPOTÊNCIA: se já foi preenchido/tem pedido, apenas redireciona
        if ($request['status'] === 'filled' || !empty($request['order_id'])) {
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        $items = $_POST['items'] ?? [];
        $notes = trim($this->input('notes', ''));
        $urgency = $this->input('urgency', 'medium');
        if (!in_array($urgency, ['low', 'medium', 'high', 'critical'])) $urgency = 'medium';
        $neededDate = $this->input('needed_date', '') ?: null;
        $siteId = $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : ($request['construction_site_id'] ?? null);

        // Motivos/justificativa de urgência (PARTE 10/13)
        $reasonNoAdvance = !empty($_POST['urgency_reason_no_advance']) ? 1 : 0;
        $reasonOccurrence = !empty($_POST['urgency_reason_site_occurrence']) ? 1 : 0;
        $urgencyDescription = trim($this->input('urgency_description', ''));

        $validItems = array_filter($items, fn($item) => !empty(trim($item['material_name'] ?? '')));

        // Validações
        if (empty($validItems)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Adicione pelo menos um material.'];
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        if (in_array($urgency, ['high', 'critical'])) {
            if ((!$reasonNoAdvance && !$reasonOccurrence) || empty($urgencyDescription)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Solicitações com urgência Alta ou Crítica precisam informar o motivo da urgência e sua descrição.'];
                header('Location: /lista-semanal/' . $token);
                exit;
            }
        }

        // Persistir dados de controle na solicitação ANTES de criar o pedido
        // (garante que nada é perdido mesmo se a criação do pedido falhar)
        WeeklyMaterialRequest::saveItems((int) $request['id'], $validItems);
        WeeklyMaterialRequest::updateById((int) $request['id'], [
            'construction_site_id' => $siteId,
            'urgency' => $urgency,
            'needed_date' => $neededDate,
            'urgency_reason_no_advance' => $reasonNoAdvance,
            'urgency_reason_site_occurrence' => $reasonOccurrence,
            'urgency_description' => $urgencyDescription ?: null,
            'notes' => $notes ?: null,
        ]);

        WeeklyMaterialLog::record(
            WeeklyMaterialLog::ACTION_FORM_SUBMITTED,
            (int) $request['id'],
            'Formulário enviado pelo responsável',
            $request['week_start'] ?? null
        );

        // Upload de áudio (mantém funcionalidade existente)
        $audioFilename = null;
        if (!empty($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/public/uploads/weekly-materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $audioFilename = 'wm_' . $request['id'] . '_' . time() . '.webm';
            move_uploaded_file($_FILES['audio']['tmp_name'], $uploadDir . $audioFilename);
            WeeklyMaterialRequest::updateById((int) $request['id'], ['audio_filename' => $audioFilename]);
        }

        // Recarregar solicitação atualizada (com site) para o service
        $freshRequest = WeeklyMaterialRequest::findByToken($token);

        // CRIAÇÃO DO PEDIDO — ponto único, idempotente
        $result = WeeklyMaterialService::createOrderFromRequest($freshRequest, $validItems, [
            'urgency' => $urgency,
            'needed_date' => $neededDate,
            'deadline' => $neededDate,
            'notes' => $notes,
            'urgency_reason_no_advance' => $reasonNoAdvance,
            'urgency_reason_site_occurrence' => $reasonOccurrence,
            'urgency_description' => $urgencyDescription,
        ]);

        if (!$result['success']) {
            // Não perder os dados; permitir nova tentativa (status permanece pendente)
            $_SESSION['flash'] = ['type' => 'error', 'message' => $result['error'] ?? 'Erro ao gerar o pedido. Tente novamente.'];
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        // Só agora marca como PREENCHIDO (order_id confirmado)
        WeeklyMaterialRequest::markFilled((int) $request['id'], (int) $result['order_id'], $notes ?: null, $audioFilename);

        // Enviar o pedido para o fluxo de cotação existente (notificações)
        if (!$result['duplicated'] && !empty($result['quote_token'])) {
            try {
                $this->notifyQuote((int) $result['order_id'], $result['quote_token']);
            } catch (\Throwable $e) {
                error_log('[WEEKLY_MATERIAL] Falha ao notificar cotação: ' . $e->getMessage());
            }
        }

        header('Location: /lista-semanal/' . $token);
        exit;
    }

    /**
     * Dispara as notificações de cotação reutilizando o fluxo do sistema de pedidos.
     */
    private function notifyQuote(int $orderId, string $quoteToken): void
    {
        $poController = new \App\Controllers\Site\PurchaseOrderController();
        if (method_exists($poController, 'sendQuoteNotifications')) {
            $poController->sendQuoteNotifications($orderId, $quoteToken);
        }
    }

    private function show404(): void
    {
        http_response_code(404);
        echo '<h1>Página não encontrada</h1><p>Link inválido ou expirado.</p>';
    }
}
