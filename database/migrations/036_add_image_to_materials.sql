-- Adiciona coluna de imagem/foto opcional ao material/produto.
-- Aditivo e nullable: nao afeta cadastros existentes, importacao em massa
-- (INSERT com colunas fixas) nem a criacao de pedidos.
-- A imagem fica vinculada ao material; o Estoque (stock_items) referencia
-- materials via material_id, entao a imagem aparece automaticamente no estoque.
ALTER TABLE materials ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER classification;
