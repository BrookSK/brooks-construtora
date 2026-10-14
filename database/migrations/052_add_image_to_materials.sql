-- Migration: Adiciona coluna image à tabela materials
-- Data: 2026-10-09
-- Guarda o caminho público da imagem do material (ex: /uploads/materials/arquivo.jpg)

ALTER TABLE `materials`
    ADD COLUMN `image` VARCHAR(500) NULL DEFAULT NULL AFTER `classification`;
