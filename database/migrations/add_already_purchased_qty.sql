-- Adicionar campo de quantidade ao "já comprado"
ALTER TABLE purchase_order_items ADD COLUMN already_purchased_qty DECIMAL(10,2) NULL AFTER already_purchased;
