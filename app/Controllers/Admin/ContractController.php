<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\ContractBaseTemplate;
use App\Models\GeneratedContract;
use App\Models\ContractorCompany;
use App\Models\Setting;
use App\Models\ContractAiLog;
use App\Services\OpenAIService;
use App\Services\ContractValidator;
use App\Services\ContractModelLibrary;

class ContractController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
    }

    // =================================================================
    // AUTO-MIGRATE + SEED
    // =================================================================

    private function ensureReady(): void
    {
        $files = [
            ROOT_PATH . '/database/migrations/039_create_contract_generation.sql',
            ROOT_PATH . '/database/migrations/040_create_contract_ai_logs.sql',
        ];
        $pdo = Database::getConnection();
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            $raw = preg_replace('/--[^\n]*/', '', file_get_contents($file));
            foreach ($this->splitSql($raw) as $stmt) {
                if (trim($stmt) === '') continue;
                try { $pdo->exec($stmt); }
                catch (\PDOException $e) {
                    $m = $e->getMessage();
                    if (stripos($m, 'already exists') === false && stripos($m, 'Duplicate') === false) {
                        error_log('[Contract] ' . $m);
                    }
                }
            }
        }
        $this->seedTemplates();
    }

    /**
     * Instancia o serviço da IA já com o modelo configurado para contratos
     * (fallback para o modelo global do sistema).
     */
    private function ai(): OpenAIService
    {
        $ai = new OpenAIService();
        $model = Setting::get('contract_openai_model', '');
        if (!empty($model)) {
            $ai->setModel($model);
        }
        return $ai;
    }

    /**
     * Persiste o diagnóstico da última chamada à IA.
     */
    private function logAi(OpenAIService $ai, ?int $contractId, string $context): void
    {
        if (empty($ai->lastDiagnostics)) {
            return;
        }
        $d = $ai->lastDiagnostics;
        ContractAiLog::record([
            'contract_id'     => $contractId,
            'operation'       => $d['operation'] ?? 'generate',
            'model'           => $d['model'] ?? null,
            'http_status'     => $d['http_status'] ?? null,
            'duration_ms'     => $d['duration_ms'] ?? null,
            'success'         => !empty($d['success']) ? 1 : 0,
            'request_payload' => $d['request_payload'] ?? null,
            'response_body'   => $d['response_body'] ?? null,
            'error_message'   => $d['error_message'] ?? null,
            'context'         => $context,
            'created_by'      => (int)Auth::id(),
        ]);
    }

    private function seedTemplates(): void
    {
        try {
            $count = (int)(Database::fetch("SELECT COUNT(*) AS c FROM contract_base_templates")['c'] ?? 0);
        } catch (\PDOException $e) {
            return;
        }
        if ($count > 0) {
            return;
        }
        ContractBaseTemplate::create([
            'name'          => 'Empreitada, Administração e Gerenciamento (padrão)',
            'contract_type' => 'execucao',
            'model_text'    => ContractModelLibrary::MODELO_EXECUCAO,
            'system_prompt' => ContractModelLibrary::SYSTEM_PROMPT,
            'is_default'    => 1,
            'active'        => 1,
            'created_by'    => (int)Auth::id(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    private function splitSql(string $raw): array
    {
        $stmts = []; $cur = ''; $inStr = false; $sc = '';
        for ($i = 0, $l = strlen($raw); $i < $l; $i++) {
            $ch = $raw[$i];
            if (!$inStr && ($ch === "'" || $ch === '"')) { $inStr = true; $sc = $ch; $cur .= $ch; continue; }
            if ($inStr) {
                if ($ch === '\\' && $i + 1 < $l) { $cur .= $ch . $raw[++$i]; continue; }
                if ($ch === $sc) $inStr = false;
                $cur .= $ch; continue;
            }
            if ($ch === ';') { $s = trim($cur); if ($s !== '') $stmts[] = $s; $cur = ''; continue; }
            $cur .= $ch;
        }
        if (trim($cur) !== '') $stmts[] = trim($cur);
        return $stmts;
    }

    // =================================================================
    // INDEX — histórico de contratos gerados
    // =================================================================

    public function index(): void
    {
        try {
            $contracts = GeneratedContract::allRecent(150);
        } catch (\PDOException $e) {
            $this->ensureReady();
            $contracts = GeneratedContract::allRecent(150);
        }

        $this->view('admin.contracts.index', [
            'user'      => Auth::user(),
            'flash'     => $this->getFlash(),
            'contracts' => $contracts,
        ]);
    }

    // =================================================================
    // WIZARD — 4 etapas
    // =================================================================

    public function wizard(): void
    {
        $this->ensureReady();

        $this->view('admin.contracts.wizard', [
            'user'         => Auth::user(),
            'flash'        => $this->getFlash(),
            'templates'    => ContractBaseTemplate::allActive(),
            'contractors'  => ContractorCompany::allActive(),
        ]);
    }

    // =================================================================
    // ETAPA 1 → 2: Upload + extração da proposta (AJAX)
    // =================================================================

    public function extract(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido.'], 400); return; }
        if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'PDF inválido.'], 400); return;
        }
        $file = $_FILES['pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['type'] !== 'application/pdf' && $ext !== 'pdf') {
            $this->json(['error' => 'Envie um arquivo PDF.'], 400); return;
        }
        if ($file['size'] > 60 * 1024 * 1024) {
            $this->json(['error' => 'Tamanho máximo: 60 MB.'], 400); return;
        }

        $dir = ROOT_PATH . '/public/uploads/contracts_tmp/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $tmp = $dir . 'prop_' . Auth::id() . '_' . time() . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $tmp)) {
            $this->json(['error' => 'Erro ao salvar o arquivo.'], 500); return;
        }

        $ai = $this->ai();
        try {
            $proposal = $ai->extractProposalFromPdf($tmp, $file['name']);
            $this->logAi($ai, null, $file['name']);
            if (file_exists($tmp)) unlink($tmp);

            $capa = $proposal['capa'] ?? [];
            $suggestedTemplate = ContractBaseTemplate::pickByType($capa['contrato_tipo'] ?? '');

            $this->json([
                'success'       => true,
                'proposal'      => $proposal,
                'source_pdf'    => $file['name'],
                'suggested_template_id' => $suggestedTemplate['id'] ?? null,
                'low_confidence' => $proposal['confianca_baixa'] ?? [],
            ]);
        } catch (\Exception $e) {
            $this->logAi($ai, null, $file['name']);
            if (file_exists($tmp)) unlink($tmp);
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // =================================================================
    // ETAPA 4: Geração do contrato (AJAX)
    // =================================================================

    public function generate(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido.'], 400); return; }
        $this->ensureReady();

        $proposal = $this->decodeJsonInput('proposal');
        $complementary = $this->decodeJsonInput('complementary');
        $templateId = (int)$this->input('template_id', 0);
        $sourcePdf = trim($this->input('source_pdf', ''));

        if (empty($proposal)) {
            $this->json(['error' => 'Dados da proposta ausentes.'], 422); return;
        }

        $template = $templateId > 0
            ? ContractBaseTemplate::find($templateId)
            : ContractBaseTemplate::pickByType($proposal['capa']['contrato_tipo'] ?? '');
        if (!$template) {
            $this->json(['error' => 'Nenhum modelo-base disponível.'], 400); return;
        }

        $ai = $this->ai();
        try {
            $gen = $ai->generateContract(
                $template['system_prompt'],
                $template['model_text'],
                $proposal,
                $complementary
            );

            $validator = new ContractValidator();
            $validation = $validator->validate($proposal, $complementary, $gen['markdown']);

            $projectCode = trim((string)($proposal['capa']['projeto_codigo'] ?? '')) ?: null;
            $projectName = trim((string)($proposal['capa']['projeto_nome'] ?? '')) ?: null;
            $version = GeneratedContract::nextVersion($projectCode);

            $id = GeneratedContract::create([
                'project_code'       => $projectCode,
                'project_name'       => $projectName,
                'base_template_id'   => $template['id'],
                'version'            => $version,
                'proposal_revision'  => trim((string)($proposal['capa']['revisao'] ?? '')) ?: null,
                'source_pdf'         => $sourcePdf ?: null,
                'extraction_json'    => json_encode($proposal, JSON_UNESCAPED_UNICODE),
                'complementary_json' => json_encode($complementary, JSON_UNESCAPED_UNICODE),
                'contract_markdown'  => $gen['markdown'],
                'report_json'        => $gen['report'],
                'validation_json'    => json_encode($validation, JSON_UNESCAPED_UNICODE),
                'status'             => 'generated',
                'created_by'         => (int)Auth::id(),
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            $this->logAi($ai, $id, trim(($projectCode ?? '') . ' ' . ($projectName ?? '')));

            $this->json([
                'success'    => true,
                'contract_id'=> $id,
                'version'    => $version,
                'markdown'   => $gen['markdown'],
                'report'     => $gen['report'],
                'validation' => $validation,
                'show_url'   => '/admin/contracts/show/' . $id,
            ]);
        } catch (\Exception $e) {
            $this->logAi($ai, null, 'Falha na geração');
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // =================================================================
    // SHOW — preview/edição do contrato gerado
    // =================================================================

    public function show(string $id = ''): void
    {
        $cid = (int)($id ?: $this->input('id'));
        $contract = GeneratedContract::findWithMeta($cid);
        if (!$contract) {
            $this->setFlash('error', 'Contrato não encontrado.');
            $this->redirect('/admin/contracts'); return;
        }

        $versions = $contract['project_code']
            ? GeneratedContract::versionsByProject($contract['project_code'])
            : [$contract];

        $this->view('admin.contracts.show', [
            'user'       => Auth::user(),
            'flash'      => $this->getFlash(),
            'contract'   => $contract,
            'validation' => json_decode($contract['validation_json'] ?? 'null', true),
            'versions'   => $versions,
        ]);
    }

    // =================================================================
    // SAVE — salva edição do markdown (WYSIWYG) sem criar nova versão
    // =================================================================

    public function save(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido.'], 400); return; }
        $cid = (int)$this->input('contract_id');
        $contract = GeneratedContract::find($cid);
        if (!$contract) { $this->json(['error' => 'Não encontrado.'], 404); return; }

        $markdown = (string)$this->input('markdown', '');
        $proposal = json_decode($contract['extraction_json'] ?? '{}', true) ?: [];
        $complementary = json_decode($contract['complementary_json'] ?? '{}', true) ?: [];

        $validator = new ContractValidator();
        $validation = $validator->validate($proposal, $complementary, $markdown);

        GeneratedContract::updateById($cid, [
            'contract_markdown' => $markdown,
            'validation_json'   => json_encode($validation, JSON_UNESCAPED_UNICODE),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'validation' => $validation]);
    }

    // =================================================================
    // REGERAR — nova versão a partir dos mesmos dados (mantém histórico)
    // =================================================================

    public function regenerate(): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'Método inválido.'], 400); return; }
        $cid = (int)$this->input('contract_id');
        $base = GeneratedContract::find($cid);
        if (!$base) { $this->json(['error' => 'Não encontrado.'], 404); return; }

        $proposal = json_decode($base['extraction_json'] ?? '{}', true) ?: [];
        $complementary = json_decode($base['complementary_json'] ?? '{}', true) ?: [];
        $template = ContractBaseTemplate::find((int)$base['base_template_id']);
        if (!$template) { $this->json(['error' => 'Modelo-base indisponível.'], 400); return; }

        $ai = $this->ai();
        try {
            $gen = $ai->generateContract($template['system_prompt'], $template['model_text'], $proposal, $complementary);

            $validator = new ContractValidator();
            $validation = $validator->validate($proposal, $complementary, $gen['markdown']);

            $version = GeneratedContract::nextVersion($base['project_code']);
            $id = GeneratedContract::create([
                'project_code'       => $base['project_code'],
                'project_name'       => $base['project_name'],
                'base_template_id'   => $template['id'],
                'version'            => $version,
                'proposal_revision'  => $base['proposal_revision'],
                'source_pdf'         => $base['source_pdf'],
                'extraction_json'    => $base['extraction_json'],
                'complementary_json' => $base['complementary_json'],
                'contract_markdown'  => $gen['markdown'],
                'report_json'        => $gen['report'],
                'validation_json'    => json_encode($validation, JSON_UNESCAPED_UNICODE),
                'status'             => 'generated',
                'created_by'         => (int)Auth::id(),
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            // regeração é registrada com a operação correta
            if (!empty($ai->lastDiagnostics)) {
                $ai->lastDiagnostics['operation'] = 'regenerate';
            }
            $this->logAi($ai, $id, trim(($base['project_code'] ?? '') . ' ' . ($base['project_name'] ?? '')));

            $this->json(['success' => true, 'contract_id' => $id, 'version' => $version, 'show_url' => '/admin/contracts/show/' . $id]);
        } catch (\Exception $e) {
            if (!empty($ai->lastDiagnostics)) {
                $ai->lastDiagnostics['operation'] = 'regenerate';
            }
            $this->logAi($ai, null, 'Falha na regeração');
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // =================================================================
    // EXPORT — página de exportação (DOCX/PDF client-side)
    // =================================================================

    public function export(string $id = ''): void
    {
        $cid = (int)($id ?: $this->input('id'));
        $contract = GeneratedContract::findWithMeta($cid);
        if (!$contract) {
            $this->setFlash('error', 'Contrato não encontrado.');
            $this->redirect('/admin/contracts'); return;
        }

        GeneratedContract::updateById($cid, ['status' => 'exported', 'updated_at' => date('Y-m-d H:i:s')]);

        $logoUrl = Setting::get('magazine_logo', '/assets/images/wp/2024/11/logo-brooks-1400x396.webp');

        $this->view('admin.contracts.export', [
            'contract' => $contract,
            'logoUrl'  => $logoUrl,
        ]);
    }

    // =================================================================
    // DELETE
    // =================================================================

    public function delete(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/contracts'); return; }
        GeneratedContract::deleteById((int)$this->input('id'));
        $this->setFlash('success', 'Contrato excluído.');
        $this->redirect('/admin/contracts');
    }

    // =================================================================
    // CONFIGURAÇÕES — seleção do modelo GPT
    // =================================================================

    public function settings(): void
    {
        $this->ensureReady();

        $current = Setting::get('contract_openai_model', '');
        $globalModel = Setting::get('openai_model', 'gpt-4');
        $hasKey = !empty(Setting::get('openai_api_key', ''));

        $models = $this->availableModels();

        $this->view('admin.contracts.settings', [
            'user'         => Auth::user(),
            'flash'        => $this->getFlash(),
            'currentModel' => $current,
            'globalModel'  => $globalModel,
            'models'       => $models,
            'hasKey'       => $hasKey,
        ]);
    }

    public function saveSettings(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/contracts/settings'); return; }
        $model = trim($this->input('contract_openai_model', ''));
        Setting::set('contract_openai_model', $model);
        $this->setFlash('success', 'Configurações salvas.');
        $this->redirect('/admin/contracts/settings');
    }

    /**
     * Modelos GPT oferecidos para seleção. Inclui o modelo global e uma lista
     * conhecida; o usuário pode digitar outro manualmente.
     */
    private function availableModels(): array
    {
        return [
            'gpt-4o'        => 'GPT-4o (recomendado — multimodal, lê PDF)',
            'gpt-4o-mini'   => 'GPT-4o mini (mais barato e rápido)',
            'gpt-4.1'       => 'GPT-4.1',
            'gpt-4.1-mini'  => 'GPT-4.1 mini',
            'gpt-4-turbo'   => 'GPT-4 Turbo',
            'gpt-4'         => 'GPT-4',
        ];
    }

    // =================================================================
    // DIAGNÓSTICO — logs de chamadas à IA
    // =================================================================

    public function diagnostics(): void
    {
        $this->ensureReady();

        $filter = trim($this->input('filter', ''));

        try {
            $logs = ContractAiLog::recent(150, $filter);
            $stats = ContractAiLog::stats();
        } catch (\PDOException $e) {
            $this->ensureReady();
            $logs = ContractAiLog::recent(150, $filter);
            $stats = ContractAiLog::stats();
        }

        // últimas linhas do error_log do PHP (quando acessível)
        $errorLog = $this->tailPhpErrorLog(120);

        $this->view('admin.contracts.diagnostics', [
            'user'     => Auth::user(),
            'flash'    => $this->getFlash(),
            'logs'     => $logs,
            'stats'    => $stats,
            'filter'   => $filter,
            'errorLog' => $errorLog,
        ]);
    }

    public function logDetail(string $id = ''): void
    {
        $lid = (int)($id ?: $this->input('id'));
        $log = ContractAiLog::find($lid);
        if (!$log) { $this->json(['error' => 'Log não encontrado.'], 404); return; }
        $this->json(['success' => true, 'log' => $log]);
    }

    public function clearLogs(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/contracts/diagnostics'); return; }
        try { ContractAiLog::clear(); } catch (\PDOException $e) { /* tabela pode não existir ainda */ }
        $this->setFlash('success', 'Logs de diagnóstico limpos.');
        $this->redirect('/admin/contracts/diagnostics');
    }

    private function tailPhpErrorLog(int $lines): string
    {
        $path = ini_get('error_log');
        if (empty($path) || !is_file($path) || !is_readable($path)) {
            return '';
        }
        $data = @file($path, FILE_IGNORE_NEW_LINES);
        if ($data === false) {
            return '';
        }
        // só as linhas relacionadas ao módulo, para não vazar ruído
        $filtered = array_filter($data, fn($l) => stripos($l, 'Contract') !== false || stripos($l, 'OpenAI') !== false);
        if (empty($filtered)) {
            $filtered = $data;
        }
        return implode("\n", array_slice($filtered, -$lines));
    }

    // =================================================================
    // VALIDAÇÃO DE CPF (AJAX, usado na Etapa 3)
    // =================================================================

    public function validateCpf(): void
    {
        $cpf = preg_replace('/\D/', '', $this->input('cpf', ''));
        $valid = (new ContractValidator())->validCpf($cpf);
        $this->json(['valid' => $valid]);
    }

    // =================================================================
    // HELPERS
    // =================================================================

    private function decodeJsonInput(string $key): array
    {
        $raw = $this->input($key, '');
        if (is_array($raw)) return $raw;
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
