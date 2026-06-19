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

    public static function purchaseOrderQuote(array $order, array $items, string $quoteUrl): string
    {
        $itemsHtml = '';
        foreach ($items as $i => $item) {
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . ($i + 1) . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['material_name']) . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['specification'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['classification'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px;">' . htmlspecialchars($item['unit'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:8px; border-bottom:1px solid #eee; font-size:13px; text-align:center;">' . number_format($item['quantity'], 0) . '</td>';
            $itemsHtml .= '</tr>';
        }

        $body = <<<HTML
<p style="margin-bottom:15px;">Um novo pedido de materiais foi criado e aguarda cotação de preços.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase;">Pedido</p>
    <p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#666;">Solicitado por: <strong>{$order['created_by_name']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#666;">Data: {$order['created_at']}</p>
</td></tr>
</table>

<p style="margin-bottom:10px;"><strong>Itens do pedido:</strong></p>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">
<tr style="background:#f8f9fa;">
    <th style="padding:8px; font-size:12px; text-align:left;">#</th>
    <th style="padding:8px; font-size:12px; text-align:left;">Material</th>
    <th style="padding:8px; font-size:12px; text-align:left;">Especificação</th>
    <th style="padding:8px; font-size:12px; text-align:left;">Classificação</th>
    <th style="padding:8px; font-size:12px; text-align:left;">Unid.</th>
    <th style="padding:8px; font-size:12px; text-align:center;">Qtd</th>
</tr>
{$itemsHtml}
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$quoteUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Informar Cotação</a>
</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique no botão acima para acessar o formulário de cotação e informar os valores.</p>
HTML;

        return self::wrap("Cotação Pendente - {$order['code']}", $body);
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
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px;">' . htmlspecialchars($item['unit'] ?? '-') . '</td>';
            $itemsHtml .= '<td style="padding:6px 8px; border-bottom:1px solid #eee; font-size:12px; text-align:center;">' . number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) . '</td>';
            $itemsHtml .= '</tr>';
        }

        $body = <<<HTML
<p style="margin-bottom:15px;">Um pedido de materiais foi cotado e aguarda sua aprovação.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border-radius:6px; margin-bottom:20px;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#888; text-transform:uppercase;">Pedido</p>
    <p style="margin:0; font-size:17px; color:#3a3b4e; font-weight:600;">{$order['code']}</p>
    <p style="margin:8px 0 0; font-size:13px; color:#666;">Cotado por: <strong>{$order['quoted_by_name']}</strong></p>
    <p style="margin:4px 0 0; font-size:13px; color:#666;">Solicitado por: {$order['created_by_name']}</p>
</td></tr>
</table>

{$suppliersHtml}

<p style="margin-bottom:8px;"><strong style="font-size:12px;">Itens do pedido:</strong></p>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee; border-radius:6px; margin-bottom:20px;">
<tr style="background:#f8f9fa;">
    <th style="padding:6px 8px; font-size:11px; text-align:left;">#</th>
    <th style="padding:6px 8px; font-size:11px; text-align:left;">Material</th>
    <th style="padding:6px 8px; font-size:11px; text-align:left;">Unid.</th>
    <th style="padding:6px 8px; font-size:11px; text-align:center;">Qtd</th>
</tr>
{$itemsHtml}
</table>

<p style="text-align:center; margin: 25px 0 10px;">
    <a href="{$approvalUrl}" style="display:inline-block; background-color:#3a3b4e; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px;">Analisar e Decidir</a>
</p>

<p style="text-align:center; font-size:12px; color:#999; margin-top:10px;">Clique no botão acima para aprovar ou rejeitar este pedido.</p>
HTML;

        return self::wrap("Aprovação Pendente - {$order['code']}", $body);
    }

    public static function purchaseOrderCompleted(array $order, array $items, string $pdfUrl, string $xlsxUrl = ''): string
    {
        $totalFormatted = number_format($order['total_estimated'] ?? 0, 2, ',', '.');

        $xlsxButton = '';
        if (!empty($xlsxUrl)) {
            $xlsxButton = <<<HTML
<a href="{$xlsxUrl}" style="display:inline-block; background-color:#28a745; color:#ffffff; padding:14px 32px; border-radius:5px; text-decoration:none; font-weight:600; font-size:14px; margin-left:10px;">Baixar Planilha</a>
HTML;
        }

        $body = <<<HTML
<p style="margin-bottom:15px;">O pedido de materiais foi <strong style="color:#28a745;">APROVADO</strong> com sucesso!</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5e9; border-radius:6px; margin-bottom:20px; border:1px solid #c8e6c9;">
<tr><td style="padding: 18px 20px;">
    <p style="margin:0 0 5px; font-size:13px; color:#388e3c; text-transform:uppercase; font-weight:600;">✓ Pedido Aprovado</p>
    <p style="margin:0; font-size:17px; color:#2e7d32; font-weight:600;">{$order['code']}</p>
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
    <p style="margin:8px 0 0; font-size:13px; color:#555;">Fornecedor: <strong>{$order['supplier_name']}</strong></p>
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
    <p style="margin:4px 0 0; font-size:13px; color:#555;">Numero: <strong>{$numberText}</strong></p>
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
}
