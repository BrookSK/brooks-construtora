-- ============================================================
-- Migration: Campo WhatsApp na newsletter + webhook da revista
-- Data: 2026-07-27
-- ============================================================

-- 1. Adicionar coluna phone na tabela de assinantes
ALTER TABLE `newsletter_subscribers` ADD COLUMN `phone` VARCHAR(50) NULL AFTER `name`;

-- 2. Settings para webhook da revista
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
    ('magazine_webhook_url', ''),
    ('magazine_webhook_phone', ''),
    ('magazine_webhook_phone_name', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 3. Token de atualização pra assinantes cadastrarem WhatsApp via link
ALTER TABLE `newsletter_subscribers` ADD COLUMN `update_token` VARCHAR(64) NULL AFTER `phone`;
