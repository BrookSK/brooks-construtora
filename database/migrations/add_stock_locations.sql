-- ============================================================
-- Migration: Tabela de Depósitos/Estoques (desvinculado de obras)
-- Data: 2026-07-27
-- ============================================================

-- ----------------------------------------------------------
-- 1. Tabela de Depósitos/Estoques
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_locations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nome do estoque (ex: Galpão Central, Estoque Obra X)',
    `code` VARCHAR(20) NULL COMMENT 'Código (EST-000001)',
    `construction_site_id` INT NULL COMMENT 'Obra vinculada (opcional)',
    `address` VARCHAR(500) NULL COMMENT 'Endereço/localização do depósito',
    `responsible_name` VARCHAR(255) NULL COMMENT 'Responsável pelo depósito',
    `notes` TEXT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stock_code` (`code`),
    KEY `idx_stock_site` (`construction_site_id`),
    KEY `idx_stock_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Adicionar coluna stock_location_id na tabela stock_items
-- ----------------------------------------------------------
ALTER TABLE `stock_items` ADD COLUMN `stock_location_id` INT UNSIGNED NULL AFTER `construction_site_id`;
ALTER TABLE `stock_items` DROP INDEX IF EXISTS `uk_material_site`;
ALTER TABLE `stock_items` ADD UNIQUE KEY `uk_material_location` (`material_id`, `stock_location_id`);

-- ----------------------------------------------------------
-- 3. Adicionar coluna stock_location_id em stock_movements
-- ----------------------------------------------------------
ALTER TABLE `stock_movements` ADD COLUMN `from_location_id` INT UNSIGNED NULL AFTER `from_site_id`;
ALTER TABLE `stock_movements` ADD COLUMN `to_location_id` INT UNSIGNED NULL AFTER `to_site_id`;

-- ----------------------------------------------------------
-- 4. Migrar dados existentes:
--    Se já tem stock_items com construction_site_id, criar
--    stock_locations automaticamente
-- ----------------------------------------------------------
INSERT INTO `stock_locations` (`name`, `code`, `construction_site_id`, `active`, `created_at`)
SELECT DISTINCT 
    CONCAT('Estoque ', cs.name),
    CONCAT('EST-', LPAD(cs.id, 6, '0')),
    cs.id,
    1,
    NOW()
FROM `stock_items` si
JOIN `construction_sites` cs ON si.construction_site_id = cs.id
WHERE si.construction_site_id IS NOT NULL
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Vincular stock_items existentes ao stock_location correspondente
UPDATE `stock_items` si
JOIN `stock_locations` sl ON sl.construction_site_id = si.construction_site_id
SET si.stock_location_id = sl.id
WHERE si.stock_location_id IS NULL AND si.construction_site_id IS NOT NULL;
