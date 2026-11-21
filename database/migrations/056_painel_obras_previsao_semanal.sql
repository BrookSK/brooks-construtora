-- Previsão semanal acumulativa por obra e categoria (derivada de painel_obras_custos_previstos)
-- Não altera custos previstos nem dados do Nibo. Pode ser recalculada a qualquer momento.

CREATE TABLE IF NOT EXISTS painel_obras_previsao_semanal (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id            INT UNSIGNED NOT NULL,
    categoria_nibo_id  VARCHAR(100) NOT NULL,
    categoria_nome     VARCHAR(255) NOT NULL,
    semana_num         INT UNSIGNED NOT NULL COMMENT 'Semana 1..N a partir de data_inicio',
    semana_inicio      DATE NOT NULL,
    semana_fim         DATE NOT NULL,
    previsto_semana    DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Distribuição proporcional do valor previsto',
    saldo_anterior     DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Saldo acumulado ao final da semana anterior',
    disponivel         DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'saldo_anterior + previsto_semana',
    gasto_semana       DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Gasto efetivo (Nibo) na semana',
    saldo_acumulado    DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'disponivel - gasto_semana',
    calculado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_obra_cat_semana (obra_id, categoria_nibo_id, semana_num),
    INDEX idx_obra (obra_id),
    CONSTRAINT fk_prevsem_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico semanal de previsão x realizado acumulado por categoria';

-- Alertas de estouro acumulado (um registro por obra + categoria; reabre se o desvio voltar)
CREATE TABLE IF NOT EXISTS painel_obras_alertas_orcamento (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id            INT UNSIGNED NOT NULL,
    categoria_nibo_id  VARCHAR(100) NOT NULL,
    categoria_nome     VARCHAR(255) NOT NULL,
    status             ENUM('ativo','resolvido') NOT NULL DEFAULT 'ativo',
    semana_num         INT UNSIGNED NOT NULL,
    semana_inicio      DATE NOT NULL,
    semana_fim         DATE NOT NULL,
    previsto_acumulado DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Orçamento disponível acumulado até a semana',
    gasto_acumulado    DECIMAL(15,2) NOT NULL DEFAULT 0,
    desvio             DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Valor negativo do saldo acumulado',
    detectado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolvido_em       DATETIME NULL,

    UNIQUE KEY uq_obra_cat (obra_id, categoria_nibo_id),
    INDEX idx_status (status),
    CONSTRAINT fk_alertaorc_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alertas de estouro orçamentário acumulado por categoria';
