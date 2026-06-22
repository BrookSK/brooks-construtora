<?php

namespace App\Services;

use App\Models\NotificationQueue;

class NotificationService
{
    private static bool $flushScheduled = false;

    /**
     * Enfileirar e-mail para múltiplos destinatários
     */
    public static function queueEmails(string $emailsCsv, string $subject, string $body, ?int $orderId = null, ?string $eventType = null): void
    {
        if (empty($emailsCsv)) return;
        
        $emailList = array_map('trim', explode(',', $emailsCsv));
        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                NotificationQueue::queueEmail($email, $subject, $body, $orderId, $eventType);
            }
        }

        self::scheduleFlush();
    }

    /**
     * Enfileirar webhook para múltiplos telefones
     * Se o campo phone tem vírgulas, cria um webhook por telefone
     */
    public static function queueWebhook(string $url, array $data, ?int $orderId = null, ?string $eventType = null): void
    {
        if (empty($url)) return;

        $phones = isset($data['phone']) && !empty($data['phone']) 
            ? array_map('trim', explode(',', $data['phone'])) 
            : [''];

        $phoneNames = isset($data['phone_name']) && !empty($data['phone_name'])
            ? array_map('trim', explode(',', $data['phone_name']))
            : [];

        foreach ($phones as $i => $phone) {
            $payload = $data;
            $payload['phone'] = $phone;
            $recipientName = $phoneNames[$i] ?? ($phoneNames[0] ?? $phone);
            $payload['phone_name'] = $recipientName;
            NotificationQueue::queueWebhook($url, $payload, $orderId, $eventType, $recipientName);
        }

        self::scheduleFlush();
    }

    /**
     * Agenda o processamento imediato para o final do request (uma só vez)
     */
    private static function scheduleFlush(): void
    {
        if (!self::$flushScheduled) {
            self::$flushScheduled = true;
            register_shutdown_function([self::class, 'processImmediate']);
        }
    }

    /**
     * Processar fila imediatamente (chamado automaticamente no shutdown do request)
     * Processa todas as pendentes para garantir envio rápido
     */
    public static function processImmediate(): void
    {
        $pending = NotificationQueue::getPending(50);
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
