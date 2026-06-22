-- Migration: Permite aprovação por item (cada item pode ter fornecedor diferente)
-- Data: 2026-06-22

-- Adicionar coluna de fornecedor aprovado em cada item do pedido
ALTER TABLE purchase_order_items 
    ADD COLUMN approved_supplier_id INT UNSIGNED DEFAULT NULL COMMENT 'Fornecedor aprovado para este item específico' AFTER total_price;
