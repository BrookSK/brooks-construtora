-- Cronograma de parcelas contratuais
CREATE TABLE IF NOT EXISTS painel_obras_parcelas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id         INT UNSIGNED NOT NULL,
    numero_parcela  INT NOT NULL,
    valor_previsto  DECIMAL(15,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    descricao       VARCHAR(255) NULL,
    nibo_schedule_id VARCHAR(100) NULL COMMENT 'ID do lancamento no Nibo (se vinculado)',
    valor_recebido  DECIMAL(15,2) NULL COMMENT 'Valor efetivamente recebido (do Nibo)',
    data_recebimento DATE NULL COMMENT 'Data do recebimento (do Nibo)',
    status          ENUM('Pendente','Parcial','Pago') NOT NULL DEFAULT 'Pendente',
    created_by      INT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_nibo_schedule (nibo_schedule_id),
    UNIQUE KEY uq_obra_numero (obra_id, numero_parcela),
    
    CONSTRAINT fk_parcela_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cronograma de parcelas contratuais com vinculo ao Nibo';

-- Adiciona campo de orcamento_estimado na tabela de obras
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS orcamento_estimado DECIMAL(15,2) NULL COMMENT 'Orcamento total estimado da obra'
AFTER valor_contratado;

-- Adiciona campos de financeiro na tabela de obras
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS numero_parcelas INT NULL COMMENT 'Quantidade de parcelas do contrato'
AFTER orcamento_estimado;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS frequencia_parcelas ENUM('Semanal','Quinzenal','Mensal','Bimestral','Trimestral','Personalizado') NULL DEFAULT 'Mensal' COMMENT 'Frequencia de pagamento das parcelas'
AFTER numero_parcelas;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS carencia_meses INT NULL DEFAULT 0 COMMENT 'Meses de carencia antes da primeira parcela'
AFTER frequencia_parcelas;

ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS valor_entrada DECIMAL(15,2) NULL COMMENT 'Valor da entrada (se houver)'
AFTER carencia_meses;

-- Adiciona campo company para suportar multiplas empresas
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS company VARCHAR(30) NOT NULL DEFAULT 'brooks' COMMENT 'brooks ou vetriks'
AFTER id;

-- Adiciona campo de controle para conciliacao
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS fonte_controle ENUM('Planilha','Sistema','Nibo') NULL COMMENT 'Fonte usada para comparacao'
AFTER observacoes;

-- Tabela de categorias de servicos (custos previstos)
CREATE TABLE IF NOT EXISTS painel_obras_categorias_servico (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(50) NOT NULL UNIQUE COMMENT 'Codigo unico da categoria',
    nome             VARCHAR(100) NOT NULL COMMENT 'Nome da categoria',
    tipo_obra        ENUM('Construcao','Reforma','Ambos') NOT NULL DEFAULT 'Ambos' COMMENT 'Tipo de obra onde a categoria se aplica',
    ordem            INT NOT NULL DEFAULT 0 COMMENT 'Ordem de exibicao',
    ativo            TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tipo_obra (tipo_obra),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorias de servicos para previsao de custos por tipo de obra';

-- Tabela de custos previstos por categoria
CREATE TABLE IF NOT EXISTS painel_obras_custos_previstos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id          INT UNSIGNED NOT NULL,
    categoria_id     INT UNSIGNED NOT NULL,
    valor_previsto   DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor previsto para esta categoria',
    observacoes      TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_categoria (categoria_id),
    UNIQUE KEY uq_obra_categoria (obra_id, categoria_id),
    
    CONSTRAINT fk_custo_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE,
    CONSTRAINT fk_custo_categoria FOREIGN KEY (categoria_id) REFERENCES painel_obras_categorias_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Custos previstos por categoria de servico para cada obra';

-- Seed: Categorias padrao para Construcao
INSERT INTO painel_obras_categorias_servico (codigo, nome, tipo_obra, ordem) VALUES
('CONST_FUNDACAO', 'Fundacao e Estrutura', 'Construcao', 1),
('CONST_ALVENARIA', 'Alvenaria', 'Construcao', 2),
('CONST_ELETRICA', 'Instalacoes Eletricas', 'Construcao', 3),
('CONST_HIDRAULICA', 'Instalacoes Hidraulicas', 'Construcao', 4),
('CONST_COBERTURA', 'Cobertura e Telhado', 'Construcao', 5),
('CONST_ESQUADRIAS', 'Esquadrias (Portas e Janelas)', 'Construcao', 6),
('CONST_REVESTIMENTO', 'Revestimentos e Acabamentos', 'Construcao', 7),
('CONST_PINTURA', 'Pintura', 'Construcao', 8),
('CONST_PISOS', 'Pisos e Pavimentacao', 'Construcao', 9),
('CONST_MAO_OBRA', 'Mao de Obra Geral', 'Construcao', 10);

-- Seed: Categorias padrao para Reforma
INSERT INTO painel_obras_categorias_servico (codigo, nome, tipo_obra, ordem) VALUES
('REFORMA_DEMOLICAO', 'Demolicao e Remocao', 'Reforma', 1),
('REFORMA_ALVENARIA', 'Alvenaria e Reparos Estruturais', 'Reforma', 2),
('REFORMA_ELETRICA', 'Instalacoes Eletricas', 'Reforma', 3),
('REFORMA_HIDRAULICA', 'Instalacoes Hidraulicas', 'Reforma', 4),
('REFORMA_GESSO', 'Gesso e Drywall', 'Reforma', 5),
('REFORMA_REVESTIMENTO', 'Revestimentos', 'Reforma', 6),
('REFORMA_PINTURA', 'Pintura', 'Reforma', 7),
('REFORMA_PISOS', 'Pisos', 'Reforma', 8),
('REFORMA_ACABAMENTOS', 'Acabamentos Finais', 'Reforma', 9),
('REFORMA_MAO_OBRA', 'Mao de Obra', 'Reforma', 10);


-- Tabela de configuracoes de visualizacao do painel (por usuario)
CREATE TABLE IF NOT EXISTS painel_obras_config (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    centros_custo_visiveis JSON NULL COMMENT 'Array de IDs de centros de custo visiveis',
    obras_visiveis    JSON NULL COMMENT 'Array de IDs de obras visiveis',
    filtros_ativos    JSON NULL COMMENT 'Outros filtros personalizados',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uq_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuracoes de visualizacao do painel por usuario';

-- Tabela de lancamentos manuais por categoria
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
    anexos            JSON NULL COMMENT 'Array de paths de arquivos anexados',
    created_by        INT NOT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_obra (obra_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_data_lancamento (data_lancamento),
    INDEX idx_status (status),
    INDEX idx_nibo (nibo_account_id),
    
    CONSTRAINT fk_lancamento_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE,
    CONSTRAINT fk_lancamento_categoria FOREIGN KEY (categoria_id) REFERENCES painel_obras_categorias_servico(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lancamentos manuais e integracao com Nibo por categoria';

-- Tabela de vinculo entre categorias Nibo e categorias personalizadas
CREATE TABLE IF NOT EXISTS painel_obras_nibo_categoria_map (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nibo_category_id      VARCHAR(100) NOT NULL COMMENT 'ID da categoria no Nibo',
    nibo_category_name    VARCHAR(255) NOT NULL COMMENT 'Nome da categoria no Nibo',
    obra_categoria_id     INT UNSIGNED NOT NULL COMMENT 'ID da categoria personalizada',
    auto_sync             TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Sincronizar automaticamente',
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nibo_cat (nibo_category_id),
    INDEX idx_obra_cat (obra_categoria_id),
    UNIQUE KEY uq_nibo_obra_cat (nibo_category_id, obra_categoria_id),
    
    CONSTRAINT fk_map_obra_categoria FOREIGN KEY (obra_categoria_id) REFERENCES painel_obras_categorias_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mapeamento entre categorias Nibo e categorias de obra';
