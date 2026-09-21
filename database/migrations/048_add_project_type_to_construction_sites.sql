-- Migration: Adicionar campo project_type (tipo de projeto: construção/reforma) nas obras
-- Data: 2024

-- Adicionar coluna project_type na tabela construction_sites
ALTER TABLE construction_sites 
ADD COLUMN project_type ENUM('construction', 'renovation') NOT NULL DEFAULT 'construction' 
AFTER status;

-- Índice para filtros por tipo de projeto
CREATE INDEX idx_construction_sites_project_type ON construction_sites(project_type);
