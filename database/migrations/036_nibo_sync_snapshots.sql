-- =====================================================
-- Histórico de sincronizações do Dashboard Financeiro (Nibo)
-- Guarda cada snapshot dos dados lidos (somente leitura) para:
--  - exibição instantânea ao entrar na área (último snapshot)
--  - preservar o estado anterior antes de cada nova sincronização
-- =====================================================

CREATE TABLE IF NOT EXISTS nibo_sync_snapshots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(150) DEFAULT NULL COMMENT 'Usuário que sincronizou',
    ok TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = sync sem erros',
    totals_json TEXT DEFAULT NULL COMMENT 'Resumo (saldo, contagens)',
    payload_json LONGTEXT DEFAULT NULL COMMENT 'Snapshot completo (masters, accounts, payables, receivables)',
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
