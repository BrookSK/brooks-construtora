-- Migration: Módulo Briefing & Geração de Objeto do Contrato com IA
-- Data: 2026-08-19

-- Clientes e Projetos (Dados do Cliente + Informações da Obra)
CREATE TABLE IF NOT EXISTS `clients_projects` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Dados do Cliente
    `client_name`       VARCHAR(255) NOT NULL                    COMMENT 'Nome completo ou razão social',
    `client_document`   VARCHAR(30)  DEFAULT NULL                COMMENT 'CPF ou CNPJ',
    `client_phone`      VARCHAR(30)  DEFAULT NULL,
    `client_email`      VARCHAR(255) DEFAULT NULL,
    -- Informações da Obra
    `project_type`      VARCHAR(100) DEFAULT NULL                COMMENT 'Tipo de obra (residencial, comercial, etc.)',
    `project_address`   TEXT         DEFAULT NULL                COMMENT 'Endereço completo da obra',
    `project_cep`       VARCHAR(10)  DEFAULT NULL,
    `project_city`      VARCHAR(100) DEFAULT NULL,
    `project_goal`      TEXT         DEFAULT NULL                COMMENT 'Objetivo / finalidade da obra',
    `project_area`      DECIMAL(10,2) DEFAULT NULL               COMMENT 'Área em m²',
    -- Controle
    `created_by`        INT UNSIGNED DEFAULT NULL                COMMENT 'ID do usuário admin',
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_client_name` (`client_name`),
    INDEX `idx_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Briefings (Briefing da Negociação + Condições Comerciais)
CREATE TABLE IF NOT EXISTS `briefings` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_project_id`     INT UNSIGNED NOT NULL               COMMENT 'FK para clients_projects',
    -- Briefing da Negociação
    `preferences`           TEXT DEFAULT NULL                   COMMENT 'Preferências do cliente',
    `priorities`            TEXT DEFAULT NULL                   COMMENT 'Prioridades do projeto',
    `needs`                 TEXT DEFAULT NULL                   COMMENT 'Necessidades específicas',
    `restrictions`          TEXT DEFAULT NULL                   COMMENT 'Restrições / limitações',
    `briefing_summary`      TEXT DEFAULT NULL                   COMMENT 'Resumo geral do briefing',
    `negotiation_details`   TEXT DEFAULT NULL                   COMMENT 'Detalhes da negociação',
    -- Condições Comerciais
    `contract_value`        DECIMAL(15,2) DEFAULT NULL          COMMENT 'Valor total do contrato R$',
    `payment_installments`  INT UNSIGNED  DEFAULT NULL          COMMENT 'Número de parcelas',
    `payment_details`       TEXT DEFAULT NULL                   COMMENT 'Detalhes do parcelamento',
    `start_date`            DATE DEFAULT NULL                   COMMENT 'Data prevista de início',
    `end_date`              DATE DEFAULT NULL                   COMMENT 'Data prevista de conclusão',
    `deadline_days`         INT UNSIGNED DEFAULT NULL           COMMENT 'Prazo em dias corridos',
    `clauses`               TEXT DEFAULT NULL                   COMMENT 'Cláusulas especiais',
    -- Controle
    `created_by`            INT UNSIGNED DEFAULT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_client_project` (`client_project_id`),
    FOREIGN KEY (`client_project_id`) REFERENCES `clients_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modelos de Prompt para geração do Objeto do Contrato
CREATE TABLE IF NOT EXISTS `contract_templates` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255) NOT NULL                       COMMENT 'Nome do modelo',
    `description`   TEXT DEFAULT NULL                           COMMENT 'Descrição do modelo',
    `prompt_template` LONGTEXT NOT NULL                         COMMENT 'Template com variáveis {{cliente_nome}}, {{endereco}}, etc.',
    `is_default`    TINYINT(1) NOT NULL DEFAULT 0               COMMENT 'Modelo padrão do sistema',
    `created_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Objetos do Contrato gerados pela IA
CREATE TABLE IF NOT EXISTS `contract_objects` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `briefing_id`           INT UNSIGNED NOT NULL               COMMENT 'FK para briefings',
    `contract_template_id`  INT UNSIGNED DEFAULT NULL           COMMENT 'Modelo usado na geração',
    `generated_text`        LONGTEXT NOT NULL                   COMMENT 'Texto do objeto gerado pela IA',
    `prompt_used`           LONGTEXT DEFAULT NULL               COMMENT 'Prompt completo enviado à IA (log)',
    `status`                ENUM('generated','approved','rejected') NOT NULL DEFAULT 'generated',
    `approved_by`           INT UNSIGNED DEFAULT NULL,
    `approved_at`           DATETIME DEFAULT NULL,
    `created_by`            INT UNSIGNED DEFAULT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_briefing`   (`briefing_id`),
    INDEX `idx_status`     (`status`),
    FOREIGN KEY (`briefing_id`) REFERENCES `briefings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modelo padrão inicial do sistema
INSERT INTO `contract_templates` (`name`, `description`, `prompt_template`, `is_default`, `created_at`) VALUES (
    'Objeto Padrão - Obra Residencial',
    'Modelo padrão para geração do objeto de contrato de obras residenciais.',
    'Você é um advogado especialista em direito da construção civil e contratos de obras no Brasil. Redija com linguagem jurídica clara, objetiva e profissional o OBJETO do contrato de prestação de serviços de engenharia/construção civil com base nas seguintes informações:\n\n**DADOS DO CLIENTE:**\n- Nome/Razão Social: {{cliente_nome}}\n- CPF/CNPJ: {{cliente_documento}}\n- Telefone: {{cliente_telefone}}\n- E-mail: {{cliente_email}}\n\n**INFORMAÇÕES DA OBRA:**\n- Tipo: {{tipo_obra}}\n- Endereço: {{endereco}}\n- Cidade: {{cidade}}\n- Objetivo: {{objetivo}}\n- Área: {{area_m2}} m²\n\n**BRIEFING DA NEGOCIAÇÃO:**\n{{briefing}}\n\n**CONDIÇÕES COMERCIAIS:**\n- Valor Total: R$ {{valor_contrato}}\n- Parcelamento: {{parcelamento}}\n- Prazo de Início: {{data_inicio}}\n- Prazo de Conclusão: {{data_conclusao}}\n- Prazo em dias: {{prazo_dias}} dias corridos\n\n**CLÁUSULAS ESPECIAIS:**\n{{clausulas}}\n\nRedija exclusivamente a cláusula do OBJETO do contrato, detalhando com precisão o escopo dos serviços a serem executados, os materiais envolvidos quando relevante, e as condições de execução. O texto deve ter entre 3 e 6 parágrafos, ser juridicamente sólido e adequado para ser inserido diretamente em um contrato formal. Não inclua outras cláusulas (pagamento, rescisão, etc.), apenas o OBJETO.',
    1,
    NOW()
);
