-- Migration: Adicionar tabela de edições de pedidos e campo quote_started_at
-- Data: 2026-08-03

-- Tabela para registrar edições de itens do pedido
CREATE TABLE IF NOT EXISTS purchase_order_edits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    edited_by_name VARCHAR(255) NOT NULL,
    edited_by_user_id INT UNSIGNED DEFAULT NULL,
    changes JSON NOT NULL COMMENT 'JSON com itens antigos e novos: {added:[], removed:[], changed:[]}',
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campo para marcar quando a cotação foi iniciada (trava edição)
ALTER TABLE purchase_orders ADD COLUMN quote_started_at DATETIME DEFAULT NULL COMMENT 'Quando o cotador clicou em Iniciar Cotação (trava edição de itens)' AFTER quoted_at;
ALTER TABLE purchase_orders ADD COLUMN quote_started_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem iniciou a cotação' AFTER quote_started_at;
