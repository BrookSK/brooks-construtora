-- ============================================
-- DIAGNÓSTICO COMPLETO E CORREÇÃO
-- ============================================

-- 1. Ver a estrutura REAL da tabela
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT,
    EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'brooks_obras' 
  AND TABLE_NAME = 'painel_obras_custos_previstos'
ORDER BY ORDINAL_POSITION;

-- ============================================
-- EXECUTE O SELECT ACIMA PRIMEIRO
-- ME ENVIE O RESULTADO COMPLETO
-- ============================================

-- 2. Ver o CREATE TABLE completo
SHOW CREATE TABLE painel_obras_custos_previstos;

-- ============================================
-- EXECUTE O SHOW CREATE TABLE ACIMA
-- ME ENVIE O RESULTADO COMPLETO
-- ============================================

-- DEPOIS QUE EU VER OS RESULTADOS, VOU CRIAR O SQL CORRETO
-- NÃO EXECUTE OS COMANDOS ABAIXO AINDA
-- ============================================

-- 3. OPÇÃO A: Se a coluna categoria_id existir
ALTER TABLE painel_obras_custos_previstos 
DROP COLUMN categoria_id,
ADD COLUMN categoria_nibo_id VARCHAR(100) NOT NULL DEFAULT '' AFTER obra_id,
ADD COLUMN categoria_nome VARCHAR(255) NOT NULL DEFAULT '' AFTER categoria_nibo_id;

-- 4. OPÇÃO B: Se a coluna categoria_id NÃO existir
ALTER TABLE painel_obras_custos_previstos 
ADD COLUMN categoria_nibo_id VARCHAR(100) NOT NULL DEFAULT '' AFTER obra_id,
ADD COLUMN categoria_nome VARCHAR(255) NOT NULL DEFAULT '' AFTER categoria_nibo_id;

-- 5. Adicionar índices (executar após uma das opções acima)
ALTER TABLE painel_obras_custos_previstos 
ADD INDEX idx_categoria (categoria_nibo_id),
ADD UNIQUE KEY uq_obra_categoria (obra_id, categoria_nibo_id);

-- 6. Verificar resultado final
DESCRIBE painel_obras_custos_previstos;
