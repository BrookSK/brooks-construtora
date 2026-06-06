    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <h3>Receba nossas novidades</h3>
                    <p>Inscreva-se para receber nossa revista digital e ficar por dentro das tendências em construção e reformas.</p>
                </div>
                <div class="col-lg-7">
                    <form class="newsletter-form" id="newsletter-form" method="POST" action="/newsletter/subscribe">
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Seu nome">
                            <input type="email" class="form-control" name="email" placeholder="Seu melhor e-mail" required>
                            <button type="submit" class="btn btn-newsletter">Inscrever-se</button>
                        </div>
                        <div class="newsletter-message mt-2" id="newsletter-message"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <img src="/assets/images/logo-brooks-white.svg" alt="Brooks Construtora" class="footer-logo">
                        <p class="mt-3">A Brooks Construtora é uma empresa especializada em reformas e construções de alto padrão, inserida no mercado de engenharia civil.</p>
                    </div>
                </div>
                <div class="col-lg-3">
                    <h5>Links Rápidos</h5>
                    <ul class="footer-links">
                        <li><a href="/">Home</a></li>
                        <li><a href="/sobre">Sobre</a></li>
                        <li><a href="/projetos">Projetos</a></li>
                        <li><a href="/revista">Revista</a></li>
                        <li><a href="/contato">Contato</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h5>Redes Sociais</h5>
                    <ul class="footer-links">
                        <?php if (!empty($settings['site_instagram'] ?? '')): ?>
                            <li><a href="<?= $settings['site_instagram'] ?>" target="_blank"><i class="bi bi-instagram"></i> Instagram</a></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['site_facebook'] ?? '')): ?>
                            <li><a href="<?= $settings['site_facebook'] ?>" target="_blank"><i class="bi bi-facebook"></i> Facebook</a></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['site_linkedin'] ?? '')): ?>
                            <li><a href="<?= $settings['site_linkedin'] ?>" target="_blank"><i class="bi bi-linkedin"></i> LinkedIn</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Contato</h5>
                    <ul class="footer-links footer-contact">
                        <?php if (!empty($settings['site_phone'] ?? '')): ?>
                            <li><i class="bi bi-telephone"></i> <?= htmlspecialchars($settings['site_phone']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['site_email'] ?? '')): ?>
                            <li><i class="bi bi-envelope"></i> <?= htmlspecialchars($settings['site_email']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($settings['site_address'] ?? '')): ?>
                            <li><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($settings['site_address']) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Brooks Construtora. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
