-- =====================================================
-- Flag "Comprado" para pedidos aprovados
-- Indica que a compra foi efetivamente realizada
-- =====================================================

ALTER TABLE purchase_orders 
    ADD COLUMN purchased_at DATETIME DEFAULT NULL COMMENT 'Data/hora que foi marcado como comprado',
    ADD COLUMN purchased_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem marcou como comprado';

ALTER TABLE purchase_orders ADD INDEX idx_purchased (purchased_at);

-- Atualizar ENUM da tabela de pagamentos para incluir 'pedido'
ALTER TABLE purchase_order_payments MODIFY COLUMN type ENUM('nf','boleto','pedido') NOT NULL;
