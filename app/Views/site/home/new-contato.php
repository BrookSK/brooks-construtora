<?php
$pageTitle = 'Contato';
$pageDescription = 'Entre em contato com a Brooks Construtora. Solicite um orçamento sem compromisso para sua reforma ou construção de alto padrão.';
$currentPage = 'contato';
$bodyClass = 'page-contato';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Page Hero -->
<section class="section" style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-3xl); background: var(--brooks-off-white);">
    <div class="container">
        <div class="reveal">
            <span class="label">Contato</span>
            <h1 class="headline-section">Vamos conversar sobre o seu projeto.</h1>
            <p class="subtitle">Sem compromisso, com toda a transparência que você merece. Nossa equipe está pronta para atender.</p>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="section">
    <div class="container">
        <div class="grid grid--2" style="gap: var(--space-4xl);">
            <!-- Form -->
            <div class="reveal-left">
                <?php if (!empty($flash)): ?>
                    <div style="padding: 16px 20px; margin-bottom: var(--space-xl); border-radius: var(--radius-md); background: <?= $flash['type'] === 'success' ? '#d4edda' : '#fee2e2' ?>; color: <?= $flash['type'] === 'success' ? '#065f46' : '#991b1b' ?>; font-size: var(--text-sm);">
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>
                
                <h2 class="headline-subsection">Envie sua mensagem</h2>
                <p style="color: var(--brooks-gray-500); margin-bottom: var(--space-xl);">Preencha o formulário abaixo. Retornaremos em até 24 horas.</p>
                
                <form method="POST" action="/contato/enviar" style="display: grid; gap: var(--space-lg);">
                    <div class="grid grid--2" style="gap: var(--space-lg);">
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: 500; color: var(--brooks-gray-700); margin-bottom: var(--space-sm);">Nome *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; transition: border-color var(--transition-fast);">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-sm); font-weight: 500; color: var(--brooks-gray-700); margin-bottom: var(--space-sm);">E-mail *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; transition: border-color var(--transition-fast);">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: 500; color: var(--brooks-gray-700); margin-bottom: var(--space-sm);">Telefone / WhatsApp</label>
                        <input type="text" name="phone" style="width: 100%; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; transition: border-color var(--transition-fast);">
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-sm); font-weight: 500; color: var(--brooks-gray-700); margin-bottom: var(--space-sm);">Mensagem *</label>
                        <textarea name="message" required rows="6" style="width: 100%; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; resize: vertical; font-family: inherit; transition: border-color var(--transition-fast);"></textarea>
                    </div>
                    <div>
                        <button type="submit" class="btn btn--primary btn--lg" style="width: 100%;">Enviar Mensagem</button>
                    </div>
                </form>
            </div>
            
            <!-- Info -->
            <div class="reveal-right">
                <div style="background: var(--brooks-off-white); border-radius: var(--radius-xl); padding: var(--space-2xl); margin-bottom: var(--space-xl);">
                    <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-lg);">Informações de Contato</h3>
                    
                    <?php $whatsappContato = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
                    <?php $phoneContato = !empty($settings['site_phone']) ? $settings['site_phone'] : '(11) 99339-2659'; ?>
                    
                    <div style="display: flex; flex-direction: column; gap: var(--space-lg);">
                        <div style="display: flex; align-items: flex-start; gap: var(--space-md);">
                            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--brooks-blue); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="phone" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">WhatsApp</p>
                                <a href="https://api.whatsapp.com/send?phone=<?= $whatsappContato ?>&text=Ol%C3%A1!" target="_blank" style="color: var(--brooks-blue-accent); font-size: var(--text-sm);"><?= $phoneContato ?></a>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-400);">Mariana ou Kauê</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: var(--space-md);">
                            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--brooks-blue); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="mail" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">E-mail</p>
                                <a href="mailto:<?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?>" style="color: var(--brooks-blue-accent); font-size: var(--text-sm);"><?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?></a>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: var(--space-md);">
                            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--brooks-blue); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="map-pin" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Endereço</p>
                                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">
                                    Av. Brigadeiro Faria Lima, 1811<br>
                                    Conjunto 910 - Jardim Paulistano<br>
                                    CEP 01452-001 - São Paulo/SP
                                </p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: var(--space-md);">
                            <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--brooks-blue); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="clock" style="width:18px;height:18px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Horário</p>
                                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Segunda a Sexta: 8h às 18h</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Map -->
                <div style="border-radius: var(--radius-xl); overflow: hidden; height: 250px; background: var(--brooks-gray-100);">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3656.8!2d-46.6905!3d-23.5718!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDM0JzE4LjUiUyA0NsKwNDEnMjUuOCJX!5e0!3m2!1spt-BR!2sbr!4v1"
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Localização Brooks Construtora">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
