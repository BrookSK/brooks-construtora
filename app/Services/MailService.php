<?php

namespace App\Services;

use App\Models\Setting;

class MailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host = Setting::get('smtp_host', '');
        $this->port = (int) Setting::get('smtp_port', 587);
        $this->username = Setting::get('smtp_username', '');
        $this->password = Setting::get('smtp_password', '');
        $this->encryption = Setting::get('smtp_encryption', 'tls');
        $this->fromEmail = Setting::get('smtp_from_email', '');
        $this->fromName = Setting::get('smtp_from_name', 'Brooks Construtora');
    }

    /**
     * Envia e-mail. Suporta anexos opcionais.
     *
     * @param array $attachments Lista de anexos no formato:
     *   [ ['path' => '/caminho/arquivo.pdf', 'name' => 'Revista.pdf', 'mime' => 'application/pdf'], ... ]
     *   ou [ ['content' => '<binário>', 'name' => 'Revista.pdf', 'mime' => 'application/pdf'], ... ]
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = false, array $attachments = []): bool
    {
        if (empty($this->host) || empty($this->username)) {
            throw new \Exception('Configurações de SMTP não definidas. Acesse Configurações para definir.');
        }

        $socket = $this->connect();

        if (!$socket) {
            throw new \Exception('Não foi possível conectar ao servidor SMTP.');
        }

        try {
            // Lê a resposta inicial
            $this->getResponse($socket);

            // EHLO
            $this->sendCommand($socket, "EHLO " . gethostname());

            // STARTTLS se necessário
            if ($this->encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand($socket, "EHLO " . gethostname());
            }

            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->sendCommand($socket, base64_encode($this->username));
            $this->sendCommand($socket, base64_encode($this->password));

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>");

            // RCPT TO
            $this->sendCommand($socket, "RCPT TO:<{$to}>");

            // DATA
            $this->sendCommand($socket, "DATA");

            // Monta a mensagem (com ou sem anexos)
            if (!empty($attachments)) {
                $message = $this->buildMessageWithAttachments($to, $subject, $body, $isHtml, $attachments);
            } else {
                $contentType = $isHtml ? 'text/html' : 'text/plain';
                $message = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
                $message .= "To: {$to}\r\n";
                $message .= "Subject: {$subject}\r\n";
                $message .= "MIME-Version: 1.0\r\n";
                $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
                $message .= "\r\n";
                $message .= $body;
            }
            $message .= "\r\n.\r\n";

            fwrite($socket, $message);
            $this->getResponse($socket);

            // QUIT
            $this->sendCommand($socket, "QUIT");

            fclose($socket);
            return true;
        } catch (\Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            throw $e;
        }
    }

    /**
     * Monta uma mensagem MIME multipart com corpo + anexos.
     */
    private function buildMessageWithAttachments(string $to, string $subject, string $body, bool $isHtml, array $attachments): string
    {
        $boundary = 'brooks_' . md5(uniqid((string) mt_rand(), true));
        $contentType = $isHtml ? 'text/html' : 'text/plain';

        $message = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $message .= "\r\n";

        // Corpo
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n";
        $message .= "\r\n";
        $message .= $body . "\r\n";

        // Anexos
        foreach ($attachments as $att) {
            $content = null;
            if (!empty($att['content'])) {
                $content = $att['content'];
            } elseif (!empty($att['path']) && is_file($att['path'])) {
                $content = file_get_contents($att['path']);
            }
            if ($content === null || $content === false) continue;

            $name = $att['name'] ?? 'anexo';
            $mime = $att['mime'] ?? 'application/octet-stream';
            $encoded = chunk_split(base64_encode($content));

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: {$mime}; name=\"{$name}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n";
            $message .= "\r\n";
            $message .= $encoded . "\r\n";
        }

        $message .= "--{$boundary}--";

        return $message;
    }

    private function connect()
    {
        $protocol = $this->encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client(
            "{$protocol}{$this->host}:{$this->port}",
            $errno,
            $errstr,
            30
        );

        return $socket;
    }

    private function sendCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->getResponse($socket);
        
        // Verifica se a resposta indica erro (códigos 4xx e 5xx)
        $code = (int) substr($response, 0, 3);
        if ($code >= 400) {
            throw new \Exception("Erro SMTP ({$code}): " . trim($response));
        }
        
        return $response;
    }

    private function getResponse($socket): string
    {
        $response = '';
        $timeout = 10; // segundos
        stream_set_timeout($socket, $timeout);
        
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        if (empty($response)) {
            throw new \Exception('Sem resposta do servidor SMTP (timeout).');
        }
        
        return $response;
    }
}
