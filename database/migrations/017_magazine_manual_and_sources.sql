-- Migration: Suporte a criação manual de revistas, fontes e prompt complementar
-- Data: 2026-06-23

-- Campo de prompt complementar nos temas
ALTER TABLE magazine_topics
    ADD COLUMN source_urls TEXT DEFAULT NULL COMMENT 'URLs de fontes para a IA usar (um por linha)' AFTER description,
    ADD COLUMN custom_prompt TEXT DEFAULT NULL COMMENT 'Prompt complementar para geração de temas' AFTER source_urls,
    ADD COLUMN created_by ENUM('ai','manual') DEFAULT 'ai' AFTER custom_prompt;

-- Tabela de fontes da revista (página de referências)
CREATE TABLE IF NOT EXISTS magazine_sources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    magazine_id INT UNSIGNED NOT NULL,
    title VARCHAR(500) NOT NULL COMMENT 'Título da fonte',
    url VARCHAR(1000) DEFAULT NULL COMMENT 'URL da fonte',
    author VARCHAR(255) DEFAULT NULL COMMENT 'Autor/veículo',
    accessed_at DATE DEFAULT NULL COMMENT 'Data de acesso',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_magazine (magazine_id),
    FOREIGN KEY (magazine_id) REFERENCES magazines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campo para prompt complementar global (configuração)
-- Será armazenado na tabela settings com chave 'magazine_custom_prompt'
