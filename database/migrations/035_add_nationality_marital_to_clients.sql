-- Migration 035: Adiciona Nacionalidade e Estado Civil ao contratante
-- Data: 2026-08-19
-- Statements separados (sem IF NOT EXISTS); ensureTables ignora "Duplicate column".

ALTER TABLE `clients_projects` ADD COLUMN `client_nationality` VARCHAR(100) DEFAULT NULL COMMENT 'Nacionalidade do contratante';
ALTER TABLE `clients_projects` ADD COLUMN `client_marital_status` VARCHAR(50) DEFAULT NULL COMMENT 'Estado civil do contratante';
