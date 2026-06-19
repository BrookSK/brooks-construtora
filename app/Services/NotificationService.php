<?php

namespace App\Services;

use App\Models\NotificationQueue;
use App\Models\Setting;

class NotificationService
{
    /**
     * Enfileirar e-mail para múltiplos destinatários
     */
    public static function queueEmails(string $emailsCsv, string $subject, string $body): void
    {
        if (empty($emailsCsv)) return;
        
        $emailList = array_map('trim', explode(',', $emailsCsv));
        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                NotificationQueue::queueEmail($email, $subject, $body);
            }
        }
    }

    /**
     * Enfileirar webhook para múltiplos telefones
     * Se o campo phone tem vírgulas, cria um webhook por telefone
     */
    public static function queueWebhook(string $url, array $data): void
    {
        if (empty($url)) return;

        $phones = isset($data['phone']) && !empty($data['phone']) 
            ? array_map('trim', explode(',', $data['phone'])) 
            : [''];

        foreach ($phones as $phone) {
            $payload = $data;
            $payload['phone'] = $phone;
            NotificationQueue::queueWebhook($url, $payload);
        }
    }

    /**
     * Tentar processar imediatamente (para não depender 100% do cron)
     * Se falhar, fica na fila para o cron pegar depois
     */
    public static function processImmediate(): void
    {
        $pending = NotificationQueue::getPending(5);
        if (empty($pending)) return;

        $mailService = null;

        foreach ($pending as $n) {
            NotificationQueue::markProcessing($n['id']);
            try {
                if ($n['type'] === 'email') {
                    if (!$mailService) $mailService = new MailService();
                    $mailService->send($n['to_email'], $n['subject'], $n['body'], true);
                    NotificationQueue::markSent($n['id']);
                } elseif ($n['type'] === 'webhook') {
                    $ch = curl_init($n['webhook_url']);
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $n['webhook_payload'],
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ]);
                    curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($error || $httpCode >= 400) {
                        NotificationQueue::markFailed($n['id'], $error ?: "HTTP {$httpCode}");
                    } else {
                        NotificationQueue::markSent($n['id']);
                    }
                }
            } catch (\Exception $e) {
                NotificationQueue::markFailed($n['id'], $e->getMessage());
            }
        }
    }
}
