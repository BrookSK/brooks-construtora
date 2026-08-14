-- Adicionar marcação "Saiu do Estoque" nos pedidos
ALTER TABLE purchase_orders 
    ADD COLUMN stock_dispatched_at DATETIME DEFAULT NULL COMMENT 'Data/hora marcado como saiu do estoque',
    ADD COLUMN stock_dispatched_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem marcou como saiu do estoque';
