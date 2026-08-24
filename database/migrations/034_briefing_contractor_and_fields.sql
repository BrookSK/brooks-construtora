-- Migration 034: Empresa contratada + campos adicionais no briefing
-- Demanda #61 — 2026-08-19
-- OBS: cada ALTER é um statement separado (sem IF NOT EXISTS, que não é
-- suportado em MySQL 5.7 / MariaDB antigo). O ensureTables() executa cada
-- statement em try/catch e ignora erros de "Duplicate column".

CREATE TABLE IF NOT EXISTS `contractor_companies` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_name`          VARCHAR(255) NOT NULL                    COMMENT 'Razão social',
    `trade_name`            VARCHAR(255) DEFAULT NULL                COMMENT 'Nome fantasia',
    `cnpj`                  VARCHAR(20)  DEFAULT NULL                COMMENT 'CNPJ (somente dígitos)',
    `address`               VARCHAR(255) DEFAULT NULL                COMMENT 'Logradouro',
    `address_number`        VARCHAR(20)  DEFAULT NULL                COMMENT 'Número',
    `complement`            VARCHAR(100) DEFAULT NULL                COMMENT 'Complemento',
    `neighborhood`          VARCHAR(100) DEFAULT NULL                COMMENT 'Bairro',
    `city`                  VARCHAR(100) DEFAULT NULL                COMMENT 'Cidade',
    `state`                 VARCHAR(2)   DEFAULT NULL                COMMENT 'UF',
    `cep`                   VARCHAR(10)  DEFAULT NULL                COMMENT 'CEP (somente dígitos)',
    `phone`                 VARCHAR(30)  DEFAULT NULL                COMMENT 'Telefone',
    `email`                 VARCHAR(255) DEFAULT NULL                COMMENT 'E-mail',
    `representative_name`   VARCHAR(255) DEFAULT NULL                COMMENT 'Representante legal',
    `representative_role`   VARCHAR(100) DEFAULT NULL                COMMENT 'Cargo do representante',
    `active`                TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company_name` (`company_name`),
    INDEX `idx_cnpj`         (`cnpj`),
    INDEX `idx_active`       (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `briefings` ADD COLUMN `contractor_company_id` INT UNSIGNED DEFAULT NULL COMMENT 'FK → contractor_companies';
ALTER TABLE `briefings` ADD COLUMN `discount_value` DECIMAL(15,2) DEFAULT NULL COMMENT 'Desconto em R$';
ALTER TABLE `briefings` ADD COLUMN `discount_percent` DECIMAL(5,2) DEFAULT NULL COMMENT 'Desconto em %';
ALTER TABLE `briefings` ADD COLUMN `payment_method` VARCHAR(255) DEFAULT NULL COMMENT 'Forma de pagamento';
ALTER TABLE `briefings` ADD COLUMN `project_number` VARCHAR(50) DEFAULT NULL COMMENT 'Nº orçamento/projeto';
ALTER TABLE `briefings` ADD COLUMN `responsible_name` VARCHAR(255) DEFAULT NULL COMMENT 'Responsável';
ALTER TABLE `briefings` ADD COLUMN `responsible_role` VARCHAR(100) DEFAULT NULL COMMENT 'Cargo responsável';

ALTER TABLE `clients_projects` ADD COLUMN `project_address_number` VARCHAR(20) DEFAULT NULL COMMENT 'Número endereço obra';
ALTER TABLE `clients_projects` ADD COLUMN `project_complement` VARCHAR(100) DEFAULT NULL COMMENT 'Complemento';
ALTER TABLE `clients_projects` ADD COLUMN `project_neighborhood` VARCHAR(100) DEFAULT NULL COMMENT 'Bairro';
ALTER TABLE `clients_projects` ADD COLUMN `project_state` VARCHAR(2) DEFAULT NULL COMMENT 'UF obra';
