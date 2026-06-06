-- Migration: Criação da tabela de configurações
-- Data: 2026-06-06

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(255) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações padrão
INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`) VALUES
('site_title', 'Brooks Construtora', NOW()),
('site_description', 'A Brooks Construtora é uma empresa especializada em reformas e construções de alto padrão.', NOW()),
('site_phone', '', NOW()),
('site_email', '', NOW()),
('site_address', '', NOW()),
('site_instagram', '', NOW()),
('site_facebook', '', NOW()),
('site_linkedin', '', NOW()),
('site_whatsapp', '', NOW()),
('smtp_host', '', NOW()),
('smtp_port', '587', NOW()),
('smtp_username', '', NOW()),
('smtp_password', '', NOW()),
('smtp_encryption', 'tls', NOW()),
('smtp_from_email', '', NOW()),
('smtp_from_name', 'Brooks Construtora', NOW()),
('openai_api_key', '', NOW()),
('openai_model', 'gpt-4', NOW()),
('openai_image_model', 'dall-e-3', NOW()),
('magazine_frequency', 'quinzenal', NOW()),
('magazine_times_per_period', '1', NOW()),
('magazine_day_of_week', '1', NOW()),
('magazine_day_of_month', '1', NOW()),
('notification_emails', '', NOW());
