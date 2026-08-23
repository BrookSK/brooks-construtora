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
        try {
            $this->renderForm($token);
        } catch (\Throwable $e) {
            // Log detalhado para diagnóstico
            error_log('[WEEKLY_MATERIAL][FORM] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            // Exibe uma página de erro legível (evita tela totalmente branca)
            http_response_code(500);
            $isDebug = isset($_GET['debug']);
            echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
                . '<title>Erro | Brooks Construtora</title>'
                . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
                . '<body style="background:#f4f6f9;font-family:sans-serif;">'
                . '<div class="container py-5" style="max-width:640px;">'
                . '<div class="card border-danger"><div class="card-body text-center py-5">'
                . '<h5 class="text-danger mb-3">Não foi possível carregar o formulário</h5>'
                . '<p class="text-muted">Tente novamente. Se persistir, avise o administrador.</p>';
            if ($isDebug) {
                echo '<hr><pre class="text-start small text-danger" style="white-space:pre-wrap;">'
                    . htmlspecialchars($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString())
                    . '</pre>';
            }
            echo '</div></div></div></body></html>';
        }
    }

    /**
     * Renderiza o formulário público de preenchimento (via token).
     * Reutiliza os campos do Novo Pedido (obra, prazo, itens).
     */
    private function renderForm(string $token = ''): void
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

        // Registrar abertura do formulário (uma vez). Não deve quebrar a
        // página caso a estrutura de log/colunas ainda não exista.
        try {
            WeeklyMaterialRequest::markOpened((int) $request['id']);
            WeeklyMaterialLog::record(
                WeeklyMaterialLog::ACTION_FORM_OPENED,
                (int) $request['id'],
                'Formulário aberto pelo responsável',
                $request['week_start'] ?? null
            );
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao registrar abertura: ' . $e->getMessage());
        }

        // Materiais para autocomplete (mesma base do Novo Pedido)
        $materials = Material::allActive();

        // EPIs também disponíveis (itens não vinculados, material_id nulo)
        try {
            foreach (\App\Models\Epi::allActive() as $epi) {
                $materials[] = [
                    'id' => 'epi-' . $epi['id'],
                    'name' => $epi['name'],
                    'specification' => $epi['category'] ?? 'EPI',
                    'category_name' => $epi['category'] ?? 'EPI',
                    'classification' => !empty($epi['ca']) ? 'CA ' . $epi['ca'] : '',
                    'unit_abbr' => 'un',
                    'unit_name' => 'Unidade',
                    'is_epi' => true,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao carregar EPIs: ' . $e->getMessage());
        }

        // Categorias e unidades para o modal "Novo Material"
        $categories = \App\Models\MaterialCategory::all('name ASC');
        $units = \App\Models\MeasurementUnit::all('name ASC');

        // Obra do link: cada link é específico de uma obra. Se a solicitação
        // já tem obra definida, ela vem TRAVADA (read-only) no formulário.
        $preselectedSite = $request['construction_site_id'] ?? null;
        $lockedSite = null;
        if (!empty($preselectedSite)) {
            $lockedSite = \App\Models\ConstructionSite::find((int) $preselectedSite);
        }

        // Fallback: se o link não tem obra (responsável sem obra vinculada),
        // oferece o seletor com as obras dele (ou todas as ativas).
        $sites = [];
        if (!$lockedSite) {
            $sites = WeeklyMaterialRequest::sitesForManager((int) $request['manager_id']);
            if (empty($sites)) {
                $sites = ConstructionSite::allActive();
            }
        }

        // Configuração de antecedência mínima (regra dos 15 dias / PARTE 8)
        $minAdvanceDays = (int) \App\Models\Setting::get('weekly_min_advance_days', '15');

        // Data mínima e padrão = hoje + antecedência mínima. O responsável NÃO
        // pode selecionar uma data antes disso (previsão sempre com 15 dias).
        $minNeededDate = WeeklyMaterialRequest::defaultNeededDate();
        $defaultNeededDate = $minNeededDate;

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
        $neededDate = $this->input('needed_date', '') ?: null;
        $siteId = $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : ($request['construction_site_id'] ?? null);

        $validItems = array_filter($items, fn($item) => !empty(trim($item['material_name'] ?? '')));

        // Validações
        if (empty($validItems)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Adicione pelo menos um material.'];
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        // A data de necessidade deve respeitar a antecedência mínima (15 dias).
        // Se vier antes do mínimo (ou vazia), força para o mínimo.
        $minNeededDate = WeeklyMaterialRequest::defaultNeededDate();
        if (empty($neededDate) || $neededDate < $minNeededDate) {
            $neededDate = $minNeededDate;
        }

        // Urgência é DERIVADA da antecedência (autoridade no servidor).
        $urgency = self::deriveUrgency($neededDate);

        // Persistir dados de controle na solicitação ANTES de criar o pedido
        // (garante que nada é perdido mesmo se a criação do pedido falhar)
        WeeklyMaterialRequest::saveItems((int) $request['id'], $validItems);
        WeeklyMaterialRequest::updateById((int) $request['id'], [
            'construction_site_id' => $siteId,
            'urgency' => $urgency,
            'needed_date' => $neededDate,
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
     * Importar materiais de PDF/imagem via IA (endpoint público via token).
     * Reutiliza o MaterialParserService (mesma lógica do Novo Pedido).
     */
    public function parsePdf(string $token = ''): void
    {
        header('Content-Type: application/json');

        if (!$this->isPost() || !$token) {
            echo json_encode(['error' => 'Requisição inválida.']);
            exit;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request) {
            echo json_encode(['error' => 'Link inválido.']);
            exit;
        }

        if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Erro no upload do arquivo.']);
            exit;
        }

        try {
            $result = \App\Services\MaterialParserService::parseUploadedFile($_FILES['pdf']);
        } catch (\Throwable $e) {
            $result = ['error' => 'Erro: ' . $e->getMessage()];
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Cadastro rápido de material (endpoint público via token).
     * Reutiliza o Model Material (mesma tabela do sistema).
     */
    public function quickStoreMaterial(string $token = ''): void
    {
        header('Content-Type: application/json');

        if (!$this->isPost() || !$token) {
            echo json_encode(['success' => false, 'error' => 'Requisição inválida.']);
            exit;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request) {
            echo json_encode(['success' => false, 'error' => 'Link inválido.']);
            exit;
        }

        $name = trim($this->input('name', ''));
        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Nome é obrigatório.']);
            exit;
        }

        $id = Material::create([
            'name' => $name,
            'specification' => trim($this->input('specification', '')),
            'category_id' => (int) $this->input('category_id') ?: null,
            'unit_id' => (int) $this->input('unit_id') ?: null,
            'classification' => trim($this->input('classification', '')),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => true, 'material' => Material::find($id)]);
        exit;
    }

    /**
     * Deriva a urgência a partir da antecedência (dias até a necessidade),
     * usando a antecedência mínima configurada (padrão 15 dias).
     * Mesma regra do cálculo exibido no formulário.
     */
    private static function deriveUrgency(?string $neededDate): string
    {
        if (empty($neededDate)) return 'medium';
        $minAdvance = (int) \App\Models\Setting::get('weekly_min_advance_days', '15');
        $days = \App\Models\WeeklyMaterialRequest::calcAntecedence($neededDate);
        if ($days === null) return 'medium';
        if ($days <= 3) return 'critical';
        if ($days < $minAdvance) return 'high';
        if ($days <= $minAdvance + 7) return 'medium';
        return 'low';
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
