-- Migration 041: Link público compartilhável para edição do contrato
-- Permite que terceiros (ex.: jurídico) editem e salvem sem login.
-- Data: 2026-08-29

ALTER TABLE `generated_contracts`
    ADD COLUMN IF NOT EXISTS `share_token`   VARCHAR(64) DEFAULT NULL COMMENT 'Token do link público de edição' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `share_enabled` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'Se o link público está ativo' AFTER `share_token`;

-- Índice único para busca rápida por token (ignora nulos no MySQL)
CREATE INDEX `idx_share_token` ON `generated_contracts` (`share_token`);
