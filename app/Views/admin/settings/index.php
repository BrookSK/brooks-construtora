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
                    <div style="background:#0a1628; padding:20px; border-radius:8px; display:inline-block;">
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

    <!-- Capa Padrão da Revista -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-card-image"></i> Capa Padrão da Revista</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Esta imagem será usada como capa padrão para toda revista criada (manual ou automática). Você pode alterar a capa de cada revista individualmente depois. Recomendado: JPG ou WEBP, 595×842px (proporção A4).</p>
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div style="background:#222; padding:10px; border-radius:8px; display:inline-block;">
                        <?php $defaultCover = $settings['magazine_default_cover'] ?? ''; ?>
                        <?php if (!empty($defaultCover)): ?>
                            <img src="<?= htmlspecialchars($defaultCover) ?>" alt="Capa Padrão" style="max-width:150px; max-height:200px; border-radius:4px;">
                        <?php else: ?>
                            <div style="width:150px; height:200px; background:#0a1628; border-radius:4px; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.5); font-size:0.75rem;">Sem capa padrão</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2"><?= !empty($defaultCover) ? 'Capa padrão definida' : 'Nenhuma capa padrão' ?></p>
                </div>
                <div class="col-md-8">
                    <div id="default-cover-form">
                        <div class="mb-2">
                            <input type="file" class="form-control" name="magazine_default_cover" id="default-cover-input" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="default-cover-submit">
                            <i class="bi bi-upload"></i> Enviar Capa Padrão
                        </button>
                        <?php if (!empty($defaultCover)): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDefaultCover()">
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

    <!-- Configuração de Perfil -->
    <div class="card mb-4" id="perfil">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-person-circle"></i> Configuração de Perfil</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-4">Esses dados são exibidos na página de Boas-vindas do painel.</p>

            <div class="row g-4 align-items-start">
                <!-- Foto de perfil -->
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <?php $avatarUrl = $profile['photo'] ?? ''; ?>
                        <div id="avatar-preview-wrapper" style="width:100px;height:100px;border-radius:50%;overflow:hidden;margin:0 auto;background:var(--color-primary);display:flex;align-items:center;justify-content:center;border:3px solid #e0e0e0;">
                            <?php if (!empty($avatarUrl)): ?>
                                <img id="avatar-preview" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span id="avatar-initials" style="color:#fff;font-size:2rem;font-weight:700;line-height:1;">
                                    <?= mb_strtoupper(mb_substr($profile['name'] ?? 'U', 0, 1)) ?>
                                </span>
                                <img id="avatar-preview" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mt-2 mb-0"><?= !empty($avatarUrl) ? 'Foto atual' : 'Nenhuma foto' ?></p>
                    </div>
                    <div>
                        <input type="file" class="form-control form-control-sm mb-2" id="avatar-input" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <button type="button" class="btn btn-sm btn-primary" id="avatar-submit">
                                <i class="bi bi-upload"></i> Salvar foto
                            </button>
                            <?php if (!empty($avatarUrl)): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="avatar-remove">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                            <?php endif; ?>
                        </div>
                        <div id="avatar-feedback" class="mt-2 small"></div>
                    </div>
                </div>

                <!-- Nome -->
                <div class="col-md-8">
                    <form method="POST" action="/admin/settings/update-profile">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nome de exibição</label>
                            <input type="text" class="form-control" name="profile_name"
                                   value="<?= htmlspecialchars($profile['name'] ?? '') ?>"
                                   placeholder="Seu nome completo" required maxlength="255">
                            <small class="text-muted">Este nome aparece na saudação da página de Boas-vindas.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">E-mail</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled>
                            <small class="text-muted">O e-mail é gerenciado pela seção de Usuários.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Salvar nome
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Revista - Webhook WhatsApp -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-whatsapp"></i> Revista - Notificação WhatsApp</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Ao publicar uma nova revista, envia notificação via webhook para os assinantes que cadastraram o WhatsApp. O telefone padrão é usado quando nenhum assinante tem telefone cadastrado.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nome (padrão)</label>
                    <input type="text" class="form-control" name="magazine_webhook_phone_name" value="<?= htmlspecialchars($settings['magazine_webhook_phone_name'] ?? '') ?>" placeholder="Ex: Brooks Construtora">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefone (padrão)</label>
                    <input type="text" class="form-control" name="magazine_webhook_phone" value="<?= htmlspecialchars($settings['magazine_webhook_phone'] ?? '') ?>" placeholder="5511999999999">
                    <small class="text-muted">Usado quando nenhum assinante tem WhatsApp</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">URL Webhook</label>
                    <input type="url" class="form-control" name="magazine_webhook_url" value="<?= htmlspecialchars($settings['magazine_webhook_url'] ?? '') ?>" placeholder="https://...">
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

var coverBtn = document.getElementById('default-cover-submit');
if (coverBtn) {
    coverBtn.addEventListener('click', function() {
        var fileInput = document.getElementById('default-cover-input');
        if (!fileInput.files.length) { alert('Selecione um arquivo.'); return; }
        var fd = new FormData();
        fd.append('magazine_default_cover', fileInput.files[0]);
        fetch('/admin/settings/upload-default-cover', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { alert('Capa padrão atualizada!'); location.reload(); }
            else { alert(data.error || 'Erro ao enviar.'); }
        })
        .catch(() => alert('Erro ao enviar.'));
    });
}

function removeDefaultCover() {
    if (!confirm('Remover capa padrão? Novas revistas serão criadas sem capa.')) return;
    fetch('/admin/settings/remove-default-cover', { method: 'POST' })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); })
    .catch(() => alert('Erro.'));
}

// -------------------------------------------------------------------
// Foto de perfil (avatar)
// -------------------------------------------------------------------
(function () {
    var input   = document.getElementById('avatar-input');
    var preview = document.getElementById('avatar-preview');
    var initials = document.getElementById('avatar-initials');
    var feedback = document.getElementById('avatar-feedback');
    var submitBtn = document.getElementById('avatar-submit');
    var removeBtn = document.getElementById('avatar-remove');

    // Prévia local antes de enviar
    if (input) {
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                if (initials) initials.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    // Envio via fetch
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (!input || !input.files.length) {
                setFeedback('Selecione uma imagem primeiro.', 'text-danger');
                return;
            }
            var fd = new FormData();
            fd.append('avatar', input.files[0]);
            submitBtn.disabled = true;
            setFeedback('Enviando…', 'text-muted');
            fetch('/admin/settings/upload-avatar', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    setFeedback('Foto salva!', 'text-success');
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    setFeedback(data.error || 'Erro ao enviar.', 'text-danger');
                    submitBtn.disabled = false;
                }
            })
            .catch(function () {
                setFeedback('Erro na requisição.', 'text-danger');
                submitBtn.disabled = false;
            });
        });
    }

    // Remoção
    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (!confirm('Remover foto de perfil?')) return;
            removeBtn.disabled = true;
            fetch('/admin/settings/remove-avatar', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) { location.reload(); }
                else { setFeedback(data.error || 'Erro ao remover.', 'text-danger'); removeBtn.disabled = false; }
            })
            .catch(function () { setFeedback('Erro na requisição.', 'text-danger'); removeBtn.disabled = false; });
        });
    }

    function setFeedback(msg, cls) {
        if (!feedback) return;
        feedback.textContent = msg;
        feedback.className = 'mt-2 small ' + cls;
    }
})();
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
