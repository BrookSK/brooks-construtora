<?php $pageTitle = 'Contato'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<!-- Page Header -->
<section class="page-header" style="background-image: url('/assets/images/contato-header.jpg');">
    <div class="page-header-overlay"></div>
    <div class="container">
        <h1>Contato</h1>
        <p>Entre em contato conosco para solicitar um orçamento</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <h3 class="mb-4">Envie sua mensagem</h3>
                <form method="POST" action="/contato/enviar">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Mensagem *</label>
                            <textarea class="form-control" name="message" rows="5" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-dark btn-lg">Enviar Mensagem</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-5">
                <h3 class="mb-4">Informações</h3>
                <div class="contact-info">
                    <?php if (!empty($settings['site_phone'] ?? '')): ?>
                    <div class="contact-item">
                        <i class="bi bi-telephone"></i>
                        <div>
                            <strong>Telefone</strong>
                            <p><?= htmlspecialchars($settings['site_phone']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['site_email'] ?? '')): ?>
                    <div class="contact-item">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>E-mail</strong>
                            <p><?= htmlspecialchars($settings['site_email']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['site_address'] ?? '')): ?>
                    <div class="contact-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <strong>Endereço</strong>
                            <p><?= htmlspecialchars($settings['site_address']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['site_whatsapp'] ?? '')): ?>
                    <div class="contact-item">
                        <i class="bi bi-whatsapp"></i>
                        <div>
                            <strong>WhatsApp</strong>
                            <p><a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['site_whatsapp']) ?>" target="_blank"><?= htmlspecialchars($settings['site_whatsapp']) ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
