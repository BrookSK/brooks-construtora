-- Migration: Adiciona campos de sugestão de imagem nas páginas da revista
-- Data: 2026-06-07
-- Estes campos armazenam a descrição gerada pela IA para que as imagens
-- possam ser geradas automaticamente em background após a criação do conteúdo.

ALTER TABLE `magazine_pages` ADD COLUMN IF NOT EXISTS `image_suggestion` TEXT NULL AFTER `caption`;
ALTER TABLE `magazine_pages` ADD COLUMN IF NOT EXISTS `image_suggestion_2` TEXT NULL AFTER `image_suggestion`;
