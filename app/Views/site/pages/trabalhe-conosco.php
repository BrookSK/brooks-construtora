<?php
$pageTitle = 'Trabalhe Conosco';
$pageDescription = 'Faça parte da equipe Brooks Construtora. Envie seu currículo e venha construir com excelência, tecnologia e cultura de alto padrão.';
$currentPage = 'trabalhe-conosco';
$bodyClass = 'page-trabalhe-conosco';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-4xl); background: linear-gradient(160deg, #0a0a14 0%, #111827 50%, #1e293b 100%);">
    <div class="container">
        <div class="reveal" style="max-width: 650px;">
            <span class="label" style="color: var(--brooks-blue-accent);">Carreiras</span>
            <h1 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-lg);">
                Venha construir com a gente.
            </h1>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.6); line-height: 1.8;">
                A Brooks Construtora busca profissionais comprometidos com excelência, organização e evolução constante. Se você se identifica com nossa cultura, envie seu currículo.
            </p>
        </div>
    </div>
</section>

<!-- Formulário -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="gap: var(--space-3xl); align-items: start;">
            <div>
                <h2 class="headline-section">Envie seu currículo</h2>
                <p style="color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Preencha o formulário abaixo com seus dados. Analisaremos seu perfil e entraremos em contato caso surja uma oportunidade compatível.
                </p>
                
                <div style="background: #f5f5f5; border-radius: var(--radius-lg); padding: var(--space-xl); margin-top: var(--space-xl);">
                    <h4 style="font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-md); font-size: var(--text-base);">O que valorizamos:</h4>
                    <ul style="list-style: none; padding: 0; display: grid; gap: var(--space-sm);">
                        <li style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm); color: var(--brooks-gray-600);">
                            <i data-lucide="check" style="width:16px;height:16px;color:var(--brooks-blue-accent);flex-shrink:0;"></i> Comprometimento e pontualidade
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm); color: var(--brooks-gray-600);">
                            <i data-lucide="check" style="width:16px;height:16px;color:var(--brooks-blue-accent);flex-shrink:0;"></i> Organização e limpeza
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm); color: var(--brooks-gray-600);">
                            <i data-lucide="check" style="width:16px;height:16px;color:var(--brooks-blue-accent);flex-shrink:0;"></i> Vontade de aprender e evoluir
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm); color: var(--brooks-gray-600);">
                            <i data-lucide="check" style="width:16px;height:16px;color:var(--brooks-blue-accent);flex-shrink:0;"></i> Trabalho em equipe
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm); color: var(--brooks-gray-600);">
                            <i data-lucide="check" style="width:16px;height:16px;color:var(--brooks-blue-accent);flex-shrink:0;"></i> Respeito pela cultura da empresa
                        </li>
                    </ul>
                </div>
            </div>
            
            <div>
                <form action="/trabalhe-conosco/enviar" method="POST" enctype="multipart/form-data" style="background: white; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-lg); padding: var(--space-xl);">
                    <div style="display: grid; gap: var(--space-md);">
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">Nome completo *</label>
                            <input type="text" name="name" required placeholder="Seu nome completo" style="width: 100%; padding: 12px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
                        </div>
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">E-mail *</label>
                            <input type="email" name="email" required placeholder="seu@email.com" style="width: 100%; padding: 12px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
                        </div>
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">Telefone / WhatsApp *</label>
                            <input type="tel" name="phone" required placeholder="(11) 99999-9999" style="width: 100%; padding: 12px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
                        </div>
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">Cargo / Área de interesse *</label>
                            <select name="area" required style="width: 100%; padding: 12px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; background: white;">
                                <option value="">Selecione...</option>
                                <option value="Servente">Servente</option>
                                <option value="Pedreiro">Pedreiro</option>
                                <option value="Eletricista">Eletricista</option>
                                <option value="Encanador">Encanador</option>
                                <option value="Pintor">Pintor</option>
                                <option value="Carpinteiro">Carpinteiro</option>
                                <option value="Gesseiro">Gesseiro</option>
                                <option value="Mestre de Obras">Mestre de Obras</option>
                                <option value="Encarregado">Encarregado</option>
                                <option value="Engenheiro Civil">Engenheiro Civil</option>
                                <option value="Técnico em Edificações">Técnico em Edificações</option>
                                <option value="Técnico em Segurança">Técnico em Segurança do Trabalho</option>
                                <option value="Administrativo">Administrativo</option>
                                <option value="Almoxarife">Almoxarife</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">Currículo (PDF) *</label>
                            <input type="file" name="resume" accept=".pdf,.doc,.docx" required style="width: 100%; padding: 10px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm);">
                            <small style="color: var(--brooks-gray-500); font-size: 11px;">Formatos aceitos: PDF, DOC, DOCX (máx. 5MB)</small>
                        </div>
                        <div>
                            <label style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy); display: block; margin-bottom: 6px;">Mensagem (opcional)</label>
                            <textarea name="message" rows="3" placeholder="Conte um pouco sobre você e sua experiência..." style="width: 100%; padding: 12px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none; resize: vertical;"></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary btn--lg" style="width: 100%; margin-top: var(--space-sm);">Enviar Currículo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
