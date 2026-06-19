-- =====================================================
-- Vendedor, Prazo de Entrega e campos auxiliares
-- =====================================================

-- Adicionar campos de vendedor e prazo de entrega no fornecedor do pedido
ALTER TABLE purchase_order_suppliers 
    ADD COLUMN vendor_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome do vendedor da loja' AFTER freight,
    ADD COLUMN vendor_phone VARCHAR(20) DEFAULT NULL AFTER vendor_name,
    ADD COLUMN vendor_email VARCHAR(255) DEFAULT NULL AFTER vendor_phone,
    ADD COLUMN delivery_days INT DEFAULT NULL COMMENT 'Prazo de entrega em dias' AFTER vendor_email,
    ADD COLUMN delivery_notes VARCHAR(255) DEFAULT NULL COMMENT 'Observações do prazo' AFTER delivery_days;

-- Adicionar campos no histórico de preços
ALTER TABLE material_price_history 
    ADD COLUMN vendor_name VARCHAR(255) DEFAULT NULL AFTER was_approved,
    ADD COLUMN delivery_days INT DEFAULT NULL AFTER vendor_name;
