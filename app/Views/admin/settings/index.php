<?php $pageTitle = 'Configurações'; $currentPage = 'settings'; ob_start(); ?>

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
                        <option value="dall-e-3" <?= $settings['openai_image_model'] === 'dall-e-3' ? 'selected' : '' ?>>DALL-E 3</option>
                        <option value="dall-e-2" <?= $settings['openai_image_model'] === 'dall-e-2' ? 'selected' : '' ?>>DALL-E 2</option>
                    </select>
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

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
