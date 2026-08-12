-- Migration: Adiciona campo image_caption à tabela magazine_pages
-- Data: 2026-08-12
-- Usado para legenda de imagens (ex: gráfico na coluna do convidado)

ALTER TABLE `magazine_pages` ADD COLUMN `image_caption` VARCHAR(500) NULL AFTER `caption`;
