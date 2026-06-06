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

    public function send(string $to, string $subject, string $body, bool $isHtml = false): bool
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

            // Cabeçalhos e corpo
            $contentType = $isHtml ? 'text/html' : 'text/plain';
            $message = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
            $message .= "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body;
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
        return $this->getResponse($socket);
    }

    private function getResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
}
