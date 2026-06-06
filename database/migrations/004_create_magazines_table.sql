-- Migration: Criação das tabelas de revistas
-- Data: 2026-06-06

CREATE TABLE IF NOT EXISTS `magazine_topics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `magazines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(500) NOT NULL,
    `subtitle` VARCHAR(500) NULL,
    `topic_id` INT UNSIGNED NULL,
    `cover_image` VARCHAR(500) NULL,
    `status` ENUM('draft', 'generated', 'review', 'approved', 'published') NOT NULL DEFAULT 'draft',
    `generated_by` ENUM('ai', 'manual') NOT NULL DEFAULT 'ai',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `published_by` INT UNSIGNED NULL,
    `published_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`topic_id`) REFERENCES `magazine_topics`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`published_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `magazine_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `magazine_id` INT UNSIGNED NOT NULL,
    `page_number` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NULL,
    `content` TEXT NULL,
    `image_url` VARCHAR(500) NULL,
    `layout_type` ENUM('cover', 'subcover', 'text_image', 'image_text', 'full_image', 'full_text', 'backcover') NOT NULL DEFAULT 'text_image',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`magazine_id`) REFERENCES `magazines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
