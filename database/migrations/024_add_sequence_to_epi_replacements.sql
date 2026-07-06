-- Migration: Número sequencial da substituição por item de entrega
-- Data: 2026-07-06

ALTER TABLE epi_replacements
    ADD COLUMN sequence_number INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Nº da substituição do mesmo item (1ª, 2ª, 3ª...)' AFTER delivery_item_id;
