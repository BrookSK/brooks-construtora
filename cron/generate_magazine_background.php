<?php
/**
 * BACKGROUND: Geração completa de revista (conteúdo + imagens)
 * 
 * Este script é disparado pelo admin e roda em background no servidor.
 * Ele gera o conteúdo da revista e depois todas as imagens, atualizando
 * o progresso na tabela generation_jobs para que o frontend possa acompanhar.
 *
 * Uso: php generate_magazine_background.php <job_id> <topic_id> <user_id>
 */

// Evita timeout do PHP (pode demorar para gerar muitas imagens)
set_time_limit(0);
ignore_user_abort(true);

// Não iniciar sessão — este script roda em background sem browser
define('BACKGROUND_PROCESS', true);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Core\Database;
use App\Models\Magazine;
use App\Models\MagazineTopic;
use App\Models\Setting;
use App\Services\OpenAIService;
use App\Services\MailService;

// Parâmetros
$jobId = (int) ($argv[1] ?? 0);
$topicId = (int) ($argv[2] ?? 0);
$userId = (int) ($argv[3] ?? 0);

if (!$jobId || !$topicId) {
    echo "Uso: php generate_magazine_background.php <job_id> <topic_id> <user_id>\n";
    exit(1);
}

// Log para debug
$logFile = ROOT_PATH . '/cron/background_generation.log';
function bgLog(string $msg): void {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$time}] {$msg}\n", FILE_APPEND);
}

bgLog("=== Processo iniciado. Job #{$jobId}, Topic #{$topicId}, User #{$userId} ===");

// Funções auxiliares para atualizar o job
function updateJob(int $jobId, array $data): void
{
    Database::update('generation_jobs', $data, 'id = ?', [$jobId]);
}

function updateStep(int $jobId, int $step, string $label): void
{
    updateJob($jobId, [
        'current_step' => $step,
        'current_step_label' => $label,
    ]);
}

function failJob(int $jobId, string $error): void
{
    updateJob($jobId, [
        'status' => 'failed',
        'error_message' => $error,
        'completed_at' => date('Y-m-d H:i:s'),
    ]);
}

// Início
updateJob($jobId, [
    'status' => 'processing',
    'started_at' => date('Y-m-d H:i:s'),
    'current_step_label' => 'Iniciando geração...',
]);

bgLog("Job marcado como processing.");

$topic = MagazineTopic::find($topicId);
if (!$topic) {
    failJob($jobId, 'Tema não encontrado.');
    bgLog("ERRO: Tema #{$topicId} não encontrado.");
    exit(1);
}

bgLog("Tema encontrado: " . $topic['title']);

// ============================================================
// PASSO 1: Gerar conteúdo da revista via GPT
// ============================================================
updateStep($jobId, 1, 'Gerando conteúdo da revista com IA...');

try {
    $openai = new OpenAIService();
    $content = $openai->generateMagazineContent($topic['title'], $topic['description']);
    bgLog("Conteúdo gerado com sucesso. Título: " . $content['title']);
} catch (Exception $e) {
    failJob($jobId, 'Erro ao gerar conteúdo: ' . $e->getMessage());
    bgLog("ERRO ao gerar conteúdo: " . $e->getMessage());
    exit(1);
}

// ============================================================
// PASSO 2: Salvar revista e páginas no banco
// ============================================================
updateStep($jobId, 2, 'Salvando revista no banco de dados...');

try {
    $magazineId = Magazine::create([
        'title' => $content['title'],
        'subtitle' => $content['subtitle'] ?? '',
        'topic_id' => $topicId,
        'status' => Magazine::STATUS_GENERATED,
        'cover_image' => null,
        'generated_by' => 'ai',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    foreach ($content['pages'] as $index => $page) {
        Magazine::addPage($magazineId, [
            'page_number' => $index + 1,
            'title' => $page['title'] ?? '',
            'subtitle' => $page['subtitle'] ?? '',
            'content' => $page['content'] ?? '',
            'image_url' => null,
            'image_url_2' => null,
            'image_suggestion' => $page['image_suggestion'] ?? null,
            'image_suggestion_2' => $page['image_suggestion_2'] ?? null,
            'caption' => $page['caption'] ?? null,
            'layout_type' => $page['layout'] ?? 'internal_01',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Vincula a revista ao job
    updateJob($jobId, ['magazine_id' => $magazineId]);

    // Marca o tema como usado
    MagazineTopic::markAsUsed($topicId);

} catch (Exception $e) {
    failJob($jobId, 'Erro ao salvar revista: ' . $e->getMessage());
    exit(1);
}

// ============================================================
// PASSO 3: Identificar imagens para gerar
// ============================================================
updateStep($jobId, 3, 'Identificando imagens para gerar...');

$pages = Magazine::getPages($magazineId);
$imagesToGenerate = [];

foreach ($pages as $page) {
    if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])) {
        continue;
    }

    $suggestion = $page['image_suggestion'] ?? null;
    $suggestion2 = $page['image_suggestion_2'] ?? null;
    $oneImageLayouts = ['internal_04', 'internal_07'];

    if ($suggestion) {
        // Determina orientação
        $orientation = 'landscape';
        if (in_array($page['layout_type'], ['internal_02', 'internal_07'])) $orientation = 'portrait';
        if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

        $imagesToGenerate[] = [
            'page_id' => $page['id'],
            'page_number' => $page['page_number'],
            'field' => 'image_url',
            'description' => $suggestion,
            'orientation' => $orientation,
            'layout_type' => $page['layout_type'],
        ];
    }

    if ($suggestion2 && !in_array($page['layout_type'], $oneImageLayouts)) {
        $orientation = 'landscape';
        if ($page['layout_type'] === 'internal_01') $orientation = 'portrait';
        if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

        $imagesToGenerate[] = [
            'page_id' => $page['id'],
            'page_number' => $page['page_number'],
            'field' => 'image_url_2',
            'description' => $suggestion2,
            'orientation' => $orientation,
            'layout_type' => $page['layout_type'],
        ];
    }
}

// Total de passos: 3 (conteúdo + salvar + identificar) + N imagens
$totalSteps = 3 + count($imagesToGenerate);
updateJob($jobId, ['total_steps' => $totalSteps]);

// ============================================================
// PASSO 4+: Gerar cada imagem
// ============================================================
$imageCount = 0;
$imageErrors = 0;

foreach ($imagesToGenerate as $i => $img) {
    $stepNum = 4 + $i;
    $imgLabel = ($img['field'] === 'image_url_2') ? 'Imagem 2' : 'Imagem 1';
    $label = "Gerando {$imgLabel} da Página {$img['page_number']}...";
    
    updateStep($jobId, $stepNum, $label);

    try {
        $imageUrl = $openai->generateImage($img['description'], $img['orientation']);
        
        if ($imageUrl) {
            Magazine::updatePage($img['page_id'], [$img['field'] => $imageUrl]);
            $imageCount++;
        } else {
            $imageErrors++;
        }
    } catch (Exception $e) {
        $imageErrors++;
        // Continua com as próximas imagens mesmo se uma falhar
        error_log("Job #{$jobId} - Erro imagem Pág.{$img['page_number']} ({$img['field']}): " . $e->getMessage());
    }
}

// ============================================================
// FINALIZAR
// ============================================================
$finalLabel = "Concluído! {$imageCount} imagens geradas.";
if ($imageErrors > 0) {
    $finalLabel .= " ({$imageErrors} falharam)";
}

updateJob($jobId, [
    'status' => 'completed',
    'current_step' => $totalSteps,
    'current_step_label' => $finalLabel,
    'completed_at' => date('Y-m-d H:i:s'),
]);

// Envia notificação por e-mail
try {
    $emails = Setting::get('notification_emails', '');
    if (!empty($emails)) {
        $mail = new MailService();
        $emailList = array_map('trim', explode(',', $emails));
        $htmlBody = \App\Services\EmailTemplate::magazineGenerated($content['title'], $magazineId);

        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->send($email, 'Nova Revista Gerada - Brooks Construtora', $htmlBody, true);
            }
        }
    }
} catch (Exception $e) {
    error_log("Job #{$jobId} - Erro ao enviar notificação: " . $e->getMessage());
}

echo "Job #{$jobId} concluído. Revista #{$magazineId}. Imagens: {$imageCount} OK, {$imageErrors} erros.\n";
bgLog("=== Processo concluído. Revista #{$magazineId}. Imagens: {$imageCount} OK, {$imageErrors} erros. ===");
exit(0);
