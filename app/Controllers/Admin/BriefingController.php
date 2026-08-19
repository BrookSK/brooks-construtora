<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\ClientProject;
use App\Models\Briefing;
use App\Models\ContractTemplate;
use App\Models\ContractObject;
use App\Services\OpenAIService;

class BriefingController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
    }

    // ---------------------------------------------------------------
    // Executa a migration 033 via PDO caso as tabelas não existam.
    // Usa CREATE TABLE IF NOT EXISTS, então é seguro rodar múltiplas vezes.
    // ---------------------------------------------------------------
    private function ensureTables(): void
    {
        $sqlFile = ROOT_PATH . '/database/migrations/033_create_briefing_contracts.sql';
        if (!file_exists($sqlFile)) {
            return;
        }

        $raw = file_get_contents($sqlFile);

        // Remove comentários -- e /* */
        $raw = preg_replace('/--[^\n]*/', '', $raw);
        $raw = preg_replace('/\/\*.*?\*\//s', '', $raw);

        // Divide em statements respeitando strings delimitadas por ' e "
        $statements = [];
        $current    = '';
        $inString   = false;
        $strChar    = '';

        for ($i = 0, $len = strlen($raw); $i < $len; $i++) {
            $ch = $raw[$i];
            if (!$inString && ($ch === "'" || $ch === '"')) {
                $inString = true;
                $strChar  = $ch;
                $current .= $ch;
                continue;
            }
            if ($inString) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $current .= $ch . $raw[++$i];
                    continue;
                }
                if ($ch === $strChar) {
                    $inString = false;
                }
                $current .= $ch;
                continue;
            }
            if ($ch === ';') {
                $stmt = trim($current);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $current = '';
                continue;
            }
            $current .= $ch;
        }
        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        $pdo = Database::getConnection();
        foreach ($statements as $stmt) {
            if (trim($stmt) === '') {
                continue;
            }
            try {
                $pdo->exec($stmt);
            } catch (\PDOException $e) {
                // Ignora "already exists" e "Duplicate entry" — idempotente
                if (stripos($e->getMessage(), 'already exists') === false
                    && stripos($e->getMessage(), 'Duplicate entry') === false) {
                    // Loga silenciosamente; não interrompe o fluxo
                    error_log('[BriefingController] ensureTables error: ' . $e->getMessage());
                }
            }
        }
    }

    // ---------------------------------------------------------------
    // Listagem principal
    // ---------------------------------------------------------------

    public function index(): void
    {
        $statusFilter = trim($this->input('status', ''));

        try {
            $projects = ClientProject::allWithBriefing(100, $statusFilter);
        } catch (\PDOException $e) {
            if (stripos($e->getMessage(), "doesn't exist") !== false
                || stripos($e->getMessage(), 'exist') !== false) {
                // Tabelas ausentes — aplica a migration automaticamente
                $this->ensureTables();
                // Tenta de novo após criar as tabelas
                $projects = ClientProject::allWithBriefing(100, $statusFilter);
            } else {
                throw $e;
            }
        }

        $this->view('admin.briefing.index', [
            'user'         => Auth::user(),
            'flash'        => $this->getFlash(),
            'projects'     => $projects,
            'mode'         => 'list',
            'statusFilter' => $statusFilter,
        ]);
    }

    // ---------------------------------------------------------------
    // Criar novo briefing (abre view em modo novo)
    // ---------------------------------------------------------------

    public function create(): void
    {
        $templates = ContractTemplate::all('is_default DESC, id ASC');
        $defaultTemplate = ContractTemplate::getDefault();

        $this->view('admin.briefing.index', [
            'user'            => Auth::user(),
            'flash'           => $this->getFlash(),
            'mode'            => 'create',
            'templates'       => $templates,
            'defaultTemplate' => $defaultTemplate,
            'projects'        => ClientProject::allWithBriefing(100),
        ]);
    }

    // ---------------------------------------------------------------
    // Salvar novo projeto + briefing
    // ---------------------------------------------------------------

    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/briefing');
            return;
        }

        $userId = (int) Auth::id();

        // Higienização: remove pontuação de campos numéricos
        $clientDocument = preg_replace('/\D/', '', $this->input('client_document', ''));
        $clientPhone    = preg_replace('/\D/', '', $this->input('client_phone', ''));
        $projectCep     = preg_replace('/\D/', '', $this->input('project_cep', ''));

        // Validação de e-mail
        $clientEmail = trim($this->input('client_email', ''));
        if (!empty($clientEmail) && !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'O e-mail informado não é válido.');
            $this->redirect('/admin/briefing/create');
            return;
        }

        // Salva o projeto/cliente
        $projectId = ClientProject::create([
            'client_name'     => trim($this->input('client_name', '')),
            'client_document' => $clientDocument,
            'client_phone'    => $clientPhone,
            'client_email'    => $clientEmail,
            'project_type'    => trim($this->input('project_type', '')),
            'project_address' => trim($this->input('project_address', '')),
            'project_cep'     => $projectCep,
            'project_city'    => trim($this->input('project_city', '')),
            'project_goal'    => trim($this->input('project_goal', '')),
            'project_area'    => $this->input('project_area') ?: null,
            'created_by'      => $userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // Salva o briefing vinculado
        $briefingId = Briefing::create([
            'client_project_id'  => $projectId,
            'preferences'        => trim($this->input('preferences', '')),
            'priorities'         => trim($this->input('priorities', '')),
            'needs'              => trim($this->input('needs', '')),
            'restrictions'       => trim($this->input('restrictions', '')),
            'briefing_summary'   => trim($this->input('briefing_summary', '')),
            'negotiation_details'=> trim($this->input('negotiation_details', '')),
            'contract_value'     => $this->input('contract_value') ?: null,
            'down_payment'       => $this->input('down_payment') ?: null,
            'discount_value'     => $this->input('discount_value') ?: null,
            'discount_percent'   => $this->input('discount_percent') ?: null,
            'payment_installments'=> $this->input('payment_installments') ?: null,
            'payment_details'    => trim($this->input('payment_details', '')),
            'start_date'         => $this->input('start_date') ?: null,
            'end_date'           => $this->input('end_date') ?: null,
            'deadline_days'      => $this->input('deadline_days') ?: null,
            'clauses'            => trim($this->input('clauses', '')),
            'created_by'         => $userId,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->setFlash('success', 'Briefing salvo com sucesso!');
        $this->redirect('/admin/briefing/edit/' . $projectId);
    }

    // ---------------------------------------------------------------
    // Editar projeto existente (abre view preenchida)
    // ---------------------------------------------------------------

    public function edit(string $id = ''): void
    {
        $projectId = (int) ($id ?: $this->input('id'));
        $project   = ClientProject::find($projectId);

        if (!$project) {
            $this->setFlash('error', 'Projeto não encontrado.');
            $this->redirect('/admin/briefing');
            return;
        }

        $briefing         = Briefing::findByProject($projectId);
        $templates        = ContractTemplate::all('is_default DESC, id ASC');
        $defaultTemplate  = ContractTemplate::getDefault();
        $contractObject   = $briefing
            ? ContractObject::latestByBriefing((int) $briefing['id'])
            : null;

        $this->view('admin.briefing.index', [
            'user'            => Auth::user(),
            'flash'           => $this->getFlash(),
            'mode'            => 'edit',
            'project'         => $project,
            'briefing'        => $briefing,
            'templates'       => $templates,
            'defaultTemplate' => $defaultTemplate,
            'contractObject'  => $contractObject,
            'projects'        => ClientProject::allWithBriefing(100),
        ]);
    }

    // ---------------------------------------------------------------
    // Atualizar projeto + briefing
    // ---------------------------------------------------------------

    public function update(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/briefing');
            return;
        }

        $projectId  = (int) $this->input('project_id');
        $briefingId = (int) $this->input('briefing_id');
        $project    = ClientProject::find($projectId);

        if (!$project) {
            $this->setFlash('error', 'Projeto não encontrado.');
            $this->redirect('/admin/briefing');
            return;
        }

        // Higienização e validação no update
        $updEmail = trim($this->input('client_email', ''));
        if (!empty($updEmail) && !filter_var($updEmail, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'O e-mail informado não é válido.');
            $this->redirect('/admin/briefing/edit/' . $projectId);
            return;
        }

        ClientProject::updateById($projectId, [
            'client_name'     => trim($this->input('client_name', '')),
            'client_document' => preg_replace('/\D/', '', $this->input('client_document', '')),
            'client_phone'    => preg_replace('/\D/', '', $this->input('client_phone', '')),
            'client_email'    => $updEmail,
            'project_type'    => trim($this->input('project_type', '')),
            'project_address' => trim($this->input('project_address', '')),
            'project_cep'     => preg_replace('/\D/', '', $this->input('project_cep', '')),
            'project_city'    => trim($this->input('project_city', '')),
            'project_goal'    => trim($this->input('project_goal', '')),
            'project_area'    => $this->input('project_area') ?: null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $briefingData = [
            'preferences'         => trim($this->input('preferences', '')),
            'priorities'          => trim($this->input('priorities', '')),
            'needs'               => trim($this->input('needs', '')),
            'restrictions'        => trim($this->input('restrictions', '')),
            'briefing_summary'    => trim($this->input('briefing_summary', '')),
            'negotiation_details' => trim($this->input('negotiation_details', '')),
            'contract_value'      => $this->input('contract_value') ?: null,
            'down_payment'        => $this->input('down_payment') ?: null,
            'discount_value'      => $this->input('discount_value') ?: null,
            'discount_percent'    => $this->input('discount_percent') ?: null,
            'payment_installments'=> $this->input('payment_installments') ?: null,
            'payment_details'     => trim($this->input('payment_details', '')),
            'start_date'          => $this->input('start_date') ?: null,
            'end_date'            => $this->input('end_date') ?: null,
            'deadline_days'       => $this->input('deadline_days') ?: null,
            'clauses'             => trim($this->input('clauses', '')),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        if ($briefingId > 0) {
            Briefing::updateById($briefingId, $briefingData);
        } else {
            $briefingData['client_project_id'] = $projectId;
            $briefingData['created_by']        = (int) Auth::id();
            $briefingData['created_at']        = date('Y-m-d H:i:s');
            Briefing::create($briefingData);
        }

        $this->setFlash('success', 'Briefing atualizado com sucesso!');
        $this->redirect('/admin/briefing/edit/' . $projectId);
    }

    // ---------------------------------------------------------------
    // Excluir projeto (e todos os briefings/objetos em cascade)
    // ---------------------------------------------------------------

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/briefing');
            return;
        }

        $projectId = (int) $this->input('id');
        ClientProject::deleteById($projectId);

        $this->setFlash('success', 'Projeto excluído com sucesso.');
        $this->redirect('/admin/briefing');
    }

    // ---------------------------------------------------------------
    // API: Transcrição de áudio via Whisper
    // POST /admin/briefing/transcribe-audio
    // ---------------------------------------------------------------

    public function transcribeAudio(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo de áudio não recebido ou inválido.'], 400);
            return;
        }

        $file         = $_FILES['audio'];
        $allowedMimes = [
            'audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4',
            'audio/wav', 'audio/x-wav', 'audio/flac', 'video/webm',
        ];

        // Alguns navegadores enviam tipos como application/octet-stream para WebM
        // por isso também validamos pela extensão
        $ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts  = ['webm', 'ogg', 'mp3', 'mp4', 'wav', 'flac', 'm4a'];

        if (!in_array($file['type'], $allowedMimes) && !in_array($ext, $allowedExts)) {
            $this->json(['error' => 'Formato de áudio não suportado.'], 400);
            return;
        }

        if ($file['size'] > 25 * 1024 * 1024) {
            $this->json(['error' => 'Áudio muito grande. Máximo 25 MB.'], 400);
            return;
        }

        // Salva temporariamente com extensão correta (Whisper exige extensão válida)
        $uploadDir = ROOT_PATH . '/public/uploads/audio_tmp/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // WebM gravado pelo MediaRecorder — renomeia para .webm explicitamente
        if (empty($ext) || $ext === 'blob') {
            $ext = 'webm';
        }

        $tmpFilename = 'whisper_' . Auth::id() . '_' . time() . '.' . $ext;
        $tmpPath     = $uploadDir . $tmpFilename;

        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $this->json(['error' => 'Erro ao salvar arquivo temporário.'], 500);
            return;
        }

        try {
            $ai          = new OpenAIService();
            $transcribed = $ai->transcribeAudio($tmpPath, 'pt');

            // Remove o temporário imediatamente após transcrição
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }

            $this->json(['success' => true, 'text' => $transcribed]);
        } catch (\Exception $e) {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // API: Polimento de texto ditado por voz
    // POST /admin/briefing/polish-text
    // ---------------------------------------------------------------

    public function polishText(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $text = trim($this->input('text', ''));

        if (empty($text)) {
            $this->json(['error' => 'Texto vazio.'], 400);
            return;
        }

        // Textos muito curtos (menos de 5 palavras) não precisam de IA
        if (str_word_count($text) < 3) {
            // Apenas capitaliza e remove espaços duplos
            $text = preg_replace('/\s{2,}/', ' ', $text);
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
            $this->json(['success' => true, 'text' => $text]);
            return;
        }

        try {
            $ai      = new OpenAIService();
            $polished = $ai->polishText($text);
            $this->json(['success' => true, 'text' => $polished]);
        } catch (\Exception $e) {
            // Em caso de erro, retorna o texto original sem travar o fluxo
            $this->json(['success' => true, 'text' => $text]);
        }
    }

    // ---------------------------------------------------------------
    // API: Geração do Objeto do Contrato
    // POST /admin/briefing/generate-object
    // ---------------------------------------------------------------

    public function generateObject(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $briefingId  = (int) $this->input('briefing_id');
        $templateId  = (int) $this->input('template_id');
        $customPrompt = trim($this->input('custom_prompt', ''));

        if ($briefingId <= 0) {
            $this->json(['error' => 'Salve o briefing antes de gerar o objeto.'], 400);
            return;
        }

        $briefing = Briefing::findByProject(
            (int) (Database::fetch(
                "SELECT client_project_id FROM briefings WHERE id = ?", [$briefingId]
            )['client_project_id'] ?? 0)
        );

        if (!$briefing) {
            $briefing = Briefing::find($briefingId);
        }

        if (!$briefing) {
            $this->json(['error' => 'Briefing não encontrado.'], 404);
            return;
        }

        // Resolve template
        $template = $templateId > 0
            ? ContractTemplate::find($templateId)
            : ContractTemplate::getDefault();

        $promptTemplate = $customPrompt ?: ($template['prompt_template'] ?? '');

        if (empty($promptTemplate)) {
            $this->json(['error' => 'Nenhum modelo de prompt disponível.'], 400);
            return;
        }

        // Monta mapa de variáveis
        $contractValue = !empty($briefing['contract_value'])
            ? number_format((float) $briefing['contract_value'], 2, ',', '.')
            : '';

        $variables = [
            'cliente_nome'      => $briefing['client_name']          ?? '',
            'cliente_documento' => $briefing['client_document']       ?? '',
            'cliente_telefone'  => $briefing['client_phone']          ?? '',
            'cliente_email'     => $briefing['client_email']          ?? '',
            'tipo_obra'         => $briefing['project_type']          ?? '',
            'endereco'          => $briefing['project_address']       ?? '',
            'cidade'            => $briefing['project_city']          ?? '',
            'objetivo'          => $briefing['project_goal']          ?? '',
            'area_m2'           => $briefing['project_area']          ?? '',
            'briefing'          => implode("\n\n", array_filter([
                $briefing['preferences']         ? "Preferências: " . $briefing['preferences']         : '',
                $briefing['priorities']          ? "Prioridades: "  . $briefing['priorities']          : '',
                $briefing['needs']               ? "Necessidades: " . $briefing['needs']               : '',
                $briefing['restrictions']        ? "Restrições: "   . $briefing['restrictions']        : '',
                $briefing['briefing_summary']    ? "Resumo: "       . $briefing['briefing_summary']    : '',
                $briefing['negotiation_details'] ? "Negociação: "   . $briefing['negotiation_details'] : '',
            ])),
            'valor_contrato'    => $contractValue,
            'entrada'           => !empty($briefing['down_payment'])
                ? number_format((float) $briefing['down_payment'], 2, ',', '.')
                : '',
            'desconto'          => !empty($briefing['discount_value'])
                ? number_format((float) $briefing['discount_value'], 2, ',', '.')
                : (!empty($briefing['discount_percent']) ? $briefing['discount_percent'] . '%' : ''),
            'parcelas'          => $briefing['payment_installments'] ?? '',
            'parcelamento'      => $briefing['payment_installments']
                ? $briefing['payment_installments'] . 'x - ' . ($briefing['payment_details'] ?? '')
                : ($briefing['payment_details'] ?? ''),
            'data_inicio'       => $briefing['start_date']     ?? '',
            'data_conclusao'    => $briefing['end_date']        ?? '',
            'prazo_dias'        => $briefing['deadline_days']   ?? '',
            'clausulas'         => $briefing['clauses']         ?? '',
        ];

        try {
            $ai     = new OpenAIService();
            $result = $ai->generateContractObject($promptTemplate, $variables);

            // Persiste o objeto gerado
            $objectId = ContractObject::create([
                'briefing_id'          => $briefingId,
                'contract_template_id' => $template['id'] ?? null,
                'generated_text'       => $result['text'],
                'prompt_used'          => $result['prompt_used'],
                'status'               => 'generated',
                'created_by'           => (int) Auth::id(),
                'created_at'           => date('Y-m-d H:i:s'),
            ]);

            $this->json([
                'success'    => true,
                'object_id'  => $objectId,
                'text'       => $result['text'],
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // API: Aprovar objeto do contrato
    // POST /admin/briefing/approve-object
    // ---------------------------------------------------------------

    public function approveObject(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $objectId = (int) $this->input('object_id');
        $object   = ContractObject::find($objectId);

        if (!$object) {
            $this->json(['error' => 'Objeto não encontrado.'], 404);
            return;
        }

        ContractObject::approve($objectId, (int) Auth::id());
        $this->json(['success' => true]);
    }

    // ---------------------------------------------------------------
    // API AJAX: Salva projeto + briefing, retorna IDs (usado pelo front
    // para "Salvar Rascunho" e "Salvar e Continuar" sem full page reload)
    // POST /admin/briefing/save-ajax
    // ---------------------------------------------------------------

    public function saveAjax(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $userId     = (int) Auth::id();
        $projectId  = (int) $this->input('project_id', 0);
        $briefingId = (int) $this->input('briefing_id', 0);

        // Higienização
        $clientDocument = preg_replace('/\D/', '', $this->input('client_document', ''));
        $clientPhone    = preg_replace('/\D/', '', $this->input('client_phone', ''));
        $projectCep     = preg_replace('/\D/', '', $this->input('project_cep', ''));
        $clientEmail    = trim($this->input('client_email', ''));

        if (!empty($clientEmail) && !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'E-mail inválido.'], 422);
            return;
        }

        $clientName = trim($this->input('client_name', ''));
        if (empty($clientName)) {
            $this->json(['error' => 'O nome do cliente é obrigatório.'], 422);
            return;
        }

        $projectData = [
            'client_name'     => $clientName,
            'client_document' => $clientDocument,
            'client_phone'    => $clientPhone,
            'client_email'    => $clientEmail,
            'project_type'    => trim($this->input('project_type', '')),
            'project_address' => trim($this->input('project_address', '')),
            'project_cep'     => $projectCep,
            'project_city'    => trim($this->input('project_city', '')),
            'project_goal'    => trim($this->input('project_goal', '')),
            'project_area'    => $this->input('project_area') ?: null,
        ];

        $briefingData = [
            'preferences'         => trim($this->input('preferences', '')),
            'priorities'          => trim($this->input('priorities', '')),
            'needs'               => trim($this->input('needs', '')),
            'restrictions'        => trim($this->input('restrictions', '')),
            'briefing_summary'    => trim($this->input('briefing_summary', '')),
            'negotiation_details' => trim($this->input('negotiation_details', '')),
            'contract_value'      => $this->input('contract_value') ?: null,
            'down_payment'        => $this->input('down_payment') ?: null,
            'discount_value'      => $this->input('discount_value') ?: null,
            'discount_percent'    => $this->input('discount_percent') ?: null,
            'payment_installments'=> $this->input('payment_installments') ?: null,
            'payment_details'     => trim($this->input('payment_details', '')),
            'start_date'          => $this->input('start_date') ?: null,
            'end_date'            => $this->input('end_date') ?: null,
            'deadline_days'       => $this->input('deadline_days') ?: null,
            'clauses'             => trim($this->input('clauses', '')),
        ];

        if ($projectId > 0 && ClientProject::find($projectId)) {
            // Atualiza existente
            ClientProject::updateById($projectId, array_merge($projectData, [
                'updated_at' => date('Y-m-d H:i:s'),
            ]));

            if ($briefingId > 0) {
                Briefing::updateById($briefingId, array_merge($briefingData, [
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            } else {
                $briefingId = Briefing::create(array_merge($briefingData, [
                    'client_project_id' => $projectId,
                    'created_by'        => $userId,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]));
            }
        } else {
            // Cria novo
            $projectId = ClientProject::create(array_merge($projectData, [
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]));

            $briefingId = Briefing::create(array_merge($briefingData, [
                'client_project_id' => $projectId,
                'created_by'        => $userId,
                'created_at'        => date('Y-m-d H:i:s'),
            ]));
        }

        $this->json([
            'success'    => true,
            'project_id' => $projectId,
            'briefing_id'=> $briefingId,
            'edit_url'   => '/admin/briefing/edit/' . $projectId,
        ]);
    }
    // ---------------------------------------------------------------

    public function storeTemplate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/briefing');
            return;
        }

        $name     = trim($this->input('template_name', ''));
        $desc     = trim($this->input('template_description', ''));
        $prompt   = trim($this->input('prompt_template', ''));

        if (empty($name) || empty($prompt)) {
            $this->json(['error' => 'Nome e template são obrigatórios.'], 400);
            return;
        }

        $id = ContractTemplate::create([
            'name'            => $name,
            'description'     => $desc,
            'prompt_template' => $prompt,
            'is_default'      => 0,
            'created_by'      => (int) Auth::id(),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'id' => $id, 'name' => $name]);
    }

    // ---------------------------------------------------------------
    // Templates: Atualizar modelo existente
    // POST /admin/briefing/update-template
    // ---------------------------------------------------------------

    public function updateTemplate(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Método inválido.'], 400);
            return;
        }

        $id     = (int) $this->input('template_id');
        $name   = trim($this->input('template_name', ''));
        $desc   = trim($this->input('template_description', ''));
        $prompt = trim($this->input('prompt_template', ''));

        if (!$id || empty($name) || empty($prompt)) {
            $this->json(['error' => 'Dados inválidos.'], 400);
            return;
        }

        ContractTemplate::updateById($id, [
            'name'            => $name,
            'description'     => $desc,
            'prompt_template' => $prompt,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true]);
    }
}
