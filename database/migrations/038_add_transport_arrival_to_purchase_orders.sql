-- Migration: Adicionar marcações "Em Transporte" e "Chegou" aos pedidos
-- Data: 2026-08-04

ALTER TABLE `purchase_orders`
    ADD COLUMN `in_transport_at` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora marcado como em transporte',
    ADD COLUMN `in_transport_by` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Quem marcou como em transporte',
    ADD COLUMN `arrived_at` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora marcado como chegou na obra',
    ADD COLUMN `arrived_by` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Quem marcou como chegou na obra';
