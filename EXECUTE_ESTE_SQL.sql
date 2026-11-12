-- =========================================================================
-- MIGRAÇÃO 055: Tabelas para Lançamentos, Categorias e Orçamentos
-- Execute este SQL no phpMyAdmin ou MySQL Workbench
-- =========================================================================

-- 1. TABELA DE CATEGORIAS DE SERVIÇOS
-- Esta tabela armazena as categorias personalizadas para orçamento
CREATE TABLE IF NOT EXISTS painel_obras_categorias_servico (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(50) NOT NULL UNIQUE COMMENT 'Codigo unico da categoria',
    nome             VARCHAR(100) NOT NULL COMMENT 'Nome da categoria',
    tipo_obra        ENUM('Construcao','Reforma','Ambos') NOT NULL DEFAULT 'Ambos',
    ordem            INT NOT NULL DEFAULT 0 COMMENT 'Ordem de exibicao',
    ativo            TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tipo_obra (tipo_obra),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE CUSTOS PREVISTOS (ORÇAMENTO POR CATEGORIA)
-- Armazena o orçamento previsto por categoria para cada obra
CREATE TABLE IF NOT EXISTS painel_obras_custos_previstos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id          INT UNSIGNED NOT NULL,
    categoria_id     INT UNSIGNED NOT NULL,
    valor_previsto   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    observacoes      TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_categoria (categoria_id),
    UNIQUE KEY uq_obra_categoria (obra_id, categoria_id),
    
    CONSTRAINT fk_custo_obra FOREIGN KEY (obra_id) 
        REFERENCES painel_obras(id) ON DELETE CASCADE,
    CONSTRAINT fk_custo_categoria FOREIGN KEY (categoria_id) 
        REFERENCES painel_obras_categorias_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE LANÇAMENTOS FINANCEIROS MANUAIS
-- Permite lançar receitas/despesas manualmente por categoria
CREATE TABLE IF NOT EXISTS painel_obras_lancamentos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id           INT UNSIGNED NOT NULL,
    categoria_id      INT UNSIGNED NOT NULL,
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
    INDEX idx_categoria (categoria_id),
    INDEX idx_data_lancamento (data_lancamento),
    INDEX idx_status (status),
    
    CONSTRAINT fk_lancamento_obra FOREIGN KEY (obra_id) 
        REFERENCES painel_obras(id) ON DELETE CASCADE,
    CONSTRAINT fk_lancamento_categoria FOREIGN KEY (categoria_id) 
        REFERENCES painel_obras_categorias_servico(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE PARCELAS CONTRATUAIS
-- Armazena o cronograma de recebimentos por obra
CREATE TABLE IF NOT EXISTS painel_obras_parcelas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id         INT UNSIGNED NOT NULL,
    numero_parcela  INT NOT NULL,
    valor_previsto  DECIMAL(15,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    descricao       VARCHAR(255) NULL,
    nibo_schedule_id VARCHAR(100) NULL COMMENT 'ID do lancamento no Nibo',
    valor_recebido  DECIMAL(15,2) NULL,
    data_recebimento DATE NULL,
    status          ENUM('Pendente','Parcial','Pago') NOT NULL DEFAULT 'Pendente',
    created_by      INT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_nibo_schedule (nibo_schedule_id),
    UNIQUE KEY uq_obra_numero (obra_id, numero_parcela),
    
    CONSTRAINT fk_parcela_obra FOREIGN KEY (obra_id) 
        REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE CONFIGURAÇÕES DO USUÁRIO
-- Salva filtros e preferências de visualização
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

-- 6. ADICIONA COLUNAS NA TABELA PAINEL_OBRAS (se não existirem)
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS orcamento_estimado DECIMAL(15,2) NULL 
AFTER valor_contratado;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS numero_parcelas INT NULL 
AFTER orcamento_estimado;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS frequencia_parcelas ENUM('Semanal','Quinzenal','Mensal','Bimestral','Trimestral','Personalizado') NULL DEFAULT 'Mensal' 
AFTER numero_parcelas;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS company VARCHAR(30) NOT NULL DEFAULT 'brooks' 
AFTER id;

-- =========================================================================
-- DADOS INICIAIS: Categorias padrão para Construção
-- =========================================================================
INSERT IGNORE INTO painel_obras_categorias_servico (codigo, nome, tipo_obra, ordem) VALUES
('CONST_FUNDACAO', 'Fundação e Estrutura', 'Construcao', 1),
('CONST_ALVENARIA', 'Alvenaria', 'Construcao', 2),
('CONST_ELETRICA', 'Instalações Elétricas', 'Construcao', 3),
('CONST_HIDRAULICA', 'Instalações Hidráulicas', 'Construcao', 4),
('CONST_COBERTURA', 'Cobertura e Telhado', 'Construcao', 5),
('CONST_ESQUADRIAS', 'Esquadrias (Portas e Janelas)', 'Construcao', 6),
('CONST_REVESTIMENTO', 'Revestimentos e Acabamentos', 'Construcao', 7),
('CONST_PINTURA', 'Pintura', 'Construcao', 8),
('CONST_PISOS', 'Pisos e Pavimentação', 'Construcao', 9),
('CONST_MAO_OBRA', 'Mão de Obra Geral', 'Construcao', 10);

-- =========================================================================
-- DADOS INICIAIS: Categorias padrão para Reforma
-- =========================================================================
INSERT IGNORE INTO painel_obras_categorias_servico (codigo, nome, tipo_obra, ordem) VALUES
('REFORMA_DEMOLICAO', 'Demolição e Remoção', 'Reforma', 1),
('REFORMA_ALVENARIA', 'Alvenaria e Reparos Estruturais', 'Reforma', 2),
('REFORMA_ELETRICA', 'Instalações Elétricas', 'Reforma', 3),
('REFORMA_HIDRAULICA', 'Instalações Hidráulicas', 'Reforma', 4),
('REFORMA_GESSO', 'Gesso e Drywall', 'Reforma', 5),
('REFORMA_REVESTIMENTO', 'Revestimentos', 'Reforma', 6),
('REFORMA_PINTURA', 'Pintura', 'Reforma', 7),
('REFORMA_PISOS', 'Pisos', 'Reforma', 8),
('REFORMA_ACABAMENTOS', 'Acabamentos Finais', 'Reforma', 9),
('REFORMA_MAO_OBRA', 'Mão de Obra', 'Reforma', 10);

-- =========================================================================
-- FIM DA MIGRAÇÃO
-- Após executar, acesse: /admin/painel-obras/categorias
-- =========================================================================
