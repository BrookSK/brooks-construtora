USE brooks_obras;

-- PASSO 1: Remover os índices que usam categoria_id
ALTER TABLE painel_obras_custos_previstos 
DROP INDEX uq_obra_categoria,
DROP INDEX idx_categoria;

-- PASSO 2: Agora podemos dropar a coluna categoria_id
ALTER TABLE painel_obras_custos_previstos 
DROP COLUMN categoria_id;

-- PASSO 3: Adicionar as novas colunas
ALTER TABLE painel_obras_custos_previstos 
ADD COLUMN categoria_nibo_id VARCHAR(100) NOT NULL DEFAULT '' AFTER obra_id,
ADD COLUMN categoria_nome VARCHAR(255) NOT NULL DEFAULT '' AFTER categoria_nibo_id;

-- PASSO 4: Recriar os índices com as novas colunas
ALTER TABLE painel_obras_custos_previstos 
ADD INDEX idx_categoria (categoria_nibo_id),
ADD UNIQUE KEY uq_obra_categoria (obra_id, categoria_nibo_id);

-- PASSO 5: Verificar resultado final
DESCRIBE painel_obras_custos_previstos;

-- PASSO 6: Ver os novos índices
SHOW INDEX FROM painel_obras_custos_previstos;
