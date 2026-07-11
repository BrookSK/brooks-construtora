-- Migration: Cadastro de obras (sites) para a Lista de Presença
-- Data: 2026-07-08
-- Incremental: apenas nova tabela e vínculo opcional no registro de presença.

CREATE TABLE IF NOT EXISTS presence_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome da obra',
    address VARCHAR(500) DEFAULT NULL COMMENT 'Endereço/local',
    notes TEXT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vínculo opcional do registro de presença à obra cadastrada
ALTER TABLE presence_records
    ADD COLUMN site_id INT UNSIGNED DEFAULT NULL COMMENT 'Obra cadastrada (se selecionada)' AFTER provider_name,
    ADD INDEX idx_site_id (site_id);
