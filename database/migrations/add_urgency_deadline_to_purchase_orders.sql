-- Adiciona campos de urgência e prazo nos pedidos de compra

ALTER TABLE purchase_orders
    ADD COLUMN urgency ENUM('low','medium','high','critical') DEFAULT 'medium' AFTER description,
    ADD COLUMN deadline DATE DEFAULT NULL AFTER urgency,
    ADD INDEX idx_urgency (urgency),
    ADD INDEX idx_deadline (deadline);
