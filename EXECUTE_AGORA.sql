-- =========================================================================
-- EXECUTE ESTE SQL AGORA NO PHPMYADMIN
-- Cria apenas as tabelas, SEM inserir categorias inventadas
-- =========================================================================

-- 1. Cria tabela de custos previstos (vinculada às categorias do Nibo)
CREATE TABLE IF NOT EXISTS painel_obras_custos_previstos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id          INT UNSIGNED NOT NULL,
    categoria_nibo_id VARCHAR(100) NOT NULL COMMENT 'ID da categoria no Nibo',
    categoria_nome   VARCHAR(255) NOT NULL COMMENT 'Nome da categoria do Nibo',
    valor_previsto   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    observacoes      TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_categoria (categoria_nibo_id),
    UNIQUE KEY uq_obra_categoria (obra_id, categoria_nibo_id),
    
    CONSTRAINT fk_custo_obra FOREIGN KEY (obra_id) 
        REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Cria tabela de lançamentos manuais (também usa categorias do Nibo)
CREATE TABLE IF NOT EXISTS painel_obras_lancamentos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id           INT UNSIGNED NOT NULL,
    categoria_nibo_id VARCHAR(100) NOT NULL COMMENT 'ID da categoria no Nibo',
    categoria_nome    VARCHAR(255) NOT NULL COMMENT 'Nome da categoria do Nibo',
    tipo              ENUM('Receita','Despesa') NOT NULL DEFAULT 'Despesa',
    descricao         VARCHAR(255) NOT NULL,
    valor             DECIMAL(15,2) NOT NULL,
    data_lancamento   DATE NOT NULL,
    data_vencimento   DATE NULL,
    status            ENUM('Previsto','Realizado','Cancelado') NOT NULL DEFAULT 'Previsto',
    nibo_account_id   VARCHAR(100) NULL COMMENT 'ID da conta no Nibo (se sincronizado)',
    observacoes       TEXT NULL,
    anexos            JSON NULL,
    created_by        INT NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_categoria (categoria_nibo_id),
    INDEX idx_data_lancamento (data_lancamento),
    INDEX idx_status (status),
    
    CONSTRAINT fk_lancamento_obra FOREIGN KEY (obra_id) 
        REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de configurações do usuário
CREATE TABLE IF NOT EXISTS painel_obras_config (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    centros_custo_visiveis JSON NULL,
    obras_visiveis    JSON NULL,
    filtros_ativos    JSON NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- PRONTO! Agora as categorias virão do Nibo dinamicamente
-- =========================================================================
