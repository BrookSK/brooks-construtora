<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\WeeklyMaterialRequest;
use App\Models\WeeklyMaterialLog;
use App\Models\ConstructionSite;
use App\Models\Material;
use App\Models\MaterialTemplate;
use App\Models\MaterialTemplateItem;
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

        // Garante que manager_name nunca é null (pin_user pode ter sido deletado)
        if (empty($request['manager_name'])) {
            $request['manager_name'] = 'Responsável';
        }

        // Já respondido → tela de confirmação.
        //  - 'filled'   : preencheu e gerou pedido
        //  - 'no_items' : encerrou o ciclo sem itens (stand-by)
        if (in_array($request['status'], ['filled', 'no_items'], true)) {
            $noItems = $request['status'] === 'no_items';
            $items = $noItems ? [] : WeeklyMaterialRequest::getItems($request['id']);
            $order = (!$noItems && !empty($request['order_id']))
                ? \App\Models\PurchaseOrder::find((int) $request['order_id'])
                : null;
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

        // Antecedência MÍNIMA da necessidade (Z): a data "preciso até" deve
        // ser no mínimo hoje + Z dias.
        $minAdvanceDays = WeeklyMaterialRequest::minNeedDays();

        // Data mínima e padrão = hoje + antecedência mínima. O responsável NÃO
        // pode selecionar uma data antes disso (previsão sempre com 15 dias).
        $minNeededDate = WeeklyMaterialRequest::defaultNeededDate();
        $defaultNeededDate = $minNeededDate;

        // Rótulo do ciclo (número, semana do mês e intervalo de datas)
        $cycleLabel = WeeklyMaterialRequest::cycleLabel($request['week_start']);

        // Janela do ciclo (Y-m-d) para limitar a data opcional por item.
        // O máximo é o fim do ciclo; o mínimo é a data mínima da necessidade.
        $cycleEndDate = WeeklyMaterialRequest::cycleEnd($request['week_start']);

        // Listas de materiais pré-definidas (mesmas do Novo Pedido).
        // Permitem carregar de uma vez vários materiais já com quantidades
        // sugeridas. É apenas uma sugestão: o responsável revisa e ajusta.
        $materialLists = [];
        try {
            $materialLists = MaterialTemplate::allActive();
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao carregar listas pré-definidas: ' . $e->getMessage());
        }

        // Tipo de projeto da obra travada (para filtrar itens da lista por
        // construção/reforma, quando a obra do link é conhecida).
        $obraType = $lockedSite['project_type'] ?? '';

        require ROOT_PATH . '/app/Views/site/weekly_materials/form.php';
    }

    /**
     * HUB do responsável: página única com todas as obras do ciclo.
     * Acessada por um link único no e-mail (evita muitos links = spam no Gmail).
     */
    public function hub(string $hubToken = ''): void
    {
        if (!$hubToken) {
            $this->show404();
            return;
        }

        $hub = WeeklyMaterialRequest::findByHubToken($hubToken);
        if (!$hub) {
            $this->show404();
            return;
        }

        $requests = WeeklyMaterialRequest::getByManagerAndWeek($hub['manager_id'], $hub['week_start']);
        $cycleLabel = WeeklyMaterialRequest::cycleLabel($hub['week_start']);
        $managerName = $hub['manager_name'];

        require ROOT_PATH . '/app/Views/site/weekly_materials/hub.php';
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

        // IDEMPOTÊNCIA: só considera "já respondido" quando há de fato uma
        // resposta válida — encerrado sem itens, ou com um pedido que EXISTE.
        // Um order_id órfão (apontando para pedido inexistente) NÃO pode
        // prender o responsável na tela de confirmação: nesse caso deixamos o
        // fluxo seguir (que é idempotente e revincula/recria o pedido).
        $alreadyAnswered = $request['status'] === 'no_items';
        if (!$alreadyAnswered && !empty($request['order_id'])) {
            $existingOrder = \App\Models\PurchaseOrder::find((int) $request['order_id']);
            if ($existingOrder) {
                $alreadyAnswered = true;
            }
        }
        if (!$alreadyAnswered && $request['status'] === 'filled' && empty($request['order_id'])) {
            // Estado inconsistente (filled sem pedido): não trava; segue o fluxo.
            $alreadyAnswered = false;
        }
        if ($alreadyAnswered) {
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        // Modo de envio: "no_items" encerra o ciclo do link SEM gerar pedido.
        $submitMode = $this->input('submit_mode', 'normal');
        if ($submitMode === 'no_items') {
            $notes = trim($this->input('notes', '')) ?: null;
            // Transição condicional (só a partir de 'pending'): idempotente.
            $closed = WeeklyMaterialRequest::markNoItems((int) $request['id'], $notes);
            if ($closed) {
                WeeklyMaterialLog::record(
                    WeeklyMaterialLog::ACTION_CLOSED_NO_ITEMS,
                    (int) $request['id'],
                    'Responsável encerrou o ciclo sem itens a solicitar',
                    $request['week_start'] ?? null
                );
            }
            header('Location: /lista-semanal/' . $token);
            exit;
        }

        // Tipo do pedido: material (padrão) ou service.
        $orderType = $this->input('order_type', 'material');
        if (!in_array($orderType, ['material', 'service'], true)) {
            $orderType = 'material';
        }

        $items = $_POST['items'] ?? [];
        $notes = trim($this->input('notes', ''));
        $neededDate = $this->input('needed_date', '') ?: null;
        $siteId = $this->input('construction_site_id') ? (int) $this->input('construction_site_id') : ($request['construction_site_id'] ?? null);

        $validItems = array_filter($items, fn($item) => !empty(trim($item['material_name'] ?? '')));

        // Validações
        if (empty($validItems)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Adicione pelo menos um item.'];
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
            'order_type' => $orderType,
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

        // Rascunho já virou pedido: limpa o rascunho salvo no servidor.
        WeeklyMaterialRequest::clearDraftByToken($token);

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
     * Autosave do rascunho NO SERVIDOR (endpoint público via token).
     * Recebe o snapshot JSON do formulário e grava na solicitação.
     * Complementa o autosave em localStorage do navegador.
     */
    public function saveDraft(string $token = ''): void
    {
        header('Content-Type: application/json');

        if (!$this->isPost() || !$token) {
            echo json_encode(['success' => false, 'error' => 'Requisição inválida.']);
            exit;
        }

        // O corpo pode vir como JSON puro ou como campo de formulário "draft".
        $raw = file_get_contents('php://input');
        $json = '';
        if (!empty($_POST['draft'])) {
            $json = (string) $_POST['draft'];
        } elseif (!empty($raw)) {
            $json = $raw;
        }

        // Valida que é um JSON decodificável (evita gravar lixo).
        $decoded = json_decode($json, true);
        if ($json === '' || $decoded === null) {
            echo json_encode(['success' => false, 'error' => 'Rascunho vazio ou inválido.']);
            exit;
        }

        // Pedido explícito de limpeza, ou rascunho sem itens: apaga do servidor.
        $items = $decoded['items'] ?? [];
        $hasContent = false;
        foreach ((array) $items as $it) {
            if (!empty(trim($it['name'] ?? '')) || !empty(trim((string) ($it['id'] ?? '')))
                || !empty(trim($it['specification'] ?? ''))) {
                $hasContent = true;
                break;
            }
        }
        if (!empty($decoded['_cleared']) || !$hasContent) {
            WeeklyMaterialRequest::clearDraftByToken($token);
            echo json_encode(['success' => true, 'cleared' => true]);
            exit;
        }

        // Re-serializa de forma canônica (protege o tamanho e o conteúdo).
        $clean = json_encode($decoded, JSON_UNESCAPED_UNICODE);

        try {
            $savedAt = WeeklyMaterialRequest::saveDraftByToken($token, $clean);
            echo json_encode(['success' => $savedAt !== null, 'updated_at' => $savedAt]);
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao salvar rascunho: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar rascunho.']);
        }
        exit;
    }

    /**
     * Carrega o rascunho salvo no servidor (endpoint público via token).
     * Usado pelo front-end para mesclar com o localStorage e escolher o mais recente.
     */
    public function loadDraft(string $token = ''): void
    {
        header('Content-Type: application/json');

        if (!$token) {
            echo json_encode(['success' => false]);
            exit;
        }

        try {
            $draft = WeeklyMaterialRequest::getDraftByToken($token);
        } catch (\Throwable $e) {
            error_log('[WEEKLY_MATERIAL] Falha ao carregar rascunho: ' . $e->getMessage());
            $draft = null;
        }

        if (!$draft) {
            echo json_encode(['success' => true, 'draft' => null]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'draft' => json_decode($draft['data'], true),
            'updated_at' => $draft['updated_at'],
        ]);
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
     * AJAX (público via token): itens de uma lista de materiais pré-definida.
     *
     * Espelha Admin\MaterialTemplateController::items(), porém validando o
     * token do link semanal em vez de sessão de admin. Usado pelo formulário
     * de preenchimento da lista semanal para carregar de uma vez os materiais
     * de uma lista, já com quantidades sugeridas.
     */
    public function predefinedList(string $token = ''): void
    {
        header('Content-Type: application/json');

        if (!$token) {
            echo json_encode(['success' => false, 'error' => 'Link inválido.']);
            exit;
        }

        $request = WeeklyMaterialRequest::findByToken($token);
        if (!$request) {
            echo json_encode(['success' => false, 'error' => 'Link inválido.']);
            exit;
        }

        $templateId = (int) $this->input('template_id', 0);
        $template = MaterialTemplate::find($templateId);
        if (!$template) {
            echo json_encode(['success' => false, 'error' => 'Lista não encontrada.']);
            exit;
        }

        $rows = MaterialTemplateItem::forTemplate($templateId, true);

        // Filtra por tipo de projeto da obra, se informado.
        $obraType = $this->input('obra_type', '');
        if ($obraType === 'construction' || $obraType === 'renovation') {
            $rows = array_filter($rows, function ($item) use ($obraType) {
                $itemType = $item['project_type'] ?? 'both';
                return $itemType === 'both' || $itemType === $obraType;
            });
            $rows = array_values($rows);
        }

        $items = array_map(function ($i) {
            return [
                'id'             => $i['material_id'] !== null ? (int) $i['material_id'] : null,
                'item_id'        => (int) $i['id'],
                'name'           => $i['material_name'],
                'specification'  => $i['specification'] ?? ($i['category_name'] ?? ''),
                'classification' => $i['classification'] ?? '',
                'unit'           => $i['unit_abbr'] ?? $i['unit'] ?? '',
                'quantity'       => (float) $i['default_quantity'],
            ];
        }, $rows);

        echo json_encode([
            'success'  => true,
            'template' => ['id' => (int) $template['id'], 'name' => $template['name']],
            'items'    => $items,
            'filtered_by_obra_type' => $obraType ?: null,
        ]);
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
