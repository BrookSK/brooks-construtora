-- Adicionar campos para marcar item como "já comprado" na cotação
ALTER TABLE purchase_order_items ADD COLUMN already_purchased TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_movement_id;
ALTER TABLE purchase_order_items ADD COLUMN already_purchased_price DECIMAL(10,2) NULL AFTER already_purchased;
