-- Migration: Adiciona campo de quantidade recebida no checklist de entrega
-- Data: 2026-06-23
-- Permite registrar quando a quantidade entregue é diferente da pedida

ALTER TABLE purchase_order_deliveries
    ADD COLUMN received_quantity DECIMAL(10,2) DEFAULT NULL COMMENT 'Quantidade efetivamente recebida' AFTER delivered_at;
