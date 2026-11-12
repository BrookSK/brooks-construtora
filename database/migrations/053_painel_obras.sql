-- =============================================================
-- Painel Financeiro de Obras — tabelas novas (nunca altera existentes)
-- =============================================================

-- A) Obras (entidade central do painel)
CREATE TABLE IF NOT EXISTS painel_obras (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL UNIQUE              COMMENT 'Ex.: REF-07, CON-03, P002',
    nome            VARCHAR(255) NOT NULL,
    tipo            ENUM('Reforma','Construção') NOT NULL,
    cliente         VARCHAR(255) NULL,
    centros_custo   JSON         NULL COMMENT 'Array de IDs de centros de custo do Nibo',
    codigos_alt     JSON         NULL COMMENT 'Códigos alternativos na descrição (ex.: ADM-105)',
    valor_contratado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    data_contrato   DATE         NOT NULL,
    data_inicio     DATE         NULL,
    data_prev_fim   DATE         NULL,
    situacao        ENUM('Ativa','Concluída','Pausada','Cancelada') NOT NULL DEFAULT 'Ativa',
    responsavel     VARCHAR(255) NULL,
    observacoes     TEXT         NULL,
    created_by      INT          NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by      INT          NULL,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_situacao (situacao),
    INDEX idx_codigo   (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cadastro de obras do Painel Financeiro';

-- B) Aditivos de contrato
CREATE TABLE IF NOT EXISTS painel_obras_aditivos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id     INT UNSIGNED NOT NULL,
    data        DATE         NOT NULL,
    valor       DECIMAL(15,2) NOT NULL COMMENT 'Pode ser negativo (desconto)',
    descricao   TEXT         NOT NULL,
    created_by  INT          NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_obra (obra_id),
    CONSTRAINT fk_adit_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Aditivos de contrato por obra';

-- C) Orçamento por obra e categoria (versionado)
CREATE TABLE IF NOT EXISTS painel_obras_orcamento (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id         INT UNSIGNED NOT NULL,
    categoria_obra  ENUM(
        'Material Básico','Mão de Obra','Equipamento/Locação',
        'Material de Acabamento','Instalações',
        'Administração/Indiretos','Projetos e Taxas','Imprevistos'
    ) NOT NULL,
    valor_orcado    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    versao          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    data_vigencia   DATE         NOT NULL,
    motivo_revisao  TEXT         NULL COMMENT 'Obrigatório a partir da versão 2',
    created_by      INT          NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_obra_versao (obra_id, versao),
    CONSTRAINT fk_orc_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Orçamento versionado por obra e categoria';

-- D) Total da planilha de controle mensal
CREATE TABLE IF NOT EXISTS painel_obras_planilha (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id         INT UNSIGNED NOT NULL,
    competencia     VARCHAR(7)   NOT NULL COMMENT 'YYYY-MM',
    total_planilha  DECIMAL(15,2) NOT NULL,
    informado_por   VARCHAR(150) NOT NULL,
    data_informacao DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacao      TEXT         NULL,
    UNIQUE KEY uq_obra_comp (obra_id, competencia),
    INDEX idx_obra (obra_id),
    CONSTRAINT fk_plan_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Total mensal da planilha de controle por obra';

-- E) De-para: categoria Nibo → categoria de obra
CREATE TABLE IF NOT EXISTS painel_categorias_depara (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_nibo_id   VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID da categoria no Nibo',
    categoria_nibo_nome VARCHAR(255) NOT NULL,
    categoria_obra      ENUM(
        'Material Básico','Mão de Obra','Equipamento/Locação',
        'Material de Acabamento','Instalações',
        'Administração/Indiretos','Projetos e Taxas','Imprevistos'
    ) NULL COMMENT 'NULL = ainda não mapeado',
    ignorar_no_painel   TINYINT(1) NOT NULL DEFAULT 0,
    created_by          INT         NULL,
    created_at          DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME    NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cat_nibo (categoria_nibo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='De-para entre categorias do Nibo e categorias de obra';

-- F) Pauta de revisão
CREATE TABLE IF NOT EXISTS painel_pauta_revisao (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id      INT UNSIGNED NOT NULL,
    data_reuniao DATE         NOT NULL,
    motivo       TEXT         NULL,
    adicionado_por VARCHAR(150) NOT NULL,
    adicionado_em DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status       ENUM('Pendente','Revisada') NOT NULL DEFAULT 'Pendente',
    INDEX idx_obra    (obra_id),
    INDEX idx_status  (status),
    CONSTRAINT fk_pauta_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pauta de reunião de revisão de obras';

-- G) Cache de categorias do Nibo (TTL controlado pelo serviço)
CREATE TABLE IF NOT EXISTS painel_categorias_nibo_cache (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company         VARCHAR(30)  NOT NULL DEFAULT 'brooks',
    categoria_id    VARCHAR(100) NOT NULL,
    nome            VARCHAR(255) NOT NULL,
    tipo            VARCHAR(10)  NULL COMMENT 'In / Out / InOut',
    grupo_id        VARCHAR(100) NULL,
    grupo_nome      VARCHAR(255) NULL,
    subgrupo_id     VARCHAR(100) NULL,
    subgrupo_nome   VARCHAR(255) NULL,
    reference_code  VARCHAR(100) NULL,
    excluida        TINYINT(1)  NOT NULL DEFAULT 0,
    caminho         VARCHAR(500) NULL COMMENT 'Grupo > Subgrupo > Categoria',
    synced_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_cat (company, categoria_id),
    INDEX idx_company (company)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache local das categorias do Nibo';

-- H) Meta anual (opcional)
CREATE TABLE IF NOT EXISTS painel_metas_anuais (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ano                      SMALLINT UNSIGNED NOT NULL UNIQUE,
    valor_contratado_ref     DECIMAL(15,2) NOT NULL,
    created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Meta anual de referência para KPI de variação';
