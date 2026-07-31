-- Adicionar valor unitário no cadastro de estoque
ALTER TABLE stock_items ADD COLUMN unit_price DECIMAL(10,2) NULL AFTER min_quantity;
