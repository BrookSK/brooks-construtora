-- Migration 035: Adiciona Nacionalidade e Estado Civil ao contratante
-- Data: 2026-08-19

ALTER TABLE `clients_projects`
    ADD COLUMN IF NOT EXISTS `client_nationality`    VARCHAR(100) DEFAULT NULL COMMENT 'Nacionalidade do contratante' AFTER `client_email`,
    ADD COLUMN IF NOT EXISTS `client_marital_status` VARCHAR(50)  DEFAULT NULL COMMENT 'Estado civil do contratante'  AFTER `client_nationality`;
