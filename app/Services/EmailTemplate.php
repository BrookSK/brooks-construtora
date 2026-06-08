<?php

namespace App\Services;

class EmailTemplate
{
    private static function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
        return $scheme . '://' . $host;
    }

    private static function wrap(string $title, string $body): string
    {
        $baseUrl = self::baseUrl();
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding: 30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
    <!-- Header -->
    <tr>
        <td style="background-color:#3a3b4e; padding: 25px 30px; text-align:center;">
            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:1px;">BROOKS CONSTRUTORA</h1>
        </td>
    </tr>
    <!-- Titulo -->
    <tr>
        <td style="padding: 30px 30px 10px; text-align:center;">
            <h2 style="margin:0; color:#3a3b4e; font-size:20px; font-weight:600;">{$title}</h2>
        </td>
    </tr>
    <!-- Corpo -->
    <tr>
        <td style="padding: 15px 30px 30px; color:#444; font-size:15px; line-height:1.7;">
            {$body}
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td style="background-color:#f9f9f9; padding: 20px 30px; text-align:center; border-top: 1px solid #eee;">
            <p style="margin:0; font-size:12px; color:#999;">Brooks Construtora &bull; Av. Brigadeiro Faria Lima, 1811 - São Paulo/SP</p>
            <p style="margin:5px 0 0; font-size:12px; color:#999;"><a href="{$baseUrl}" style="color:#446084; text-decoration:none;">www.brooksconstrutora.com.br</a></p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function magazineGenerated(string $magazineTitle, int $magazineId, string $topicTitle = ''): string
    {
        $baseUrl = self::baseUrl();
        $editUrl = "{$baseUrl}/admin/magazines/edit/{$magazineId}";
        $date = date('d/m/Y \à\s H:i');
        $displayTitle = !empty($topicTitle) ? $topicTitle : $magazineTitle;

        $body = <<<HTML
<p style="margin-bottom:15px;">Uma nova edição da revista foi gerada automaticamente pela IA e está aguardando revisão da equipe.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; padding:0; margin-bottom:20px;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Tema da Edição</p>
    <p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">{$displayTitle}</p>
    <p style="margin:10px 0 0; font-size:13px; color:#888;">Gerada em {$date}</p>
</td></tr>
</table>

<p style="margin-bottom:15px;"><strong>Próximos passos:</strong></p>
<ol style="color:#555; padding-left:20px; margin-bottom:20px;">
    <li style="margin-bottom:8px;">Acesse o painel e revise o conteúdo gerado</li>
    <li style="margin-bottom:8px;">Faça o upload da capa da revista</li>
    <li style="margin-bottom:8px;">Revise os textos e imagens de cada página</li>
    <li style="margin-bottom:8px;">Aprove e publique para enviar aos assinantes</li>
</ol>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$editUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:12px 28px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Revisar Revista</a>
</p>
HTML;

        return self::wrap('Nova Revista Gerada', $body);
    }

    public static function magazinePublished(string $magazineTitle, int $magazineId, string $subscriberName = '', string $subscriberEmail = '', string $topicTitle = ''): string
    {
        $baseUrl = self::baseUrl();
        $viewUrl = "{$baseUrl}/revista/ver/{$magazineId}";
        $unsubscribeUrl = "{$baseUrl}/newsletter/unsubscribe?email=" . urlencode($subscriberEmail);
        $greeting = !empty($subscriberName) ? "Olá, {$subscriberName}!" : "Olá!";
        $displayTitle = !empty($topicTitle) ? $topicTitle : $magazineTitle;

        $body = <<<HTML
<p style="margin-bottom:15px;">{$greeting}</p>

<p style="margin-bottom:15px;">Uma nova edição da nossa revista digital foi publicada. Confira conteúdo exclusivo sobre construção, reformas e arquitetura de alto padrão.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Nova Edição</p>
    <p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">{$displayTitle}</p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$viewUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:12px 28px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Ler Revista</a>
</p>

<p style="text-align:center; font-size:13px; color:#666; margin-top:15px;">Você também pode baixar a revista em PDF acessando o link acima.</p>

<p style="font-size:12px; color:#999; margin-top:25px; text-align:center;">Você recebeu este e-mail por ser assinante da Revista Brooks Construtora.<br>
<a href="{$unsubscribeUrl}" style="color:#999; text-decoration:underline;">Não quero mais receber</a></p>
HTML;

        return self::wrap('Nova Revista: ' . $displayTitle, $body);
    }

    public static function contactReceived(string $name, string $email, string $phone, string $message): string
    {
        $date = date('d/m/Y \à\s H:i');

        $body = <<<HTML
<p style="margin-bottom:15px;">Novo contato recebido pelo formulário do site:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">
<tr><td style="padding: 18px 20px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="padding:5px 0; color:#888; font-size:13px; width:80px; vertical-align:top;">Nome:</td><td style="padding:5px 0; color:#333; font-size:14px;">{$name}</td></tr>
        <tr><td style="padding:5px 0; color:#888; font-size:13px; vertical-align:top;">E-mail:</td><td style="padding:5px 0; color:#333; font-size:14px;"><a href="mailto:{$email}" style="color:#446084;">{$email}</a></td></tr>
        <tr><td style="padding:5px 0; color:#888; font-size:13px; vertical-align:top;">Telefone:</td><td style="padding:5px 0; color:#333; font-size:14px;">{$phone}</td></tr>
        <tr><td style="padding:5px 0; color:#888; font-size:13px; vertical-align:top;">Data:</td><td style="padding:5px 0; color:#333; font-size:14px;">{$date}</td></tr>
    </table>
</td></tr>
</table>

<p style="margin-bottom:5px;"><strong>Mensagem:</strong></p>
<div style="background:#f8f9fa; border-left:3px solid #3a3b4e; padding:15px; border-radius:0 6px 6px 0; color:#444; font-size:14px; line-height:1.7;">
    {$message}
</div>
HTML;

        return self::wrap('Novo Contato do Site', $body);
    }
}
