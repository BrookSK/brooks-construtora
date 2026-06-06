<?php
/**
 * CRON: Geração automática de revistas
 * 
 * Adicione ao crontab:
 * 0 9 * * * php /caminho/para/cron/generate_magazine.php
 * 
 * Este script verifica diariamente se é hora de gerar uma nova revista
 * baseado nas configurações de frequência definidas no painel admin.
 */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require ROOT_PATH . '/app/Config/app.php';
$app = new App\Core\Application($config);

use App\Models\Setting;
use App\Models\Magazine;
use App\Models\MagazineTopic;
use App\Services\OpenAIService;
use App\Services\MailService;

// Verifica se deve gerar hoje
$frequency = Setting::get('magazine_frequency', 'quinzenal');
$timesPerPeriod = (int) Setting::get('magazine_times_per_period', 1);
$dayOfWeek = (int) Setting::get('magazine_day_of_week', 1);
$dayOfMonth = (int) Setting::get('magazine_day_of_month', 1);

$shouldGenerate = false;
$today = new DateTime();

switch ($frequency) {
    case 'diario':
        $shouldGenerate = true;
        break;

    case 'semanal':
        if ((int) $today->format('w') === $dayOfWeek) {
            $shouldGenerate = true;
        }
        break;

    case 'quinzenal':
        $day = (int) $today->format('j');
        if ((int) $today->format('w') === $dayOfWeek && ($day <= 7 || ($day >= 15 && $day <= 21))) {
            $shouldGenerate = true;
        }
        break;

    case 'mensal':
        if ((int) $today->format('j') === $dayOfMonth) {
            $shouldGenerate = true;
        }
        break;
}

if (!$shouldGenerate) {
    echo "[" . date('Y-m-d H:i:s') . "] Hoje não é dia de gerar revista.\n";
    exit(0);
}

// Verifica se tem tema disponível
$topics = MagazineTopic::getPending();
if (empty($topics)) {
    echo "[" . date('Y-m-d H:i:s') . "] Nenhum tema disponível. Gerando temas...\n";

    try {
        $openai = new OpenAIService();
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
        echo "[" . date('Y-m-d H:i:s') . "] ERRO ao gerar temas: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Pega o primeiro tema disponível
$topic = $topics[0];

echo "[" . date('Y-m-d H:i:s') . "] Gerando revista com tema: {$topic['title']}\n";

try {
    $openai = new OpenAIService();
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
            'content' => $page['content'] ?? '',
            'image_url' => $page['image_url'] ?? null,
            'layout_type' => $page['layout'] ?? 'text_image',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Marca tema como usado
    MagazineTopic::markAsUsed($topic['id']);

    echo "[" . date('Y-m-d H:i:s') . "] Revista #{$magazineId} gerada com sucesso!\n";

    // Envia notificação
    $emails = Setting::get('notification_emails', '');
    if (!empty($emails)) {
        $mail = new MailService();
        $emailList = array_map('trim', explode(',', $emails));

        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->send(
                    $email,
                    'Nova Revista Gerada - Brooks Construtora',
                    "Uma nova revista foi gerada automaticamente e está aguardando revisão.\n\nTítulo: {$content['title']}\n\nAcesse o painel administrativo para revisar, fazer upload da capa e publicar."
                );
            }
        }
        echo "[" . date('Y-m-d H:i:s') . "] Notificações enviadas.\n";
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Concluído.\n";
