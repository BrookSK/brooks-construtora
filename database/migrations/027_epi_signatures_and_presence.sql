-- Migration: Assinaturas adicionais em EPI (colaborador) + módulo Lista de Presença
-- Data: 2026-07-07
-- Incremental: apenas adiciona colunas/tabelas novas. Não altera estruturas existentes.

-- ---------------------------------------------------------------
-- Assinaturas do colaborador (a assinatura do responsável já existe)
-- ---------------------------------------------------------------
ALTER TABLE epi_deliveries
    ADD COLUMN worker_signature_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Assinatura do colaborador que recebeu' AFTER signature_path;

ALTER TABLE epi_replacements
    ADD COLUMN worker_signature_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Assinatura do colaborador' AFTER new_delivery_photo_path,
    ADD COLUMN responsible_signature_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Assinatura do responsável' AFTER worker_signature_path;

ALTER TABLE epi_returns
    ADD COLUMN worker_signature_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Assinatura do colaborador' AFTER signature_path,
    ADD COLUMN responsible_signature_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Assinatura do responsável' AFTER worker_signature_path;

-- ---------------------------------------------------------------
-- Módulo Lista de Presença
-- ---------------------------------------------------------------

-- Cadastro de prestadores (estrutura base; será complementada conforme planilha)
CREATE TABLE IF NOT EXISTS presence_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome do prestador',
    document VARCHAR(50) DEFAULT NULL COMMENT 'CPF ou documento',
    company VARCHAR(255) DEFAULT NULL COMMENT 'Empresa',
    role VARCHAR(255) DEFAULT NULL COMMENT 'Função/cargo',
    phone VARCHAR(30) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_document (document),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros diários de presença
CREATE TABLE IF NOT EXISTS presence_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED DEFAULT NULL COMMENT 'Prestador (se cadastrado)',
    provider_name VARCHAR(255) NOT NULL COMMENT 'Snapshot do nome',
    company VARCHAR(255) DEFAULT NULL COMMENT 'Empresa',
    site VARCHAR(255) NOT NULL COMMENT 'Obra',
    presence_date DATE NOT NULL COMMENT 'Data da presença',
    presence_time TIME NOT NULL COMMENT 'Hora da presença',
    notes TEXT DEFAULT NULL,
    signature_path VARCHAR(500) DEFAULT NULL COMMENT 'Assinatura do prestador',
    status ENUM('registered','cancelled') NOT NULL DEFAULT 'registered',
    created_by_id INT UNSIGNED DEFAULT NULL,
    created_by_name VARCHAR(255) DEFAULT NULL COMMENT 'Usuário responsável pelo registro',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider_id),
    INDEX idx_site (site),
    INDEX idx_date (presence_date),
    FOREIGN KEY (provider_id) REFERENCES presence_providers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
