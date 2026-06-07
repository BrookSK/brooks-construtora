-- Migration: Adiciona terceiro campo de imagem nas páginas
-- Data: 2026-06-07

ALTER TABLE `magazine_pages` ADD COLUMN `image_url_3` VARCHAR(500) NULL AFTER `image_url_2`;
