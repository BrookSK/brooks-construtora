-- =========================================================================
-- MIGRAÇÃO SIMPLIFICADA: Atualizar painel_obras_custos_previstos
-- =========================================================================

-- Tenta remover as FKs mais comuns (ignora erro se não existir)
ALTER TABLE `painel_obras_custos_previstos` DROP FOREIGN KEY `painel_obras_custos_previstos_ibfk_1`;
ALTER TABLE `painel_obras_custos_previstos` DROP FOREIGN KEY `painel_obras_custos_previstos_ibfk_2`;
ALTER TABLE `painel_obras_custos_previstos` DROP FOREIGN KEY `fk_custo_categoria`;
ALTER TABLE `painel_obras_custos_previstos` DROP FOREIGN KEY `fk_categoria`;

-- Se nenhuma FK funcionou acima, PARE AQUI e execute este comando no terminal do servidor:
-- SHOW CREATE TABLE painel_obras_custos_previstos;
-- Depois copie o nome correto da CONSTRAINT e execute:
-- ALTER TABLE painel_obras_custos_previstos DROP FOREIGN KEY nome_correto_da_fk;

-- Remove índices
ALTER TABLE `painel_obras_custos_previstos` DROP INDEX `categoria_id`;

-- Remove a coluna antiga
ALTER TABLE `painel_obras_custos_previstos` DROP COLUMN `categoria_id`;

-- Adiciona novas colunas
ALTER TABLE `painel_obras_custos_previstos` 
ADD COLUMN `categoria_nibo_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'ID da categoria no Nibo' AFTER `obra_id`,
ADD COLUMN `categoria_nome` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nome da categoria do Nibo' AFTER `categoria_nibo_id`;

-- Adiciona índices
ALTER TABLE `painel_obras_custos_previstos`
ADD INDEX `idx_categoria` (`categoria_nibo_id`),
ADD UNIQUE KEY `uq_obra_categoria` (`obra_id`, `categoria_nibo_id`);

-- Verifica resultado
DESCRIBE `painel_obras_custos_previstos`;
