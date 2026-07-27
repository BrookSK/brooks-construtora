<?php $pageTitle = 'Configurações'; $currentPage = 'settings'; ob_start(); ?>

<?php
// Detectar branch/ambiente atual (prioriza domínio, fallback .git/HEAD)
$_currentBranch = 'main';
$_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
if (str_contains($_host, 'plesk.page') || str_contains($_host, 'beta')) {
    $_currentBranch = 'estoque';
} else {
    $_headFile = ROOT_PATH . '/.git/HEAD';
    if (file_exists($_headFile)) {
        $_head = trim(file_get_contents($_headFile));
        if (str_starts_with($_head, 'ref: refs/heads/')) {
            $_currentBranch = substr($_head, strlen('ref: refs/heads/'));
        }
    }
}
$_config = require ROOT_PATH . '/app/Config/app.php';
$_currentDb = $_config['database']['dbname'] ?? '—';
$_isProduction = $_currentBranch === 'main';
?>

<div class="alert alert-<?= $_isProduction ? 'success' : 'warning' ?> d-flex align-items-center gap-3 mb-4" style="border-left: 4px solid;">
    <div>
        <i class="bi bi-<?= $_isProduction ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>" style="font-size: 1.4rem;"></i>
    </div>
    <div>
        <strong>Ambiente:</strong> <?= $_isProduction ? 'Produção' : 'Desenvolvimento' ?>
        &nbsp;|&nbsp;
        <strong>Branch:</strong> <code><?= htmlspecialchars($_currentBranch) ?></code>
        &nbsp;|&nbsp;
        <strong>Banco:</strong> <code><?= htmlspecialchars($_currentDb) ?></code>
    </div>
</div>

<form method="POST" action="/admin/settings/update">
    <!-- SMTP -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-envelope"></i> Configurações de E-mail (SMTP)</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Host SMTP</label>
                    <input type="text" class="form-control" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Porta</label>
                    <input type="number" class="form-control" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Criptografia</label>
                    <select class="form-select" name="smtp_encryption">
                        <option value="tls" <?= $settings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= $settings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="none" <?= $settings['smtp_encryption'] === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuário SMTP</label>
                    <input type="text" class="form-control" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Senha SMTP</label>
                    <input type="password" class="form-control" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail Remetente</label>
                    <input type="email" class="form-control" name="smtp_from_email" value="<?= htmlspecialchars($settings['smtp_from_email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nome Remetente</label>
                    <input type="text" class="form-control" name="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name']) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- OpenAI -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-robot"></i> Configurações OpenAI (ChatGPT / DALL-E)</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Chave API OpenAI</label>
                    <input type="password" class="form-control" name="openai_api_key" value="<?= htmlspecialchars($settings['openai_api_key']) ?>" placeholder="sk-...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modelo de Texto</label>
                    <select class="form-select" name="openai_model">
                        <option value="gpt-4" <?= $settings['openai_model'] === 'gpt-4' ? 'selected' : '' ?>>GPT-4</option>
                        <option value="gpt-4-turbo" <?= $settings['openai_model'] === 'gpt-4-turbo' ? 'selected' : '' ?>>GPT-4 Turbo</option>
                        <option value="gpt-4o" <?= $settings['openai_model'] === 'gpt-4o' ? 'selected' : '' ?>>GPT-4o</option>
                        <option value="gpt-3.5-turbo" <?= $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : '' ?>>GPT-3.5 Turbo</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modelo de Imagens</label>
                    <select class="form-select" name="openai_image_model">
                        <option value="gpt-image-1" <?= ($settings['openai_image_model'] ?? '') === 'gpt-image-1' ? 'selected' : '' ?>>GPT Image 1 (Recomendado)</option>
                        <option value="dall-e-3" <?= ($settings['openai_image_model'] ?? '') === 'dall-e-3' ? 'selected' : '' ?>>DALL-E 3</option>
                        <option value="dall-e-2" <?= ($settings['openai_image_model'] ?? '') === 'dall-e-2' ? 'selected' : '' ?>>DALL-E 2</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo da Revista -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-image"></i> Logo da Revista Digital</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Esta imagem será usada como logo nas capas e contracapas das revistas geradas. Recomendado: PNG ou WEBP com fundo transparente, branca (para aparecer em fundo escuro).</p>
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div style="background:#1a472a; padding:20px; border-radius:8px; display:inline-block;">
                        <?php $magazineLogo = $settings['magazine_logo'] ?? ''; ?>
                        <?php if (!empty($magazineLogo)): ?>
                            <img src="<?= htmlspecialchars($magazineLogo) ?>" alt="Logo Revista" style="max-width:200px; max-height:80px;">
                        <?php else: ?>
                            <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Logo Padrão" style="max-width:200px; max-height:80px;">
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2"><?= !empty($magazineLogo) ? 'Logo customizada' : 'Usando logo padrão' ?></p>
                </div>
                <div class="col-md-8">
                    <div id="magazine-logo-form">
                        <div class="mb-2">
                            <input type="file" class="form-control" name="magazine_logo" id="magazine-logo-input" accept="image/*">
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="magazine-logo-submit">
                            <i class="bi bi-upload"></i> Enviar Logo
                        </button>
                        <?php if (!empty($magazineLogo)): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMagazineLogo()">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notificações -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-bell"></i> E-mails de Notificação</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">E-mails para lembrete de revista gerada</label>
                <textarea class="form-control" name="notification_emails" rows="2" placeholder="email1@empresa.com, email2@empresa.com"><?= htmlspecialchars($settings['notification_emails']) ?></textarea>
                <small class="text-muted">Separe múltiplos e-mails por vírgula. Estes receberão aviso quando uma nova revista for gerada pela IA.</small>
            </div>
        </div>
    </div>

    <!-- Cron / Agendamento -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Cron - Geração Automática</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Token de Segurança do Cron</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="cron_token" value="<?= htmlspecialchars($settings['cron_token'] ?? '') ?>" placeholder="Será gerado automaticamente no primeiro acesso">
                    <button type="button" class="btn btn-outline-secondary" onclick="this.previousElementSibling.value = [...Array(64)].map(() => Math.random().toString(36)[2]).join('')">Gerar Novo</button>
                </div>
                <small class="text-muted">Use este token na URL do cron. Se estiver vazio, será gerado automaticamente ao acessar a URL pela primeira vez.</small>
            </div>
            <div class="alert alert-info mb-3">
                <?php
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $baseUrl = $scheme . '://' . $host;
                    $cronToken = htmlspecialchars($settings['cron_token'] ?? 'SEU_TOKEN');
                ?>
                <strong>URL do Cron:</strong><br>
                <code><?= $baseUrl ?>/cron.php?token=<?= $cronToken ?></code>
                <br><br>
                <strong>Configure no servidor (a cada 10 minutos):</strong><br>
                <code>*/10 * * * * curl -s "<?= $baseUrl ?>/cron.php?token=<?= $cronToken ?>" > /dev/null 2>&1</code>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Última execução:</strong> <?= !empty($settings['cron_last_run']) ? $settings['cron_last_run'] : 'Nunca' ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Última revista gerada:</strong> <?= !empty($settings['cron_last_generated']) ? $settings['cron_last_generated'] : 'Nunca' ?></p>
                </div>
            </div>
            <small class="text-muted">O sistema verifica a frequência configurada em "Agendamento" e só gera quando chegar o dia correto. Mesmo rodando a cada 10 min, só gera 1x por período.</small>
        </div>
    </div>

    <!-- Site -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-globe"></i> Informações do Site</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Título do Site</label>
                    <input type="text" class="form-control" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail de Contato</label>
                    <input type="email" class="form-control" name="site_email" value="<?= htmlspecialchars($settings['site_email']) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="site_description" rows="2"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="site_phone" value="<?= htmlspecialchars($settings['site_phone']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" name="site_whatsapp" value="<?= htmlspecialchars($settings['site_whatsapp']) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" name="site_address" value="<?= htmlspecialchars($settings['site_address']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Instagram</label>
                    <input type="url" class="form-control" name="site_instagram" value="<?= htmlspecialchars($settings['site_instagram']) ?>" placeholder="https://instagram.com/...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Facebook</label>
                    <input type="url" class="form-control" name="site_facebook" value="<?= htmlspecialchars($settings['site_facebook']) ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" class="form-control" name="site_linkedin" value="<?= htmlspecialchars($settings['site_linkedin']) ?>" placeholder="https://linkedin.com/...">
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg"></i> Salvar Configurações
        </button>
    </div>
</form>

<script>
var logoBtn = document.getElementById('magazine-logo-submit');
if (logoBtn) {
    logoBtn.addEventListener('click', function() {
        var fileInput = document.getElementById('magazine-logo-input');
        if (!fileInput.files.length) { alert('Selecione um arquivo.'); return; }
        var fd = new FormData();
        fd.append('magazine_logo', fileInput.files[0]);
        fetch('/admin/settings/upload-magazine-logo', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { alert('Logo atualizada!'); location.reload(); }
            else { alert(data.error || 'Erro ao enviar.'); }
        })
        .catch(() => alert('Erro ao enviar.'));
    });
}

function removeMagazineLogo() {
    if (!confirm('Remover logo customizada e voltar para a padrão?')) return;
    fetch('/admin/settings/remove-magazine-logo', { method: 'POST' })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); })
    .catch(() => alert('Erro.'));
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
