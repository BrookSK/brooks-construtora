-- ============================================================
-- Migration: Campo show_images na tabela magazine_pages
-- Data: 2026-07-27
-- ============================================================

ALTER TABLE `magazine_pages` ADD COLUMN `show_images` VARCHAR(1) DEFAULT '1' AFTER `caption`;
