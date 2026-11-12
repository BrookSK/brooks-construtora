-- Cronograma de parcelas contratuais
CREATE TABLE IF NOT EXISTS painel_obras_parcelas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    obra_id         INT UNSIGNED NOT NULL,
    numero_parcela  INT NOT NULL,
    valor_previsto  DECIMAL(15,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    descricao       VARCHAR(255) NULL,
    nibo_schedule_id VARCHAR(100) NULL COMMENT 'ID do lançamento no Nibo (se vinculado)',
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
  COMMENT='Cronograma de parcelas contratuais com vínculo ao Nibo';

-- Adiciona campo de orcamento_estimado na tabela de obras
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS orcamento_estimado DECIMAL(15,2) NULL COMMENT 'Orçamento total estimado da obra'
AFTER valor_contratado;

-- Adiciona campo de numero_parcelas na tabela de obras
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS numero_parcelas INT NULL COMMENT 'Quantidade de parcelas do contrato'
AFTER orcamento_estimado;

-- Adiciona campo company para suportar múltiplas empresas
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS company VARCHAR(30) NOT NULL DEFAULT 'brooks' COMMENT 'brooks ou vetriks'
AFTER id;

-- Adiciona campo de controle para conciliação
ALTER TABLE painel_obras 
ADD COLUMN IF NOT EXISTS fonte_controle ENUM('Planilha','Sistema','Nibo') NULL COMMENT 'Fonte usada para comparação'
AFTER observacoes;
