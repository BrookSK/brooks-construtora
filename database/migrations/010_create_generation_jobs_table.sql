-- Migration: Tabela de jobs de geração em background
-- Data: 2026-06-07
-- Rastreia o progresso de geração de revistas (conteúdo + imagens)

CREATE TABLE IF NOT EXISTS `generation_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `magazine_id` INT UNSIGNED NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'magazine_full',
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `total_steps` INT UNSIGNED NOT NULL DEFAULT 0,
    `current_step` INT UNSIGNED NOT NULL DEFAULT 0,
    `current_step_label` VARCHAR(255) NULL,
    `error_message` TEXT NULL,
    `started_by` INT UNSIGNED NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`magazine_id`) REFERENCES `magazines`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`started_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
