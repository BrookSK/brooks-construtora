-- Migration 039: Módulo "Elaboração de Contrato"
-- Converte a Proposta Comercial (PDF) em Contrato de Empreitada preenchido.
-- Data: 2026-08-29

-- Modelos-base de contrato (Execução / Administração / Gerenciamento).
-- O texto do modelo (Anexo A) e o prompt de sistema (Parte 2) são semeados
-- pelo ContractController a partir de App\Services\ContractModelLibrary,
-- evitando problemas de escape com os caracteres de caixa (┌ │ └).
CREATE TABLE IF NOT EXISTS `contract_base_templates` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`           VARCHAR(255) NOT NULL                     COMMENT 'Nome do modelo-base',
    `contract_type`  VARCHAR(50)  NOT NULL DEFAULT 'execucao'  COMMENT 'execucao | administracao | gerenciamento',
    `model_text`     LONGTEXT     NOT NULL                     COMMENT 'MODELO_CONTRATO (Anexo A) com placeholders {{...}}',
    `system_prompt`  LONGTEXT     NOT NULL                     COMMENT 'Prompt de sistema (Parte 2)',
    `is_default`     TINYINT(1)   NOT NULL DEFAULT 0,
    `active`         TINYINT(1)   NOT NULL DEFAULT 1,
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_contract_type` (`contract_type`),
    INDEX `idx_is_default`    (`is_default`),
    INDEX `idx_active`        (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contratos gerados, com versionamento por projeto (P XXXX).
CREATE TABLE IF NOT EXISTS `generated_contracts` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_code`       VARCHAR(50)  DEFAULT NULL             COMMENT 'Código do projeto, ex.: P 0019',
    `project_name`       VARCHAR(255) DEFAULT NULL             COMMENT 'Nome do projeto, ex.: JUAN E YASMINI',
    `base_template_id`   INT UNSIGNED DEFAULT NULL             COMMENT 'FK contract_base_templates',
    `version`            INT UNSIGNED NOT NULL DEFAULT 1       COMMENT 'v1, v2...',
    `proposal_revision`  VARCHAR(50)  DEFAULT NULL             COMMENT 'Revisão do orçamento que originou o contrato',
    `source_pdf`         VARCHAR(255) DEFAULT NULL             COMMENT 'Nome do PDF do orçamento',
    `extraction_json`    LONGTEXT     DEFAULT NULL             COMMENT 'JSON extraído da proposta (auditoria)',
    `complementary_json` LONGTEXT     DEFAULT NULL             COMMENT 'JSON de dados complementares (contratante/obra/condomínio)',
    `contract_markdown`  LONGTEXT     DEFAULT NULL             COMMENT 'Contrato gerado em Markdown',
    `report_json`        LONGTEXT     DEFAULT NULL             COMMENT 'RELATÓRIO da IA (pendências/divergências)',
    `validation_json`    LONGTEXT     DEFAULT NULL             COMMENT 'Resultado do checklist de validação',
    `status`             ENUM('draft','generated','exported') NOT NULL DEFAULT 'draft',
    `created_by`         INT UNSIGNED DEFAULT NULL,
    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_project_code` (`project_code`),
    INDEX `idx_status`       (`status`),
    INDEX `idx_base_template`(`base_template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
