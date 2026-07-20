-- Migration: Adicionar campos de revisão financeira aos pedidos
-- Data: 2026-07-20

ALTER TABLE `purchase_orders` 
ADD COLUMN `financial_reviewed_at` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora da revisão pelo financeiro',
ADD COLUMN `financial_reviewed_by` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome do usuário que revisou';
