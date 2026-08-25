-- Link de compra opcional por item do pedido (referencia da compra online).
-- Aditivo e nullable: nao afeta itens existentes.
ALTER TABLE purchase_order_items ADD COLUMN link VARCHAR(500) DEFAULT NULL AFTER classification;
