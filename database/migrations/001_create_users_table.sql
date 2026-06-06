-- Migration: Criação da tabela de usuários
-- Data: 2026-06-06

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'designer', 'editor') NOT NULL DEFAULT 'editor',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cria o super admin padrão (senha: Brooks@2026)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `active`, `created_at`) VALUES
('Super Admin', 'admin@brooksconstrutora.com.br', '$2y$12$doigl9mgJ.41kbqnwoWffe6se4NsLwOk4fo/0rmzpZrDYZkarmZyO', 'super_admin', 1, NOW());
