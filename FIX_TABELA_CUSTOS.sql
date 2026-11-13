-- =========================================================================
-- MIGRAÇÃO: Atualizar tabela painel_obras_custos_previstos
-- Para usar categorias do Nibo diretamente (VARCHAR) ao invés de FK (INT)
-- =========================================================================

-- 1. Remove índices e constraints relacionados à categoria_id
ALTER TABLE `painel_obras_custos_previstos` 
DROP INDEX IF EXISTS `categoria_id`;

ALTER TABLE `painel_obras_custos_previstos` 
DROP INDEX IF EXISTS `fk_categoria_id`;

-- 2. Remove FKs se existirem (tenta vários nomes possíveis)
SET @sql1 = IF(
    EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS 
           WHERE CONSTRAINT_SCHEMA = DATABASE() 
           AND TABLE_NAME = 'painel_obras_custos_previstos' 
           AND CONSTRAINT_NAME = 'fk_custo_categoria'),
    'ALTER TABLE painel_obras_custos_previstos DROP FOREIGN KEY fk_custo_categoria',
    'SELECT "FK fk_custo_categoria não existe"'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @sql2 = IF(
    EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS 
           WHERE CONSTRAINT_SCHEMA = DATABASE() 
           AND TABLE_NAME = 'painel_obras_custos_previstos' 
           AND CONSTRAINT_NAME = 'painel_obras_custos_previstos_ibfk_1'),
    'ALTER TABLE painel_obras_custos_previstos DROP FOREIGN KEY painel_obras_custos_previstos_ibfk_1',
    'SELECT "FK painel_obras_custos_previstos_ibfk_1 não existe"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Agora remove a coluna categoria_id
ALTER TABLE `painel_obras_custos_previstos` 
DROP COLUMN `categoria_id`;

-- 4. Adiciona as novas colunas para categorias do Nibo
ALTER TABLE `painel_obras_custos_previstos` 
ADD COLUMN `categoria_nibo_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'ID da categoria no Nibo' AFTER `obra_id`,
ADD COLUMN `categoria_nome` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nome da categoria do Nibo' AFTER `categoria_nibo_id`;

-- 5. Adiciona índices
ALTER TABLE `painel_obras_custos_previstos`
ADD INDEX `idx_categoria` (`categoria_nibo_id`),
ADD UNIQUE KEY `uq_obra_categoria` (`obra_id`, `categoria_nibo_id`);

-- 6. Verifica a estrutura final
DESCRIBE `painel_obras_custos_previstos`;

-- =========================================================================
-- Estrutura esperada após a migração:
-- - id (INT)
-- - obra_id (INT)
-- - categoria_nibo_id (VARCHAR) ← NOVO
-- - categoria_nome (VARCHAR) ← NOVO
-- - valor_previsto (DECIMAL)
-- - observacoes (TEXT)
-- - created_at (DATETIME)
-- - updated_at (DATETIME)
-- =========================================================================
