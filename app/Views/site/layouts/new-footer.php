</main><!-- #main-content -->

<!-- Footer -->
<footer class="site-footer" role="contentinfo">
    <div class="container">
        
        <!-- Main Footer Grid -->
        <div class="footer-main">
            <!-- Brand Column -->
            <div class="footer-brand">
                <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora" class="footer-brand__logo" width="180" height="51">
                <p class="footer-brand__text">
                    Ecossistema completo de inovação para a construção civil de alto padrão. Tecnologia, processos e excelência há mais de 10 anos em São Paulo.
                </p>
                <div class="footer-social">
                    <a href="https://www.instagram.com/brooksconstrutora/" target="_blank" rel="noopener noreferrer" class="footer-social__link" aria-label="Instagram">
                        <i data-lucide="instagram" style="width:18px;height:18px;"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/brooksconstrutora/" target="_blank" rel="noopener noreferrer" class="footer-social__link" aria-label="LinkedIn">
                        <i data-lucide="linkedin" style="width:18px;height:18px;"></i>
                    </a>
                    <a href="https://www.youtube.com/@brooksconstrutora" target="_blank" rel="noopener noreferrer" class="footer-social__link" aria-label="YouTube">
                        <i data-lucide="youtube" style="width:18px;height:18px;"></i>
                    </a>
                </div>
                
                <!-- Newsletter -->
                <div class="footer-newsletter">
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.4); margin-bottom: var(--space-sm);">Assine a Revista Brooks</p>
                    <form action="/newsletter/subscribe" method="POST" class="footer-newsletter__form" id="footer-newsletter-form">
                        <input type="email" name="email" placeholder="Seu melhor e-mail" required class="footer-newsletter__input" aria-label="E-mail para newsletter">
                        <button type="submit" class="footer-newsletter__btn">Assinar Gratuitamente</button>
                    </form>
                </div>
            </div>
            
            <!-- Institucional -->
            <div class="footer-col">
                <h4 class="footer-col__title">Institucional</h4>
                <ul class="footer-col__list">
                    <li><a href="/sobre">Sobre a Brooks</a></li>
                    <li><a href="/cultura">Cultura</a></li>
                    <li><a href="/projetos">Projetos</a></li>
                    <li><a href="/revista">Revista Digital</a></li>
                    <li><a href="/contato">Contato</a></li>
                </ul>
            </div>
            
            <!-- Ecossistema -->
            <div class="footer-col">
                <h4 class="footer-col__title">Ecossistema</h4>
                <ul class="footer-col__list">
                    <li><a href="/vetriks">Vetriks</a></li>
                    <li><a href="/forca-estrutural">Força Estrutural</a></li>
                    <li><a href="/academy">Brooks Academy</a></li>
                    <li><a href="/matterport">Matterport</a></li>
                    <li><a href="https://vetriks.com.br/" target="_blank" rel="noopener">Vetriks App</a></li>
                </ul>
            </div>
            
            <!-- Contato -->
            <div class="footer-col">
                <h4 class="footer-col__title">Contato</h4>
                <ul class="footer-col__list">
                    <?php $whatsapp = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
                    <?php $phone = !empty($settings['site_phone']) ? $settings['site_phone'] : '(11) 99339-2659'; ?>
                    <?php $email = !empty($settings['site_email']) ? $settings['site_email'] : 'contato@brooksconstrutora.com.br'; ?>
                    <li><a href="https://api.whatsapp.com/send?phone=<?= $whatsapp ?>&text=Ol%C3%A1!" target="_blank"><?= $phone ?></a></li>
                    <li><a href="mailto:<?= $email ?>"><?= $email ?></a></li>
                    <li style="color: rgba(255,255,255,0.4); font-size: var(--text-xs); line-height: 1.6; margin-top: var(--space-sm);">
                        Av. Brigadeiro Faria Lima, 1811<br>
                        Cj. 910 - Jardim Paulistano<br>
                        São Paulo/SP - 01452-001
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <p class="footer-bottom__copy">&copy; <?= date('Y') ?> Brooks Construtora. CNPJ 24.811.527/0001-64. Todos os direitos reservados.</p>
            <div class="footer-bottom__links">
                <a href="/politica-privacidade">Política de Privacidade</a>
                <a href="/termos">Termos de Uso</a>
                <a href="/admin/login">Área Restrita</a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Buttons -->
<!-- WhatsApp - Left -->
<a href="https://api.whatsapp.com/send?phone=<?= $whatsapp ?? '5511993392659' ?>&text=Ol%C3%A1%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es." 
   target="_blank" 
   rel="noopener noreferrer"
   class="floating-btn floating-btn--whatsapp"
   aria-label="Contato via WhatsApp">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<!-- Chat Bot - Right -->
<button class="floating-btn floating-btn--chat" id="chat-toggle" aria-label="Assistente virtual">
    <i data-lucide="message-circle" style="width:24px;height:24px;"></i>
</button>

<!-- Chat Widget (FAQ rápido) -->
<div class="chat-widget" id="chat-widget" aria-hidden="true">
    <div class="chat-widget__header">
        <div class="chat-widget__avatar">
            <i data-lucide="message-circle" style="width:20px;height:20px;"></i>
        </div>
        <div>
            <div class="chat-widget__name">Dúvidas Rápidas</div>
            <div class="chat-widget__status">Selecione uma pergunta</div>
        </div>
        <button class="chat-widget__close" id="chat-close" aria-label="Fechar chat">&times;</button>
    </div>
    <div class="chat-widget__body" id="chat-body">
        <div class="chat-widget__message chat-widget__message--bot">
            <p>Olá! 👋 Selecione uma das perguntas abaixo ou fale conosco pelo WhatsApp.</p>
        </div>
        <div class="chat-widget__questions" id="chat-questions">
            <button class="chat-widget__question-btn" data-answer="Realizamos reformas completas de alto padrão em imóveis totalmente desocupados — residenciais, corporativos e outros. Atendemos São Paulo e região.">Quais serviços vocês oferecem?</button>
            <button class="chat-widget__question-btn" data-answer="A Brooks atua há mais de 10 anos no mercado. Temos centenas de obras entregues, 5 estrelas no Google e zero reclamações no Reclame Aqui.">Há quanto tempo a Brooks existe?</button>
            <button class="chat-widget__question-btn" data-answer="Atendemos São Paulo e região metropolitana. Nosso escritório fica na Av. Brigadeiro Faria Lima, 1811 - Jardim Paulistano.">Qual a área de atuação?</button>
            <button class="chat-widget__question-btn" data-answer="Sim! Solicite um orçamento sem compromisso pelo nosso formulário de contato ou WhatsApp: (11) 99339-2659.">Como solicitar um orçamento?</button>
            <button class="chat-widget__question-btn" data-answer="A Vetriks é nossa tecnologia própria de gestão de obras. Um sistema completo, integrado e com IA, criado dentro da Brooks para resolver problemas reais da construção.">O que é a Vetriks?</button>
        </div>
    </div>
    <div class="chat-widget__footer" style="justify-content: center; padding: var(--space-md);">
        <?php $whatsappChat = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
        <a href="https://api.whatsapp.com/send?phone=<?= $whatsappChat ?>&text=Ol%C3%A1!" target="_blank" class="btn btn--primary btn--sm" style="width: 100%; border-radius: var(--radius-full); font-size: var(--text-xs);">
            Falar pelo WhatsApp
        </a>
    </div>
</div>

<!-- Scripts -->
<script src="/assets/js/brooks.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
</body>
</html>
