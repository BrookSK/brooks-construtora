-- Migration 034: Empresa contratada + campos adicionais no briefing
-- Demanda #61 — 2026-08-19

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

ALTER TABLE `briefings`
    ADD COLUMN IF NOT EXISTS `contractor_company_id` INT UNSIGNED DEFAULT NULL COMMENT 'FK → contractor_companies' AFTER `client_project_id`,
    ADD COLUMN IF NOT EXISTS `discount_value`        DECIMAL(15,2) DEFAULT NULL COMMENT 'Desconto em R$' AFTER `contract_value`,
    ADD COLUMN IF NOT EXISTS `discount_percent`      DECIMAL(5,2)  DEFAULT NULL COMMENT 'Desconto em %' AFTER `discount_value`,
    ADD COLUMN IF NOT EXISTS `payment_method`        VARCHAR(255)  DEFAULT NULL COMMENT 'Forma de pagamento' AFTER `payment_details`,
    ADD COLUMN IF NOT EXISTS `project_number`        VARCHAR(50)   DEFAULT NULL COMMENT 'Nº orçamento/projeto' AFTER `payment_method`,
    ADD COLUMN IF NOT EXISTS `responsible_name`      VARCHAR(255)  DEFAULT NULL COMMENT 'Responsável' AFTER `project_number`,
    ADD COLUMN IF NOT EXISTS `responsible_role`      VARCHAR(100)  DEFAULT NULL COMMENT 'Cargo responsável' AFTER `responsible_name`;

ALTER TABLE `clients_projects`
    ADD COLUMN IF NOT EXISTS `project_address_number` VARCHAR(20)  DEFAULT NULL COMMENT 'Número endereço obra' AFTER `project_address`,
    ADD COLUMN IF NOT EXISTS `project_complement`     VARCHAR(100) DEFAULT NULL COMMENT 'Complemento' AFTER `project_address_number`,
    ADD COLUMN IF NOT EXISTS `project_neighborhood`   VARCHAR(100) DEFAULT NULL COMMENT 'Bairro' AFTER `project_complement`,
    ADD COLUMN IF NOT EXISTS `project_state`          VARCHAR(2)   DEFAULT NULL COMMENT 'UF obra' AFTER `project_city`;

-- =====================================================
-- Atualiza o template padrão para incluir cabeçalho da contratada
-- =====================================================
UPDATE `contract_templates`
SET `prompt_template` = CONCAT(
'{{contratada_razao_social}}\nCNPJ: {{contratada_cnpj}}\n{{contratada_endereco}}, {{contratada_numero}} {{contratada_complemento}}\n{{contratada_bairro}} - {{contratada_cidade}}/{{contratada_estado}} - CEP: {{contratada_cep}}\nTel: {{contratada_telefone}} | E-mail: {{contratada_email}}\nRepresentante Legal: {{contratada_representante}} - {{contratada_representante_cargo}}\n\n---\n\n',
`prompt_template`
),
`updated_at` = NOW()
WHERE `is_default` = 1
LIMIT 1;
