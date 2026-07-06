-- Migration: Adiciona categoria aos EPIs
-- Data: 2026-07-06

ALTER TABLE epis
    ADD COLUMN category VARCHAR(100) DEFAULT NULL COMMENT 'Categoria/grupo do EPI' AFTER name,
    ADD INDEX idx_category (category);
