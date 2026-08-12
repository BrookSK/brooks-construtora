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
        'cover_image' => \App\Models\Setting::get('magazine_default_cover', null),
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
            'image_url' => null,
            'image_url_2' => null,
            'image_suggestion' => $page['image_suggestion'] ?? null,
            'image_suggestion_2' => $page['image_suggestion_2'] ?? null,
            'caption' => $page['caption'] ?? null,
            'layout_type' => $page['layout'] ?? 'text_image',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Marca tema como usado
    MagazineTopic::markAsUsed($topic['id']);

    echo "[" . date('Y-m-d H:i:s') . "] Revista #{$magazineId} gerada com sucesso!\n";

    // Gera imagens automaticamente (no cron não tem timeout de browser)
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando geração de imagens...\n";
    $pages = Magazine::getPages($magazineId);
    $imageCount = 0;
    $imageErrors = 0;

    foreach ($pages as $page) {
        if (in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])) {
            continue;
        }

        $suggestion = $page['image_suggestion'] ?? null;
        $suggestion2 = $page['image_suggestion_2'] ?? null;
        $oneImageLayouts = ['internal_04', 'internal_07'];

        // Imagem 1
        if ($suggestion && empty($page['image_url'])) {
            $orientation = 'landscape';
            if (in_array($page['layout_type'], ['internal_02', 'internal_07'])) $orientation = 'portrait';
            if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

            try {
                $imageUrl = $openai->generateImage($suggestion, $orientation);
                if ($imageUrl) {
                    Magazine::updatePage($page['id'], ['image_url' => $imageUrl]);
                    $imageCount++;
                    echo "[" . date('Y-m-d H:i:s') . "]   Página {$page['page_number']} - Imagem 1 gerada.\n";
                }
            } catch (Exception $e) {
                $imageErrors++;
                echo "[" . date('Y-m-d H:i:s') . "]   ERRO Página {$page['page_number']} - Imagem 1: " . $e->getMessage() . "\n";
            }
        }

        // Imagem 2
        if ($suggestion2 && empty($page['image_url_2']) && !in_array($page['layout_type'], $oneImageLayouts)) {
            $orientation = 'landscape';
            if ($page['layout_type'] === 'internal_01') $orientation = 'portrait';
            if (in_array($page['layout_type'], ['internal_05', 'internal_06'])) $orientation = 'portrait';

            try {
                $imageUrl = $openai->generateImage($suggestion2, $orientation);
                if ($imageUrl) {
                    Magazine::updatePage($page['id'], ['image_url_2' => $imageUrl]);
                    $imageCount++;
                    echo "[" . date('Y-m-d H:i:s') . "]   Página {$page['page_number']} - Imagem 2 gerada.\n";
                }
            } catch (Exception $e) {
                $imageErrors++;
                echo "[" . date('Y-m-d H:i:s') . "]   ERRO Página {$page['page_number']} - Imagem 2: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] Imagens: {$imageCount} geradas, {$imageErrors} erros.\n";

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
