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

    public static function resumeReceived(string $name, string $email, string $phone, string $area, string $message, ?string $resumeUrl): string
    {
        $date = date('d/m/Y \à\s H:i');

        $body = <<<HTML
<p style="margin-bottom:20px; color:#555;">Um novo currículo foi recebido através do site. Confira os dados abaixo:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:8px; margin-bottom:20px; border: 1px solid #e9ecef;">
<tr><td style="padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                <span style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Nome</span><br>
                <strong style="font-size:15px; color:#1a1a2e;">{$name}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                <span style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">E-mail</span><br>
                <a href="mailto:{$email}" style="font-size:15px; color:#446084; text-decoration:none;">{$email}</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e9ecef;">
                <span style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Telefone / WhatsApp</span><br>
                <strong style="font-size:15px; color:#1a1a2e;">{$phone}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0;">
                <span style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Área de Interesse</span><br>
                <span style="display:inline-block; margin-top:4px; padding: 4px 12px; background:#3a3b4e; color:#fff; border-radius:4px; font-size:13px; font-weight:600;">{$area}</span>
            </td>
        </tr>
    </table>
</td></tr>
</table>
HTML;

        if (!empty($message)) {
            $message = nl2br(htmlspecialchars($message));
            $body .= <<<HTML
<div style="background:#fff8e1; border-left:4px solid #ffc107; padding:15px 18px; border-radius:4px; margin-bottom:20px;">
    <p style="margin:0 0 5px; font-size:12px; color:#888; text-transform:uppercase; letter-spacing:0.5px;">Mensagem do candidato</p>
    <p style="margin:0; font-size:14px; color:#555; line-height:1.6;">{$message}</p>
</div>
HTML;
        }

        if (!empty($resumeUrl)) {
            $body .= <<<HTML
<div style="text-align:center; margin:25px 0;">
    <a href="{$resumeUrl}" style="display:inline-block; padding: 12px 30px; background:#3a3b4e; color:#ffffff; text-decoration:none; border-radius:6px; font-size:14px; font-weight:600; letter-spacing:0.5px;">
        📎 Baixar Currículo
    </a>
</div>
HTML;
        }

        $body .= <<<HTML
<p style="font-size:12px; color:#999; text-align:center; margin-top:20px;">Recebido em {$date} via site Brooks Construtora</p>
HTML;

        return self::wrap('Novo Currículo Recebido', $body);
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

    public static function newsletterWelcome(string $email, string $name = ''): string
    {
        $baseUrl = self::baseUrl();
        $revistaUrl = "{$baseUrl}/revista";
        $unsubscribeUrl = "{$baseUrl}/newsletter/unsubscribe?email=" . urlencode($email);
        $greeting = !empty($name) ? "Olá, {$name}!" : "Olá!";

        $body = <<<HTML
<p style="margin-bottom:15px; font-size:16px;">{$greeting}</p>

<p style="margin-bottom:15px;">Obrigado por se inscrever na <strong>Revista Brooks</strong>! Estamos felizes em ter você com a gente.</p>

<p style="margin-bottom:20px;">A partir de agora, você receberá edições exclusivas com conteúdo sobre:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:8px; margin-bottom:25px;">
<tr><td style="padding: 20px 25px;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 8px 0; font-size:14px; color:#444;">
                <span style="color:#3a3b4e; font-weight:bold; margin-right:8px;">▸</span> Construção civil de alto padrão
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-size:14px; color:#444;">
                <span style="color:#3a3b4e; font-weight:bold; margin-right:8px;">▸</span> Reformas e projetos de arquitetura
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-size:14px; color:#444;">
                <span style="color:#3a3b4e; font-weight:bold; margin-right:8px;">▸</span> Tendências e inovação no setor
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-size:14px; color:#444;">
                <span style="color:#3a3b4e; font-weight:bold; margin-right:8px;">▸</span> Sustentabilidade e tecnologia na obra
            </td>
        </tr>
    </table>
</td></tr>
</table>

<p style="margin-bottom:20px; font-size:14px; color:#555;">Enquanto isso, que tal conferir nossas edições anteriores?</p>

<p style="text-align:center; margin: 25px 0 15px;">
    <a href="{$revistaUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Ver Revista Digital</a>
</p>

<p style="margin-top:25px; padding-top:20px; border-top:1px solid #eee; font-size:13px; color:#888; line-height:1.6;">
    Fique à vontade para responder este e-mail se tiver dúvidas ou sugestões.<br>
    Publicamos novas edições periodicamente — você será notificado sempre que uma nova sair!
</p>

<p style="font-size:12px; color:#999; margin-top:20px; text-align:center;">
    Você recebeu este e-mail porque se inscreveu na Revista Brooks Construtora.<br>
    <a href="{$unsubscribeUrl}" style="color:#999; text-decoration:underline;">Cancelar inscrição</a>
</p>
HTML;

        return self::wrap('Bem-vindo à Revista Brooks!', $body);
    }

    public static function purchaseOrderQuote(array $order, array $items, string $quoteUrl, array $orderSuppliers = []): string
    {
        $itemsHtml = '';
        foreach ($items as $i => $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . ($i + 1) . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['material_name']) . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['specification'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['classification'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px; text-align:center;">' . number_format($item['quantity'], 0) . '</td>';
            $itemsHtml .= '</tr>';
        }

        // Informação da obra
        $obraHtml = '';
        if (!empty($order['construction_site_name'])) {
            $obraLabel = htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']);
            $obraHtml = '<p style="margin:8px 0 0; font-size:13px; color:#666;">Obra: <strong>' . $obraLabel . '</strong></p>';
            if (!empty($order['construction_site_address'])) {
                $obraAddress = htmlspecialchars($order['construction_site_address']);
                if (!empty($order['construction_site_city'])) $obraAddress .= ' - ' . htmlspecialchars($order['construction_site_city']) . '/' . htmlspecialchars($order['construction_site_state'] ?? '');
                $obraHtml .= '<p style="margin:2px 0 0; font-size:12px; color:#888;">' . $obraAddress . '</p>';
            }
        }

        $body = '<p style="margin-bottom:15px;">Um novo pedido de materiais foi criado e aguarda cotação de preços.</p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">'
            . '<tr><td style="padding: 18px 20px;">'
            . '<p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase;">Pedido</p>'
            . '<p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">' . htmlspecialchars($order['code']) . '</p>'
            . '<p style="margin:8px 0 0; font-size:13px; color:#666;">Solicitado por: <strong>' . htmlspecialchars($order['created_by_name'] ?? '') . '</strong></p>'
            . '<p style="margin:4px 0 0; font-size:13px; color:#666;">Data: ' . htmlspecialchars($order['created_at'] ?? '') . '</p>'
            . $obraHtml
            . '</td></tr></table>'
            . '<p style="margin-bottom:10px;"><strong>Itens do pedido:</strong></p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">'
            . '<tr style="background:#f8f9fa;">'
            . '<th style="padding:8px; font-size:12px; text-align:left;">#</th>'
            . '<th style="padding:8px; font-size:12px; text-align:left;">Material</th>'
            . '<th style="padding:8px; font-size:12px; text-align:left;">Especificação</th>'
            . '<th style="padding:8px; font-size:12px; text-align:left;">Classificação</th>'
            . '<th style="padding:8px; font-size:12px; text-align:center;">Qtd</th>'
            . '</tr>'
            . $itemsHtml
            . '</table>';

        $body .= '<p style="text-align:center; margin: 25px 0 10px;">'
            . '<a href="' . $quoteUrl . '" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Informar Cotação</a>'
            . '</p>'
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique no botão acima para acessar o formulário de cotação e informar os valores.</p>';

        return self::wrap("Cotação Pendente - " . htmlspecialchars($order['code']), $body);
    }

    public static function purchaseOrderApproval(array $order, array $items, string $approvalUrl, array $orderSuppliers = []): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        // Seção de fornecedores cotados
        $suppliersHtml = '';
        if (!empty($orderSuppliers)) {
            $suppliersHtml = '<p style="margin-bottom:10px;"><strong>Fornecedores cotados:</strong></p>';
            $suppliersHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">';
            $suppliersHtml .= '<tr style="background:#f8f9fa;"><th style="padding:8px 12px; font-size:12px; text-align:left;">Fornecedor</th><th style="padding:8px 12px; font-size:12px; text-align:right;">Total</th></tr>';
            foreach ($orderSuppliers as $os) {
                $osFmt = ($os['subtotal_final'] ?? $os['total'] ?? 0) > 0 ? 'R$ ' . number_format($os['subtotal_final'] ?? $os['total'], 2, ',', '.') : 'Pendente';
                $suppliersHtml .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($os['supplier_name']) . '</td>';
                $suppliersHtml .= '<td style="padding:8px 12px; border-bottom:1px solid #eee; font-size:13px; text-align:right; font-weight:600;">' . $osFmt . '</td></tr>';
            }
            $suppliersHtml .= '</table>';
        }

        // Itens do pedido
        $itemsHtml = '';
        foreach ($items as $i => $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px;">' . ($i + 1) . '</td>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px;">' . htmlspecialchars($item['material_name']) . '</td>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px; text-align:center;">' . number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) . '</td>';
            $itemsHtml .= '</tr>';
        }

        $body = '<p style="margin-bottom:15px;">Um pedido de materiais foi cotado e aguarda sua aprovação.</p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">'
            . '<tr><td style="padding: 18px 20px;">'
            . '<p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase;">Pedido</p>'
            . '<p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">' . htmlspecialchars($order['code']) . '</p>'
            . '<p style="margin:8px 0 0; font-size:13px; color:#666;">Cotado por: <strong>' . htmlspecialchars($order['quoted_by_name'] ?? '') . '</strong></p>'
            . '<p style="margin:4px 0 0; font-size:13px; color:#666;">Solicitado por: ' . htmlspecialchars($order['created_by_name'] ?? '') . '</p>';

        // Obra info no email de aprovação
        if (!empty($order['construction_site_name'])) {
            $obraName = htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']);
            $body .= '<p style="margin:8px 0 0; font-size:13px; color:#666;">Obra: <strong>' . $obraName . '</strong></p>';
        }

        $body .= '</td></tr></table>'
            . $suppliersHtml
            . '<p style="margin-bottom:8px;"><strong style="font-size:12px;">Itens do pedido:</strong></p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">'
            . '<tr style="background:#f8f9fa;">'
            . '<th style="padding:6px 8px; font-size:11px; text-align:left;">#</th>'
            . '<th style="padding:6px 8px; font-size:11px; text-align:left;">Material</th>'
            . '<th style="padding:6px 8px; font-size:11px; text-align:center;">Qtd</th>'
            . '</tr>'
            . $itemsHtml
            . '</table>'
            . '<p style="text-align:center; margin: 25px 0 10px;">'
            . '<a href="' . $approvalUrl . '" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Analisar e Decidir</a>'
            . '</p>'
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique no botão acima para aprovar ou rejeitar este pedido.</p>';

        return self::wrap("Aprovação Pendente - {$order['code']}", $body);
    }

    public static function purchaseOrderCompleted(array $order, array $items, string $pdfUrl, string $xlsxUrl = '', array $approvedSuppliers = []): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        $xlsxButton = '';
        if (!empty($xlsxUrl)) {
            $xlsxButton = <<<HTML
<a href="{$xlsxUrl}" style="display:inline-block; background-color:#28a745; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px; margin-left:10px;">Baixar Planilha</a>
HTML;
        }

        $supplierLine = '';
        if (!empty($approvedSuppliers)) {
            $names = array_column($approvedSuppliers, 'supplier_name');
            $supplierLine = '<p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor(es): <strong>' . htmlspecialchars(implode(', ', $names)) . '</strong></p>';
        }

        $obraLine = '';
        if (!empty($order['construction_site_name'])) {
            $obraName = htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']);
            $obraLine = '<p style="margin:8px 0 0; font-size:13px; color:#555;">Obra: <strong>' . $obraName . '</strong></p>';
        }

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido de materiais foi <strong style="color:#28a745;">APROVADO</strong> com sucesso!</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5e9; border-radius:6px; margin-bottom:20px; border:1px solid #c8e6c9;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#388e3c; text-transform:uppercase; font-weight:600;">✓ Pedido Aprovado</p>
    <p style="margin:0; font-size:17px; color:#2e7d32; font-weight:600;">{$order['code']}</p>
    {$supplierLine}
    {$obraLine}
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Aprovado por: <strong>{$order['approved_by_name']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Data: {$order['approved_at']}</p>
    <p style="margin:10px 0 0; font-size:20px; color:#2e7d32; font-weight:700;">Total: R$ {$totalFormatted}</p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$pdfUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Ver PDF do Pedido</a>
    {$xlsxButton}
</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique acima para visualizar o PDF ou baixar a planilha do pedido.</p>
HTML;

        return self::wrap("Pedido Aprovado - {$order['code']}", $body);
    }

    public static function purchaseOrderRejected(array $order, string $rejectedBy, string $reason): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');
        $date = date('d/m/Y \à\s H:i');

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido de materiais foi <strong style="color:#dc3545;">REJEITADO</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fdeaea; border-radius:6px; margin-bottom:20px; border:1px solid #f5c6cb;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#721c24; text-transform:uppercase; font-weight:600;">✗ Pedido Rejeitado</p>
    <p style="margin:0; font-size:17px; color:#721c24; font-weight:600;">{$order['code']}</p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor cotado: <strong>R$ {$totalFormatted}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Rejeitado por: <strong>{$rejectedBy}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Data: {$date}</p>
</td></tr>
</table>

<p style="margin-bottom:5px;"><strong>Motivo da rejeição:</strong></p>
<div style="background:#f8f9fa; border-left:3px solid #dc3545; padding:12px 15px; border-radius:0 6px 6px 0; color:#444; font-size:14px; line-height:1.6;">
    {$reason}
</div>

<p style="text-align:center; font-size:12px; color:#999; margin-top:20px;">O pedido deverá ser revisado e, se necessário, um novo pedido poderá ser criado.</p>
HTML;

        return self::wrap("Pedido Rejeitado - {$order['code']}", $body);
    }

    public static function purchaseOrderDeleted(string $orderCode, string $deletedBy, string $deletedAt): string
    {
        $body = <<<HTML
<p style="margin-bottom:15px;">Um pedido de materiais foi <strong style="color:#dc3545;">DELETADO permanentemente</strong> do sistema.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fdeaea; border-radius:6px; margin-bottom:20px; border:1px solid #f5c6cb;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#721c24; text-transform:uppercase; font-weight:600;">Pedido Excluído</p>
    <p style="margin:0; font-size:17px; color:#721c24; font-weight:600;">{$orderCode}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Deletado por: <strong>{$deletedBy}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Data: {$deletedAt}</p>
</td></tr>
</table>

<p style="font-size:13px; color:#666;">Este pedido e todos os seus dados (itens, cotações, histórico) foram removidos permanentemente do banco de dados.</p>
HTML;

        return self::wrap("Pedido Deletado - {$orderCode}", $body);
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

    /**
     * Template de e-mail para envio de NF/Boleto (Fase 4)
     */
    public static function purchaseOrderDelivery(array $order, array $items, string $checklistUrl, string $supplierDisplay): string
    {
        $itemCount = count($items);
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        $body = <<<HTML
<p style="margin-bottom:15px;">O checklist de entrega do pedido está disponível para conferência na obra.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5e9; border-radius:6px; margin-bottom:20px; border:1px solid #c8e6c9;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#388e3c; text-transform:uppercase; font-weight:600;">📋 Checklist de Entrega</p>
    <p style="margin:0; font-size:17px; color:#2e7d32; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor(es): <strong>{$supplierDisplay}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Itens: <strong>{$itemCount}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor Total: <strong>R$ {$totalFormatted}</strong></p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$checklistUrl}" style="display:inline-block; background-color:#28a745; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Acessar Checklist de Entrega</a>
</p>

<p style="font-size:13px; color:#666; margin-top:15px;">Use este link para conferir os materiais no momento da entrega. Marque cada item como entregue, conferido ou registre divergências diretamente pelo celular.</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">As alterações são salvas automaticamente em tempo real.</p>
HTML;

        return self::wrap("Checklist de Entrega - {$order['code']}", $body);
    }

    public static function spareItemAdded(array $order, string $description, float $total, string $purchasedBy, float $weekTotal, float $weeklyBudget): string
    {
        $totalFmt = number_format($total, 2, ',', '.');
        $weekFmt = number_format($weekTotal, 2, ',', '.');
        $budgetFmt = number_format($weeklyBudget, 2, ',', '.');
        $remaining = $weeklyBudget - $weekTotal;
        $remainingFmt = number_format(max(0, $remaining), 2, ',', '.');
        $exceeded = $remaining < 0;
        $barColor = $exceeded ? '#dc3545' : ($remaining < ($weeklyBudget * 0.2) ? '#ffc107' : '#28a745');

        $body = <<<HTML
<p style="margin-bottom:15px;">Um item sobressalente foi adicionado a um pedido.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd; border-radius:6px; margin-bottom:20px; border:1px solid #ffc107;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#856404; text-transform:uppercase; font-weight:600;">🛒 Item Sobressalente</p>
    <p style="margin:0; font-size:17px; color:#333; font-weight:600;">{$description}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Pedido: <strong>{$order['code']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor: <strong>R$ {$totalFmt}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Comprado por: <strong>{$purchasedBy}</strong></p>
</td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px; border:1px solid #dee2e6;">
<tr><td style="padding: 15px 20px;">
    <p style="margin:0 0 8px; font-size:13px; color:#333; font-weight:600;">Saldo Semanal</p>
    <div style="background:#e9ecef; border-radius:4px; height:10px; overflow:hidden;">
        <div style="background:{$barColor}; height:100%; width:min(100%, {$weekTotal}00/{$weeklyBudget}00 * 100)%;"></div>
    </div>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Gasto: R$ {$weekFmt} / R$ {$budgetFmt}</p>
    <p style="margin:4px 0 0; font-size:13px; color:{$barColor}; font-weight:600;">Restante: R$ {$remainingFmt}</p>
</td></tr>
</table>
HTML;

        if ($exceeded) {
            $body .= '<p style="color:#dc3545; font-weight:600; text-align:center;">⚠️ ORÇAMENTO SEMANAL EXCEDIDO!</p>';
        }

        return self::wrap("Item Sobressalente - {$order['code']}", $body);
    }

    public static function purchaseOrderCancelled(array $order, string $cancelledBy): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');
        $date = date('d/m/Y \à\s H:i');

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido de materiais foi <strong style="color:#dc3545;">CANCELADO</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8d7da; border-radius:6px; margin-bottom:20px; border:1px solid #f5c6cb;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#721c24; text-transform:uppercase; font-weight:600;">❌ Pedido Cancelado</p>
    <p style="margin:0; font-size:17px; color:#721c24; font-weight:600;">{$order['code']}</p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor: <strong>R$ {$totalFormatted}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Cancelado por: <strong>{$cancelledBy}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Data: {$date}</p>
</td></tr>
</table>

<p style="font-size:13px; color:#666;">Este pedido foi cancelado e não precisa mais de nenhuma ação.</p>
HTML;

        return self::wrap("Pedido Cancelado - {$order['code']}", $body);
    }

    public static function orderReopened(array $order, string $previousSupplier, string $reason, string $approvalUrl, string $reopenedBy): string
    {
        $totalFmt = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido abaixo foi <strong style="color:#dc3545;">REABERTO PARA REAPROVAÇÃO</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd; border-radius:6px; margin-bottom:20px; border:1px solid #ffc107;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#856404; text-transform:uppercase; font-weight:600;">⚠️ Reaprovação Necessária</p>
    <p style="margin:0; font-size:17px; color:#333; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor anterior: <strong>{$previousSupplier}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor: <strong>R$ {$totalFmt}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Reaberto por: <strong>{$reopenedBy}</strong></p>
</td></tr>
</table>

HTML;
        if ($reason) {
            $body .= "<p style=\"margin-bottom:15px;\"><strong>Motivo:</strong> {$reason}</p>";
        }

        $body .= <<<HTML
<p style="font-size:13px; color:#666;">O fornecedor aprovado anteriormente foi desfeito. Acesse o link abaixo para selecionar o fornecedor correto e reaprovar.</p>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$approvalUrl}" style="display:inline-block; background-color:#ffc107; color:#333; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Acessar e Reaprovar</a>
</p>
HTML;

        return self::wrap("⚠️ Reaprovação - {$order['code']}", $body);
    }

    public static function orderComment(array $order, string $authorName, string $message, string $actionUrl, string $role): string
    {
        $roleLabel = $role === 'approver' ? 'Aprovação' : 'Cotação';
        $actionLabel = $role === 'approver' ? 'Responder / Editar Cotação' : 'Ver Aprovação';
        $bgColor = $role === 'approver' ? '#fff3cd' : '#d1ecf1';
        $borderColor = $role === 'approver' ? '#ffc107' : '#bee5eb';

        $body = <<<HTML
<p style="margin-bottom:15px;">Nova mensagem sobre o pedido <strong>{$order['code']}</strong>:</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:{$bgColor}; border-radius:6px; margin-bottom:20px; border:1px solid {$borderColor};">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#333; font-weight:600;">💬 Mensagem de {$authorName} ({$roleLabel})</p>
    <p style="margin:10px 0 0; font-size:14px; color:#333; line-height:1.6; white-space:pre-wrap;">{$message}</p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$actionUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">{$actionLabel}</a>
</p>
HTML;

        return self::wrap("Mensagem - Pedido {$order['code']}", $body);
    }

    public static function purchaseOrderPaymentPending(array $order, string $panelUrl): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido de materiais foi aprovado e está aguardando o envio da <strong>NF ou Boleto</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd; border-radius:6px; margin-bottom:20px; border:1px solid #ffc107;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#856404; text-transform:uppercase; font-weight:600;">⏳ NF/Boleto Pendente</p>
    <p style="margin:0; font-size:17px; color:#333; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor: <strong>{$order['supplier_name']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor: <strong>R$ {$totalFormatted}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Aprovado por: <strong>{$order['approved_by_name']}</strong></p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$panelUrl}" style="display:inline-block; background-color:#ffc107; color:#333; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Acessar Painel e Enviar NF/Boleto</a>
</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Acesse o painel para fazer o upload da NF ou boleto referente a este pedido.</p>
HTML;

        return self::wrap("NF/Boleto Pendente - {$order['code']}", $body);
    }

    /**
     * Template de e-mail para envio de NF/Boleto (Fase 4) - quando já recebeu
     */
    public static function purchaseOrderPayment(array $order, string $typeLabel, array $docData, string $uploadedBy, string $panelUrl): string
    {
        $amountFmt = $docData['amount'] ? 'R$ ' . number_format($docData['amount'], 2, ',', '.') : 'N/A';
        $dueDateFmt = !empty($docData['due_date']) ? date('d/m/Y', strtotime($docData['due_date'])) : 'N/A';
        $numberText = !empty($docData['number']) ? $docData['number'] : 'N/A';

        $body = <<<HTML
<p style="margin-bottom:15px;">Um novo documento foi registrado no pedido de materiais.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#e3f2fd; border-radius:6px; margin-bottom:20px; border:1px solid #bbdefb;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#1565c0; text-transform:uppercase; font-weight:600;">{$typeLabel} Registrado</p>
    <p style="margin:0; font-size:17px; color:#1565c0; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor: <strong>{$order['supplier_name']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Número: <strong>{$numberText}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Valor: <strong>{$amountFmt}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Vencimento: <strong>{$dueDateFmt}</strong></p>
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Enviado por: <strong>{$uploadedBy}</strong></p>
</td></tr>
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$panelUrl}" style="display:inline-block; background-color:#1565c0; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Acessar Painel de Pedidos</a>
</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Acesse o painel para visualizar o documento, marcar como pago ou conferir os detalhes.</p>
HTML;

        return self::wrap("{$typeLabel} Enviado - {$order['code']}", $body);
    }

    public static function purchaseOrderEdited(array $order, array $items, string $quoteUrl, array $changes): string
    {
        // Resumo das alterações
        $changesHtml = '';
        if (!empty($changes['added'])) {
            $changesHtml .= '<div style="margin-bottom:10px;"><strong style="color:#28a745;">Itens adicionados:</strong><ul style="margin:5px 0; padding-left:20px;">';
            foreach ($changes['added'] as $a) {
                $changesHtml .= '<li style="font-size:13px;">' . htmlspecialchars($a['material_name']) . ' — Qtd: ' . $a['quantity'] . '</li>';
            }
            $changesHtml .= '</ul></div>';
        }
        if (!empty($changes['removed'])) {
            $changesHtml .= '<div style="margin-bottom:10px;"><strong style="color:#dc3545;">Itens removidos:</strong><ul style="margin:5px 0; padding-left:20px;">';
            foreach ($changes['removed'] as $r) {
                $changesHtml .= '<li style="font-size:13px;">' . htmlspecialchars($r['material_name']) . ' — Qtd: ' . $r['quantity'] . '</li>';
            }
            $changesHtml .= '</ul></div>';
        }
        if (!empty($changes['changed'])) {
            $changesHtml .= '<div style="margin-bottom:10px;"><strong style="color:#0d6efd;">Itens alterados:</strong><ul style="margin:5px 0; padding-left:20px;">';
            foreach ($changes['changed'] as $c) {
                $changesHtml .= '<li style="font-size:13px;">' . htmlspecialchars($c['material_name']) . ' — Qtd: ' . $c['old_quantity'] . ' → ' . $c['new_quantity'] . '</li>';
            }
            $changesHtml .= '</ul></div>';
        }

        // Lista atual de itens
        $itemsHtml = '';
        foreach ($items as $i => $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px;">' . ($i + 1) . '</td>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px;">' . htmlspecialchars($item['material_name']) . '</td>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px; text-align:center;">' . number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) . '</td>';
            $itemsHtml .= '</tr>';
        }

        // Obra
        $obraHtml = '';
        if (!empty($order['construction_site_name'])) {
            $obraHtml = '<p style="margin:8px 0 0; font-size:13px; color:#666;">Obra: <strong>' . htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']) . '</strong></p>';
        }

        $body = '<p style="margin-bottom:15px;">O pedido abaixo foi <strong style="color:#856404;">editado</strong> após a criação. Confira as alterações e a nova lista de itens.</p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd; border-radius:6px; margin-bottom:20px; border:1px solid #ffc107;">'
            . '<tr><td style="padding: 18px 20px;">'
            . '<p style="margin:0 0 5px; font-size:13px; color:#856404; text-transform:uppercase;">⚠️ Pedido Editado</p>'
            . '<p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">' . htmlspecialchars($order['code']) . '</p>'
            . '<p style="margin:8px 0 0; font-size:13px; color:#666;">Solicitado por: <strong>' . htmlspecialchars($order['created_by_name'] ?? '') . '</strong></p>'
            . $obraHtml
            . '</td></tr></table>'
            . '<p style="margin-bottom:10px;"><strong>O que foi alterado:</strong></p>'
            . $changesHtml
            . '<p style="margin-bottom:10px; margin-top:20px;"><strong>Nova lista de itens:</strong></p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">'
            . '<tr style="background:#f8f9fa;">'
            . '<th style="padding:6px 8px; font-size:11px; text-align:left;">#</th>'
            . '<th style="padding:6px 8px; font-size:11px; text-align:left;">Material</th>'
            . '<th style="padding:6px 8px; font-size:11px; text-align:center;">Qtd</th>'
            . '</tr>'
            . $itemsHtml
            . '</table>';

        $body .= '<p style="text-align:center; margin: 25px 0 10px;">'
            . '<a href="' . $quoteUrl . '" style="display:inline-block; background-color:#856404; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Ver Cotação Atualizada</a>'
            . '</p>'
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">O link de cotação continua o mesmo. Revise os novos itens antes de informar os preços.</p>';

        return self::wrap("⚠️ Pedido Editado - " . htmlspecialchars($order['code']), $body);
    }

    /**
     * Template de e-mail para notificação de edição financeira
     */
    public static function purchaseOrderFinancialEdit(array $order, array $changes, array $financialChanges, bool $obraChanged, string $oldObraName, string $newObraName, string $editedBy, float $oldTotal, float $newTotal): string
    {
        $date = date('d/m/Y \à\s H:i');
        $oldTotalFmt = number_format($oldTotal, 2, ',', '.');
        $newTotalFmt = number_format($newTotal, 2, ',', '.');
        $diffTotal = $newTotal - $oldTotal;
        $diffTotalFmt = ($diffTotal >= 0 ? '+' : '') . number_format($diffTotal, 2, ',', '.');
        $diffColor = $diffTotal > 0 ? '#dc3545' : ($diffTotal < 0 ? '#198754' : '#666');

        // Obra info
        $obraHtml = '';
        if (!empty($order['construction_site_name'])) {
            $obraLabel = htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']);
            $obraHtml = '<p style="margin:8px 0 0; font-size:13px; color:#555;">Obra: <strong>' . $obraLabel . '</strong></p>';
        }

        // Seção de alteração de obra
        $obraChangeHtml = '';
        if ($obraChanged) {
            $oldObraDisplay = !empty($oldObraName) ? htmlspecialchars($oldObraName) : '(nenhuma)';
            $newObraDisplay = htmlspecialchars($newObraName);
            $obraChangeHtml = '<div style="background:#fff3cd; border:1px solid #ffc107; border-radius:6px; padding:12px 16px; margin-bottom:15px;">'
                . '<p style="margin:0; font-size:13px; color:#856404;"><strong>Obra alterada:</strong> '
                . '<span style="text-decoration:line-through;">' . $oldObraDisplay . '</span> → <strong>' . $newObraDisplay . '</strong></p>'
                . '</div>';
        }

        // Seção de alterações financeiras
        $financialHtml = '';
        if (!empty($financialChanges)) {
            $financialHtml = '<p style="margin-bottom:8px;"><strong>Alterações financeiras:</strong></p>'
                . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:15px;">'
                . '<tr style="background:#f8f9fa;"><th style="padding:6px 10px; font-size:11px; text-align:left;">Campo</th><th style="padding:6px 10px; font-size:11px; text-align:left;">Fornecedor</th><th style="padding:6px 10px; font-size:11px; text-align:center;">Anterior</th><th style="padding:6px 10px; font-size:11px; text-align:center;">Novo</th></tr>';
            foreach ($financialChanges as $fc) {
                $oldVal = $fc['old_value'];
                $newVal = $fc['new_value'];
                if (isset($fc['is_percent']) && $fc['is_percent']) {
                    $oldVal = $oldVal . '%';
                    $newVal = $newVal . '%';
                } elseif (isset($fc['is_money']) && $fc['is_money']) {
                    $oldVal = 'R$ ' . number_format((float)$oldVal, 2, ',', '.');
                    $newVal = 'R$ ' . number_format((float)$newVal, 2, ',', '.');
                }
                $financialHtml .= '<tr>'
                    . '<td style="padding:6px 10px; border-bottom:1px solid #eee; font-size:12px;">' . htmlspecialchars($fc['label']) . '</td>'
                    . '<td style="padding:6px 10px; border-bottom:1px solid #eee; font-size:12px;">' . htmlspecialchars($fc['supplier_name']) . '</td>'
                    . '<td style="padding:6px 10px; border-bottom:1px solid #eee; font-size:12px; text-align:center;"><span style="color:#999; text-decoration:line-through;">' . $oldVal . '</span></td>'
                    . '<td style="padding:6px 10px; border-bottom:1px solid #eee; font-size:12px; text-align:center;"><strong style="color:#0d6efd;">' . $newVal . '</strong></td>'
                    . '</tr>';
            }
            $financialHtml .= '</table>';
        }

        // Tabela de alterações de itens
        $changesHtml = '';
        $changesCount = count($changes);
        if (!empty($changes)) {
            foreach ($changes as $i => $change) {
                $materialName = htmlspecialchars($change['material_name']);
                $spec = !empty($change['specification']) ? ' (' . htmlspecialchars($change['specification']) . ')' : '';
                $changesHtml .= '<tr>';
                $changesHtml .= '<td style="padding:8px 10px; border-bottom:1px solid #eee; font-size:12px; vertical-align:top;">' . ($i + 1) . '</td>';
                $changesHtml .= '<td style="padding:8px 10px; border-bottom:1px solid #eee; font-size:12px; vertical-align:top;"><strong>' . $materialName . '</strong>' . $spec . '</td>';

                $qtyHtml = '';
                if ($change['qty_changed']) {
                    $oldQtyFmt = number_format($change['old_quantity'], $change['old_quantity'] == (int)$change['old_quantity'] ? 0 : 2, ',', '.');
                    $newQtyFmt = number_format($change['new_quantity'], $change['new_quantity'] == (int)$change['new_quantity'] ? 0 : 2, ',', '.');
                    $qtyHtml = '<span style="color:#999; text-decoration:line-through;">' . $oldQtyFmt . '</span> → <strong style="color:#0d6efd;">' . $newQtyFmt . '</strong>';
                } else {
                    $qtyHtml = number_format($change['old_quantity'], $change['old_quantity'] == (int)$change['old_quantity'] ? 0 : 2, ',', '.');
                }
                $changesHtml .= '<td style="padding:8px 10px; border-bottom:1px solid #eee; font-size:12px; text-align:center; vertical-align:top;">' . $qtyHtml . '</td>';

                $priceHtml = '';
                if ($change['price_changed']) {
                    $oldPriceFmt = 'R$ ' . number_format($change['old_unit_price'], 2, ',', '.');
                    $newPriceFmt = 'R$ ' . number_format($change['new_unit_price'], 2, ',', '.');
                    $priceHtml = '<span style="color:#999; text-decoration:line-through;">' . $oldPriceFmt . '</span> → <strong style="color:#0d6efd;">' . $newPriceFmt . '</strong>';
                } else {
                    $priceHtml = 'R$ ' . number_format($change['old_unit_price'], 2, ',', '.');
                }
                $changesHtml .= '<td style="padding:8px 10px; border-bottom:1px solid #eee; font-size:12px; text-align:right; vertical-align:top;">' . $priceHtml . '</td>';

                $oldItemTotalFmt = 'R$ ' . number_format($change['old_total_price'], 2, ',', '.');
                $newItemTotalFmt = 'R$ ' . number_format($change['new_total_price'], 2, ',', '.');
                $changesHtml .= '<td style="padding:8px 10px; border-bottom:1px solid #eee; font-size:12px; text-align:right; vertical-align:top;">'
                    . '<span style="color:#999; text-decoration:line-through;">' . $oldItemTotalFmt . '</span><br><strong style="color:#198754;">' . $newItemTotalFmt . '</strong>'
                    . '</td>';
                $changesHtml .= '</tr>';
            }
        }

        // Montar tabela de itens apenas se houver alterações
        $itemsTableHtml = '';
        if (!empty($changes)) {
            $itemsTableHtml = '<p style="margin-bottom:10px;"><strong>Itens alterados (' . $changesCount . '):</strong></p>'
                . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">'
                . '<tr style="background:#f8f9fa;">'
                . '<th style="padding:8px 10px; font-size:11px; text-align:left;">#</th>'
                . '<th style="padding:8px 10px; font-size:11px; text-align:left;">Material</th>'
                . '<th style="padding:8px 10px; font-size:11px; text-align:center;">Quantidade</th>'
                . '<th style="padding:8px 10px; font-size:11px; text-align:right;">Unit&aacute;rio</th>'
                . '<th style="padding:8px 10px; font-size:11px; text-align:right;">Total</th>'
                . '</tr>'
                . $changesHtml
                . '</table>';
        }

        $body = '<p style="margin-bottom:15px;">O pedido de materiais foi <strong style="color:#8b5cf6;">EDITADO PELO FINANCEIRO</strong> após a aprovação.</p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(139, 92, 246, 0.08); border-radius:6px; margin-bottom:20px; border:1px solid rgba(139, 92, 246, 0.3);">'
            . '<tr><td style="padding: 18px 20px;">'
            . '<p style="margin:0 0 5px; font-size:13px; color:#6d28d9; text-transform:uppercase; font-weight:600;">⚠️ Edição Financeira</p>'
            . '<p style="margin:0; font-size:17px; color:#6d28d9; font-weight:600;">' . htmlspecialchars($order['code']) . '</p>'
            . $obraHtml
            . '<p style="margin:8px 0 0; font-size:13px; color:#555;">Editado por: <strong>' . htmlspecialchars($editedBy) . '</strong></p>'
            . '<p style="margin:4px 0 0; font-size:13px; color:#555;">Data: ' . $date . '</p>'
            . '<p style="margin:8px 0 0; font-size:13px; color:#555;">Total anterior: <span style="text-decoration:line-through;">R$ ' . $oldTotalFmt . '</span></p>'
            . '<p style="margin:4px 0 0; font-size:15px; color:#555;">Novo total: <strong style="color:#198754;">R$ ' . $newTotalFmt . '</strong> <small style="color:' . $diffColor . ';">(' . $diffTotalFmt . ')</small></p>'
            . '</td></tr></table>'
            . $obraChangeHtml
            . $financialHtml
            . $itemsTableHtml
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:20px;">Esta edição foi realizada pelo setor financeiro. O histórico completo está registrado no sistema.</p>';

        return self::wrap("⚠️ Edição Financeira - " . htmlspecialchars($order['code']), $body);
    }

    /**
     * E-mail de solicitação da lista semanal de materiais.
     * Usa um único link (hub) que leva a uma página com todas as obras,
     * evitando o bloqueio de spam do Gmail por excesso de links.
     */
    public static function weeklyMaterialRequest(string $managerName, string $cycleDate, string $hubUrl, int $totalObras = 1): string
    {
        $intro = $totalObras > 1
            ? 'Você é responsável por <strong>' . $totalObras . ' obras</strong> neste ciclo.'
            : 'Preencha a solicitação de materiais da sua obra.';

        $body = '<p style="margin-bottom:15px;">Olá <strong>' . htmlspecialchars($managerName) . '</strong>,</p>'
            . '<p style="margin-bottom:20px;">Envie a lista de materiais que você vai precisar no ciclo de <strong>' . htmlspecialchars($cycleDate) . '</strong>. ' . $intro . '</p>'
            . '<p style="text-align:center; margin: 25px 0 10px;">'
            . '<a href="' . $hubUrl . '" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Acessar Minhas Listas</a>'
            . '</p>'
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique no botão acima para ver todas as suas obras e preencher a lista de materiais de cada uma.</p>';

        return self::wrap('Lista Semanal de Materiais - Ciclo ' . htmlspecialchars($cycleDate), $body);
    }

    /**
     * E-mail de cobrança da lista semanal (pendente).
     */
    public static function weeklyMaterialReminder(string $managerName, string $cycleDate, array $sites): string
    {
        $totalObras = count($sites);

        $sitesHtml = '';
        foreach ($sites as $s) {
            $sitesHtml .= '<tr><td style="padding: 14px 20px; border-bottom:1px solid #eee;">'
                . '<p style="margin:0 0 6px; font-size:14px; color:#3a3b4e; font-weight:600;">🏗️ ' . htmlspecialchars($s['site']) . '</p>'
                . '<a href="' . $s['url'] . '" style="display:inline-block; background-color:#dc3545; color:#ffffff; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:600; font-size:13px;">Preencher Agora</a>'
                . '</td></tr>';
        }

        $body = '<table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd; border-radius:6px; margin-bottom:20px; border:1px solid #ffe69c;">'
            . '<tr><td style="padding: 18px 20px;">'
            . '<p style="margin:0; font-size:15px; color:#856404; font-weight:600;">⚠️ ' . htmlspecialchars($managerName) . ', você ainda NÃO preencheu a lista!</p>'
            . '<p style="margin:6px 0 0; font-size:13px; color:#856404;">A lista de materiais do ciclo de <strong>' . htmlspecialchars($cycleDate) . '</strong> está pendente'
            . ($totalObras > 1 ? ' (' . $totalObras . ' obras)' : '') . '.</p>'
            . '</td></tr></table>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">'
            . $sitesHtml
            . '</table>'
            . '<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Por favor, preencha o quanto antes para garantir o pedido dos materiais a tempo.</p>';

        return self::wrap('⚠️ Pendente - Lista Semanal de Materiais', $body);
    }
}
