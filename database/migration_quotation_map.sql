-- =====================================================
-- MAPA DE COTAÇÕES - Reestruturação do fluxo
-- Fornecedores saem da criação do pedido e vão para a cotação
-- Adição de campos financeiros (desconto, acréscimo, IPI, ICMS, frete)
-- =====================================================

-- Adicionar campos financeiros na tabela de fornecedores do pedido
ALTER TABLE purchase_order_suppliers 
    ADD COLUMN discount_type ENUM('percent','fixed') DEFAULT 'percent' AFTER quote_notes,
    ADD COLUMN discount_value DECIMAL(12,2) DEFAULT 0 AFTER discount_type,
    ADD COLUMN surcharge_type ENUM('percent','fixed') DEFAULT 'percent' AFTER discount_value,
    ADD COLUMN surcharge_value DECIMAL(12,2) DEFAULT 0 AFTER surcharge_type,
    ADD COLUMN ipi_percent DECIMAL(5,2) DEFAULT 0 AFTER surcharge_value,
    ADD COLUMN icms_percent DECIMAL(5,2) DEFAULT 0 AFTER ipi_percent,
    ADD COLUMN freight DECIMAL(12,2) DEFAULT 0 AFTER icms_percent,
    ADD COLUMN subtotal_items DECIMAL(12,2) DEFAULT 0 COMMENT 'Soma dos itens antes de ajustes' AFTER freight,
    ADD COLUMN subtotal_final DECIMAL(12,2) DEFAULT 0 COMMENT 'Total final com todos os ajustes' AFTER subtotal_items;

-- Adicionar campo de melhor preço e último preço na tabela de histórico
ALTER TABLE material_price_history 
    ADD COLUMN total_price DECIMAL(12,2) DEFAULT NULL AFTER unit_price;

-- Garantir que a tabela purchase_order_suppliers pode ser criada mesmo sem FK
-- (fornecedores são adicionados na cotação, não na criação do pedido)
