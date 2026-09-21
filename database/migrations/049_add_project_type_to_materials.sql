-- Migration: Adicionar campo project_type (tipo de projeto: construção/reforma/ambos) nos materiais
-- Data: 2024

-- Adicionar coluna project_type na tabela materials
ALTER TABLE materials 
ADD COLUMN project_type ENUM('construction', 'renovation', 'both') NOT NULL DEFAULT 'both' 
AFTER active;

-- Índice para filtros por tipo de projeto
CREATE INDEX idx_materials_project_type ON materials(project_type);

-- Adicionar coluna project_type também nos itens da lista de materiais (templates)
-- para manter um snapshot do tipo no momento da adição à lista
ALTER TABLE material_template_items 
ADD COLUMN project_type ENUM('construction', 'renovation', 'both') NULL DEFAULT NULL 
AFTER unit;
