-- Tabela para vincular centros de custo do Nibo às obras
CREATE TABLE IF NOT EXISTS painel_obras_centros_custo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id INT UNSIGNED NOT NULL,
    cc_nibo_id VARCHAR(255) NULL,
    cc_nibo_nome VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    
    FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE,
    
    INDEX idx_obra (obra_id),
    INDEX idx_cc_nibo_id (cc_nibo_id),
    INDEX idx_cc_nibo_nome (cc_nibo_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Vinculação entre obras e centros de custo do Nibo';

-- Remove a coluna centros_custo JSON antiga se existir
-- ALTER TABLE painel_obras DROP COLUMN IF EXISTS centros_custo;
