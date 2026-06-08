<?php
/**
 * CRON Endpoint - Geração automática de revistas
 * 
 * Configure o cron do servidor para acessar esta URL a cada 10 minutos:
 * */10 * * * * curl -s "https://seu-dominio.com.br/cron.php?token=SEU_TOKEN_AQUI" > /dev/null 2>&1
 * 
 * O sistema controla internamente se já rodou no período configurado.
 */

// Carrega a aplicação
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\Setting;
use App\Models\Magazine;
use App\Models\MagazineTopic;
use App\Core\Database;

header('Content-Type: application/json');

// Verifica token de segurança
$cronToken = Setting::get('cron_token', '');
$requestToken = $_GET['token'] ?? '';

if (empty($cronToken)) {
    // Se não tem token configurado, gera um e salva
    $cronToken = bin2hex(random_bytes(32));
    Setting::set('cron_token', $cronToken);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token de cron gerado. Configure nas Configurações do admin e use na URL do cron.',
        'token' => $cronToken,
    ]);
    exit;
}

if ($requestToken !== $cronToken) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token inválido.']);
    exit;
}

// Verifica se deve gerar hoje baseado na frequência
$frequency = Setting::get('magazine_frequency', 'quinzenal');
$timesPerPeriod = (int) Setting::get('magazine_times_per_period', 1);
$dayOfWeek = (int) Setting::get('magazine_day_of_week', 1);
$dayOfMonth = (int) Setting::get('magazine_day_of_month', 1);
$lastRun = Setting::get('cron_last_run', '');
$lastGenerated = Setting::get('cron_last_generated', '');

$now = new DateTime();
$today = $now->format('Y-m-d');

// Verifica se já rodou hoje (evita rodar múltiplas vezes no mesmo dia)
if ($lastRun === $today) {
    echo json_encode([
        'status' => 'skip',
        'message' => 'Cron já executou hoje.',
        'last_run' => $lastRun,
        'last_generated' => $lastGenerated,
    ]);
    exit;
}

// Verifica se é dia de gerar
$shouldGenerate = false;

switch ($frequency) {
    case 'diario':
        $shouldGenerate = true;
        break;

    case 'semanal':
        if ((int) $now->format('w') === $dayOfWeek) {
            $shouldGenerate = true;
        }
        break;

    case 'quinzenal':
        $day = (int) $now->format('j');
        $weekday = (int) $now->format('w');
        // Gera na primeira e terceira semana do mês no dia configurado
        if ($weekday === $dayOfWeek && ($day <= 7 || ($day >= 15 && $day <= 21))) {
            $shouldGenerate = true;
        }
        break;

    case 'mensal':
        if ((int) $now->format('j') === $dayOfMonth) {
            $shouldGenerate = true;
        }
        break;
}

// Marca que rodou hoje (independente de gerar ou não)
Setting::set('cron_last_run', $today);

if (!$shouldGenerate) {
    echo json_encode([
        'status' => 'skip',
        'message' => "Hoje não é dia de gerar. Frequência: {$frequency}.",
        'last_run' => $today,
        'last_generated' => $lastGenerated,
        'next_check' => 'Cron rodará novamente amanhã.',
    ]);
    exit;
}

// Verifica se já gerou neste período (proteção contra duplicatas)
if (!empty($lastGenerated)) {
    $lastGeneratedDate = new DateTime($lastGenerated);
    $diff = $now->diff($lastGeneratedDate)->days;

    $minDays = match($frequency) {
        'diario' => 1,
        'semanal' => 6,
        'quinzenal' => 13,
        'mensal' => 27,
        default => 1,
    };

    if ($diff < $minDays) {
        echo json_encode([
            'status' => 'skip',
            'message' => "Última geração foi há {$diff} dia(s). Mínimo entre gerações: {$minDays} dias.",
            'last_generated' => $lastGenerated,
        ]);
        exit;
    }
}

// Hora de gerar! Verifica se tem tema disponível
$topics = MagazineTopic::getPending();

if (empty($topics)) {
    // Tenta gerar temas automaticamente
    try {
        $openai = new App\Services\OpenAIService();
        $newTopics = $openai->generateTopics(10);

        foreach ($newTopics as $topic) {
            MagazineTopic::create([
                'title' => $topic['title'],
                'description' => $topic['description'],
                'used' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $topics = MagazineTopic::getPending();
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sem temas disponíveis e erro ao gerar: ' . $e->getMessage(),
        ]);
        exit;
    }
}

if (empty($topics)) {
    echo json_encode(['status' => 'error', 'message' => 'Nenhum tema disponível para gerar revista.']);
    exit;
}

// Pega o primeiro tema
$topic = $topics[0];

try {
    $openai = new App\Services\OpenAIService();
    $content = $openai->generateMagazineContent($topic['title'], $topic['description']);

    // Cria a revista
    $magazineId = Magazine::create([
        'title' => $content['title'],
        'subtitle' => $content['subtitle'],
        'topic_id' => $topic['id'],
        'status' => Magazine::STATUS_GENERATED,
        'cover_image' => null,
        'generated_by' => 'ai',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // Cria as páginas
    foreach ($content['pages'] as $index => $page) {
        Magazine::addPage($magazineId, [
            'page_number' => $index + 1,
            'title' => $page['title'] ?? '',
            'subtitle' => $page['subtitle'] ?? '',
            'content' => $page['content'] ?? '',
            'image_url' => $page['image_url'] ?? null,
            'image_url_2' => $page['image_url_2'] ?? null,
            'caption' => $page['caption'] ?? null,
            'layout_type' => $page['layout'] ?? 'internal_01',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Marca tema como usado
    MagazineTopic::markAsUsed($topic['id']);

    // Atualiza controle
    Setting::set('cron_last_generated', $today);
    Setting::set('cron_last_magazine_id', (string) $magazineId);

    // Envia notificação por e-mail
    $notificationSent = false;
    $emails = Setting::get('notification_emails', '');
    if (!empty($emails)) {
        try {
            $mail = new App\Services\MailService();
            $emailList = array_map('trim', explode(',', $emails));
            $htmlBody = App\Services\EmailTemplate::magazineGenerated($content['title'], $magazineId, $topic['title']);

            foreach ($emailList as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->send(
                        $email,
                        'Nova Revista Gerada - Brooks Construtora',
                        $htmlBody,
                        true
                    );
                }
            }
            $notificationSent = true;
        } catch (Exception $e) {
            // Não interrompe por erro de e-mail
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Revista gerada com sucesso!',
        'magazine_id' => $magazineId,
        'title' => $content['title'],
        'topic' => $topic['title'],
        'notification_sent' => $notificationSent,
        'generated_at' => date('Y-m-d H:i:s'),
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao gerar revista: ' . $e->getMessage(),
    ]);
}
