-- =====================================================
-- Suporte a múltiplas empresas no Dashboard Financeiro (Nibo)
-- Cada snapshot passa a pertencer a uma empresa (brooks/vetriks).
-- A visão "completo" consolida as duas em tempo de leitura.
-- =====================================================

ALTER TABLE nibo_sync_snapshots
    ADD COLUMN company VARCHAR(30) NOT NULL DEFAULT 'brooks' AFTER id;

CREATE INDEX idx_company_created ON nibo_sync_snapshots (company, created_at);
