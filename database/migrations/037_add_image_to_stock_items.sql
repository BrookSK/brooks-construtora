-- Adiciona coluna de imagem/foto opcional ao item de estoque.
-- Independente da imagem do material: o Estoque controla a propria imagem.
-- Aditivo e nullable: nao afeta cadastros existentes de estoque.
ALTER TABLE stock_items ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER notes;
