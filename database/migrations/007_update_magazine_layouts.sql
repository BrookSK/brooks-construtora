-- Migration: Atualiza tipos de layout das páginas da revista
-- Data: 2026-06-07
-- Novos layouts baseados no PDF modelo

ALTER TABLE `magazine_pages` MODIFY COLUMN `layout_type` VARCHAR(50) NOT NULL DEFAULT 'text_image';

-- Tipos de layout:
-- cover: Capa (foto background full, título NÚCLEO/ECO, "CONSTRUÇÃO — SUSTENTÁVEL", logo, tema)
-- subcover: Subcapa (variação da capa com layout diferente de título)
-- internal_01: Imagem full-width topo + título uppercase + 2 colunas (texto esq + imagem dir)
-- internal_02: Imagem grande esquerda + textos direita + título bold + texto com imagem pequena
-- internal_03: Título bold grande + subtítulo + texto full + 2 imagens lado a lado
-- internal_04: Imagem full com overlay escuro + título branco sobreposto + textos abaixo
-- internal_05: 2 imagens com legendas + 2 colunas de texto
-- internal_06: Grid 4 imagens (2x2) + texto lateral
-- internal_07: Citação grande + imagem com texto lateral
-- backcover: Contracapa (fundo verde escuro, logo, frase, barra vermelha)

-- Adiciona campo para imagem secundária e subtítulo da página
ALTER TABLE `magazine_pages` ADD COLUMN `image_url_2` VARCHAR(500) NULL AFTER `image_url`;
ALTER TABLE `magazine_pages` ADD COLUMN `subtitle` VARCHAR(500) NULL AFTER `title`;
ALTER TABLE `magazine_pages` ADD COLUMN `caption` VARCHAR(500) NULL AFTER `image_url_2`;
