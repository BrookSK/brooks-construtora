-- Ajustes: previsto x pago separados, alertas projetados e snapshots congelados.
-- Pré-requisito: 056_painel_obras_previsao_semanal.sql já executada.

-- 1) Visão ATUAL da previsão semanal (tabela derivada; é recalculada a cada sync).
--    Recriada com colunas separadas: orçado (distribuição do custo previsto),
--    programado (previsto no Nibo, por forecast_date) e pago (por paymentDate).
--    O histórico imutável fica em painel_obras_snapshots.
DROP TABLE IF EXISTS painel_obras_previsao_semanal;
CREATE TABLE painel_obras_previsao_semanal (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id            INT UNSIGNED NOT NULL,
    categoria_nibo_id  VARCHAR(100) NOT NULL,
    categoria_nome     VARCHAR(255) NOT NULL,
    semana_num         INT UNSIGNED NOT NULL,
    semana_inicio      DATE NOT NULL,
    semana_fim         DATE NOT NULL,
    orcado_semana      DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Custo previsto da categoria distribuído na semana',
    saldo_anterior     DECIMAL(15,2) NOT NULL DEFAULT 0,
    disponivel         DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'saldo_anterior + orcado_semana',
    programado_semana  DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Despesas programadas no Nibo (forecast_date ou vencimento), qualquer status',
    programado_aberto  DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Parte do programado ainda não paga',
    pago_semana        DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Despesas efetivamente pagas (payment_date)',
    saldo_acumulado    DECIMAL(15,2) NULL COMMENT 'disponivel - pago_semana (NULL em semanas futuras)',
    saldo_projetado    DECIMAL(15,2) NULL COMMENT 'Saldo considerando compromissos em aberto',
    is_atual           TINYINT(1) NOT NULL DEFAULT 0,
    calculado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_obra_cat_semana (obra_id, categoria_nibo_id, semana_num),
    INDEX idx_obra (obra_id),
    INDEX idx_atual (is_atual),
    CONSTRAINT fk_prevsem_obra FOREIGN KEY (obra_id) REFERENCES painel_obras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Visão atual: orçado x programado x pago por categoria e semana';

-- 2) Alertas: realizado (saldo pago negativo) ou projetado (compromissos em aberto estouram o saldo)
ALTER TABLE painel_obras_alertas_orcamento
    ADD COLUMN IF NOT EXISTS tipo ENUM('realizado','projetado') NOT NULL DEFAULT 'realizado' AFTER status,
    ADD COLUMN IF NOT EXISTS comprometido DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Previsto ainda não pago incluído no cálculo' AFTER gasto_acumulado;

-- 3) Snapshots congelados (INSERT-ONLY): um por sincronização do Nibo.
--    Os lançamentos ficam no próprio nibo_sync_snapshots (imutável); aqui ficam a configuração das
--    obras, custos previstos, mapas de categoria/de-para, recebimentos e os resultados semanais.
CREATE TABLE IF NOT EXISTS painel_obras_snapshots (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nibo_snapshot_id INT UNSIGNED NOT NULL COMMENT 'nibo_sync_snapshots.id (lançamentos da época)',
    company          VARCHAR(30) NOT NULL,
    snapshot_em      DATETIME NOT NULL COMMENT 'Data/hora da sincronização',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    compressao       VARCHAR(10) NOT NULL DEFAULT 'none',
    payload          LONGBLOB NOT NULL COMMENT 'JSON (gzip) com obras, custos, de-para, categorias, recebimentos, semanal e alertas',
    resumo_json      TEXT NULL,

    UNIQUE KEY uq_nibo_snapshot (nibo_snapshot_id),
    INDEX idx_company_em (company, snapshot_em),
    -- RESTRICT: o snapshot do Nibo (lançamentos da época) não pode ser apagado enquanto houver foto congelada dele
    CONSTRAINT fk_painelsnap_nibo FOREIGN KEY (nibo_snapshot_id) REFERENCES nibo_sync_snapshots(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fotografias imutáveis do Painel de Obras por sincronização';

-- 4) (Opcional, recomendado) Bloqueio em nível de banco contra UPDATE/DELETE em snapshots.
--    Execute no phpMyAdmin/cliente com DELIMITER, se o usuário do banco tiver permissão TRIGGER:
--
-- DELIMITER $$
-- CREATE TRIGGER trg_painel_snapshots_no_update BEFORE UPDATE ON painel_obras_snapshots
-- FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Snapshots são imutáveis'; END$$
-- CREATE TRIGGER trg_painel_snapshots_no_delete BEFORE DELETE ON painel_obras_snapshots
-- FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Snapshots são imutáveis'; END$$
-- CREATE TRIGGER trg_nibo_snapshots_no_update BEFORE UPDATE ON nibo_sync_snapshots
-- FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Snapshots são imutáveis'; END$$
-- DELIMITER ;
