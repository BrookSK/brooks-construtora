-- Link de compra opcional por material x fornecedor na cotacao.
-- Aditivo e nullable: nao afeta cotacoes existentes.
ALTER TABLE purchase_order_item_prices ADD COLUMN link VARCHAR(500) DEFAULT NULL AFTER total_price;
