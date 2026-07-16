-- Migration: Criação da tabela de checklists de limpeza
-- Data: 2026-07-16

CREATE TABLE IF NOT EXISTS `cleaning_checklists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `performed_at` DATE NOT NULL COMMENT 'Data da realização',
    `responsible_name` VARCHAR(255) NOT NULL COMMENT 'Responsável pela atividade',
    `inspector_name` VARCHAR(255) NULL COMMENT 'Responsável pela inspeção',
    `sectors` JSON NOT NULL COMMENT 'Setores realizados (array)',
    `items` JSON NOT NULL COMMENT 'Itens do checklist com status C/NC/NA e observações',
    `signature_data` TEXT NULL COMMENT 'Assinatura do inspetor (base64)',
    `user_id` INT UNSIGNED NULL COMMENT 'Usuário que registrou',
    `observations` TEXT NULL COMMENT 'Observações gerais',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
