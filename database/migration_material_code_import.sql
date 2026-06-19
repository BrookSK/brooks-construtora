-- =====================================================
-- Adicionar código do insumo na tabela de materiais
-- =====================================================

ALTER TABLE materials ADD COLUMN code VARCHAR(20) DEFAULT NULL COMMENT 'Código do insumo' AFTER id;
ALTER TABLE materials ADD INDEX idx_code (code);
