-- ============================================================
-- Migration: Sistema de Estoque por Obra + Contatos de Fornecedores
-- Data: 2026-07-27
-- ============================================================

-- ----------------------------------------------------------
-- 1. Tabela de Estoque por Obra
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `material_id` INT NOT NULL,
    `construction_site_id` INT NOT NULL,
    `quantity` DECIMAL(12,3) NOT NULL DEFAULT 0,
    `min_quantity` DECIMAL(12,3) NOT NULL DEFAULT 0 COMMENT 'Quantidade mínima (alerta)',
    `location_detail` VARCHAR(255) NULL COMMENT 'Localização específica no almoxarifado',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_material_site` (`material_id`, `construction_site_id`),
    KEY `idx_site` (`construction_site_id`),
    KEY `idx_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabela de Movimentações de Estoque
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `material_id` INT NOT NULL,
    `from_site_id` INT NULL COMMENT 'Obra de origem (NULL se entrada externa)',
    `to_site_id` INT NULL COMMENT 'Obra destino (NULL se saída/uso)',
    `quantity` DECIMAL(12,3) NOT NULL,
    `type` ENUM('entry','exit','transfer','adjustment') NOT NULL,
    `status` ENUM('pending','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `order_id` INT NULL COMMENT 'Pedido de compra vinculado',
    `requested_by` VARCHAR(255) NULL COMMENT 'Quem solicitou',
    `transported_by` VARCHAR(255) NULL COMMENT 'Quem transportou (Wilton)',
    `delivered_by` VARCHAR(255) NULL COMMENT 'Quem confirmou entrega',
    `transit_at` DATETIME NULL COMMENT 'Quando saiu para transporte',
    `delivered_at` DATETIME NULL COMMENT 'Quando foi entregue',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_movement_material` (`material_id`),
    KEY `idx_movement_from` (`from_site_id`),
    KEY `idx_movement_to` (`to_site_id`),
    KEY `idx_movement_order` (`order_id`),
    KEY `idx_movement_status` (`status`),
    KEY `idx_movement_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabela de Contatos/Vendedores de Fornecedores
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `supplier_contacts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(255) NULL,
    `role` VARCHAR(100) NULL DEFAULT 'vendedor' COMMENT 'Cargo/função do contato',
    `notes` TEXT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_supplier` (`supplier_id`),
    KEY `idx_contact_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Coluna source_type nos itens do pedido (compra vs estoque)
-- ----------------------------------------------------------
-- Usar procedure para adicionar colunas apenas se não existirem
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS _add_stock_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items' AND COLUMN_NAME = 'source_type') THEN
        ALTER TABLE `purchase_order_items` ADD COLUMN `source_type` ENUM('purchase','stock_use','stock_transfer') NULL DEFAULT NULL COMMENT 'Origem: compra normal, uso de estoque, ou transferência' AFTER `approved_supplier_id`;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items' AND COLUMN_NAME = 'stock_from_site_id') THEN
        ALTER TABLE `purchase_order_items` ADD COLUMN `stock_from_site_id` INT NULL COMMENT 'Obra de onde saiu o material (se estoque)' AFTER `source_type`;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items' AND COLUMN_NAME = 'stock_movement_id') THEN
        ALTER TABLE `purchase_order_items` ADD COLUMN `stock_movement_id` INT UNSIGNED NULL COMMENT 'Referência à movimentação de estoque' AFTER `stock_from_site_id`;
    END IF;
END //
DELIMITER ;
CALL _add_stock_columns();
DROP PROCEDURE IF EXISTS _add_stock_columns;

-- ----------------------------------------------------------
-- 5. Tabela de mensagens de cotação (para auto-fill GPT)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quote_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `contact_id` INT UNSIGNED NULL COMMENT 'Vendedor específico',
    `raw_messages` TEXT NOT NULL COMMENT 'Mensagens coladas do WhatsApp',
    `parsed_data` JSON NULL COMMENT 'Dados extraídos pela IA',
    `parsed_at` DATETIME NULL,
    `applied` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se os dados foram aplicados ao formulário',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qm_order` (`order_id`),
    KEY `idx_qm_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Tabela de log de envios de cotação via webhook
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quote_webhook_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `contact_id` INT UNSIGNED NULL,
    `message_sent` TEXT NOT NULL,
    `webhook_url` VARCHAR(500) NOT NULL,
    `response_code` INT NULL,
    `response_body` TEXT NULL,
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qwl_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Settings padrão para transporte (Wilton)
-- ----------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
    ('orders_transport_emails', ''),
    ('orders_transport_webhook', ''),
    ('orders_transport_phone', ''),
    ('orders_transport_phone_name', ''),
    ('orders_quote_send_webhook', ''),
    ('orders_quote_default_message', 'Olá! Bom dia, tudo bem?\n\nPrecisamos de cotação para os seguintes itens:\n\n{items_list}\n\nObra: {construction_site}\nPedido: {order_code}\n\nPoderia nos enviar o orçamento?\n\nObrigado!')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
