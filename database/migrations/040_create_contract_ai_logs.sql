-- Migration 040: Diagnóstico do módulo Elaboração de Contrato
-- Registra cada chamada à IA (extração e geração): requisição, resposta,
-- status, duração e erros — para acompanhamento em tela.
-- Data: 2026-08-29

CREATE TABLE IF NOT EXISTS `contract_ai_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contract_id`      INT UNSIGNED DEFAULT NULL             COMMENT 'FK generated_contracts (quando aplicável)',
    `operation`        VARCHAR(50)  NOT NULL                 COMMENT 'extract | generate | regenerate',
    `model`            VARCHAR(120) DEFAULT NULL             COMMENT 'Modelo GPT utilizado',
    `http_status`      INT          DEFAULT NULL             COMMENT 'HTTP retornado pela API',
    `duration_ms`      INT UNSIGNED DEFAULT NULL             COMMENT 'Duração da chamada em ms',
    `success`          TINYINT(1)   NOT NULL DEFAULT 0,
    `request_payload`  LONGTEXT     DEFAULT NULL             COMMENT 'Resumo/conteúdo enviado (truncado)',
    `response_body`    LONGTEXT     DEFAULT NULL             COMMENT 'Resposta bruta (truncada)',
    `error_message`    TEXT         DEFAULT NULL             COMMENT 'Mensagem de erro, se houver',
    `context`          VARCHAR(255) DEFAULT NULL             COMMENT 'Contexto (ex.: nome do PDF, projeto)',
    `created_by`       INT UNSIGNED DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_operation`   (`operation`),
    INDEX `idx_success`     (`success`),
    INDEX `idx_created_at`  (`created_at`),
    INDEX `idx_contract`    (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
