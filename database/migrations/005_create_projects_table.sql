-- Migration: Criação das tabelas de projetos
-- Data: 2026-06-06

CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `category` VARCHAR(100) NULL,
    `featured_image` VARCHAR(500) NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `caption` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projetos existentes do WordPress
INSERT INTO `projects` (`title`, `slug`, `description`, `category`, `active`, `sort_order`, `created_at`) VALUES
('Projeto Joia Bergamo', 'projeto-joia-bergamo-2', 'Reforma completa de alto padrão no condomínio Joia Bergamo.', 'Reforma Residencial', 1, 1, NOW()),
('Projeto Joia Bergamo - Reforma RSVP', 'projeto-joia-bergamo-reforma-rsvp', 'Reforma RSVP no condomínio Joia Bergamo.', 'Reforma Residencial', 1, 2, NOW()),
('Projeto Norah Carneiro', 'projeto-norah-carneiro', 'Projeto residencial Norah Carneiro.', 'Construção Residencial', 1, 3, NOW()),
('Projeto Rocha Andrade', 'projeto-rocha-andrade', 'Projeto residencial Rocha Andrade.', 'Construção Residencial', 1, 4, NOW()),
('Reforma Completa de Mansão no Alphaville', 'reforma-completa-de-mansao-no-alphaville', 'Reforma completa de mansão de alto padrão no Alphaville.', 'Reforma Residencial', 1, 5, NOW()),
('Reforma Corporativa - Cafeteria do Palácio dos Bandeirantes', 'reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes', 'Reforma corporativa da cafeteria no Palácio dos Bandeirantes.', 'Reforma Corporativa', 1, 6, NOW()),
('Reforma Corporativa de Escritório no Itaim Bibi', 'reforma-corporativa-de-escritorio-no-itaim-bibi', 'Reforma corporativa de escritório de alto padrão no Itaim Bibi.', 'Reforma Corporativa', 1, 7, NOW());
