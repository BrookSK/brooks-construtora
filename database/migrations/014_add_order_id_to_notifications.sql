-- Migration: Vincular notificações aos pedidos para histórico
-- Data: 2026-06-22

ALTER TABLE notification_queue
    ADD COLUMN order_id INT UNSIGNED DEFAULT NULL COMMENT 'Pedido relacionado (para histórico)' AFTER id,
    ADD COLUMN event_type VARCHAR(50) DEFAULT NULL COMMENT 'Fase: quote_requested, approval_requested, order_approved, order_rejected, payment_uploaded, delivery_ready, spare_item' AFTER order_id,
    ADD COLUMN recipient_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome do destinatário (para webhook)' AFTER to_email,
    ADD INDEX idx_order (order_id);
