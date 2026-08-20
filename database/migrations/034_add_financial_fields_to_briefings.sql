-- Migration: Adiciona campos financeiros (entrada, desconto) à tabela briefings
-- Data: 2026-08-19

ALTER TABLE `briefings`
    ADD COLUMN `down_payment`      DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor da entrada R$' AFTER `contract_value`,
    ADD COLUMN `discount_value`    DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor do desconto R$' AFTER `down_payment`,
    ADD COLUMN `discount_percent`  DECIMAL(5,2)  DEFAULT NULL COMMENT 'Percentual de desconto (%)' AFTER `discount_value`;
