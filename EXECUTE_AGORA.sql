-- =========================================================================
-- PAINEL DE OBRAS - ESTRUTURA FINAL SIMPLIFICADA
-- Execute este SQL no phpMyAdmin
-- =========================================================================

-- 1. Tabela de custos previstos por obra (usa categorias do Nibo dinamicamente)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Custos previstos por categoria - valores comparados com lançamentos do Nibo';

-- 2. Tabela de lançamentos manuais (complemento aos lançamentos do Nibo)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Lançamentos manuais complementares aos do Nibo';

-- 3. Tabela de configurações do usuário (filtros do dashboard)
CREATE TABLE IF NOT EXISTS painel_obras_config (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    centros_custo_visiveis JSON NULL COMMENT 'IDs dos CCs visíveis no dashboard',
    obras_visiveis    JSON NULL COMMENT 'IDs das obras visíveis no dashboard',
    filtros_ativos    JSON NULL COMMENT 'Outros filtros ativos',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações de filtros e visualização por usuário';

-- =========================================================================
-- COMO FUNCIONA:
-- =========================================================================
--
-- 1. CATEGORIAS: Vêm do Nibo dinamicamente (snapshot), não são hardcoded
--
-- 2. CUSTOS PREVISTOS: Cadastrados na aba "Custos Previstos" de cada obra
--    - Seleciona categoria do Nibo
--    - Define valor previsto
--    - Sistema compara com lançamentos reais do Nibo
--
-- 3. LANÇAMENTOS DO NIBO: Populam automaticamente receitas/despesas
--    - Vinculados à obra pelo centro de custo
--    - Separados por tipo (payable = despesa, receivable = receita)
--    - Agrupados por categoria
--
-- 4. DASHBOARD: Mostra previsto vs realizado
--    - Orçado: soma dos custos previstos por categoria
--    - Gasto: soma dos lançamentos (payable) pagos do Nibo
--    - Receitas: soma dos lançamentos (receivable) recebidos do Nibo
--    - Desvio: diferença entre orçado e gasto
--
-- =========================================================================
-- FONTE ÚNICA DE DADOS: Painel de Obras → Editar Obra → Custos Previstos
-- =========================================================================
