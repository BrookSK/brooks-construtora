-- Migration: Adiciona campos de justificativa e comprovante nos itens sobressalentes
-- Data: 2026-06-23

ALTER TABLE purchase_order_spare_items
    ADD COLUMN justification TEXT DEFAULT NULL COMMENT 'Justificativa obrigatória do uso' AFTER notes,
    ADD COLUMN receipt_path VARCHAR(500) DEFAULT NULL COMMENT 'Caminho do comprovante/foto' AFTER justification,
    ADD COLUMN receipt_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome original do arquivo' AFTER receipt_path;
