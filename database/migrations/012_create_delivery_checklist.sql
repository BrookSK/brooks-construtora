-- Migration: Checklist de entrega dos itens do pedido
-- Data: 2026-06-22
-- Controla o status de entrega de cada item, com data combinada e registro de divergências

CREATE TABLE IF NOT EXISTS purchase_order_deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED DEFAULT NULL COMMENT 'Fornecedor aprovado deste item',

    -- Datas
    expected_date DATE DEFAULT NULL COMMENT 'Data combinada de entrega',
    delivered_at DATETIME DEFAULT NULL COMMENT 'Data/hora em que foi entregue',

    -- Status: pending, delivered, checked, divergence, replacement_requested, replacement_delivered
    status ENUM('pending','delivered','checked','divergence','replacement_requested','replacement_delivered') DEFAULT 'pending',

    -- Divergência
    divergence_notes TEXT DEFAULT NULL COMMENT 'Descrição do problema na entrega',
    replacement_requested_at DATETIME DEFAULT NULL COMMENT 'Quando foi solicitada a troca',
    replacement_expected_date DATE DEFAULT NULL COMMENT 'Data prevista para entrega da troca',
    replacement_delivered_at DATETIME DEFAULT NULL COMMENT 'Quando a troca foi entregue',
    replacement_notes TEXT DEFAULT NULL COMMENT 'Observações da troca',

    -- Controle
    checked_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem conferiu',
    notes TEXT DEFAULT NULL COMMENT 'Observações gerais',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order (order_id),
    INDEX idx_item (item_id),
    INDEX idx_status (status),
    INDEX idx_expected (expected_date),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES purchase_order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de alterações do checklist
CREATE TABLE IF NOT EXISTS purchase_order_delivery_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'Ação realizada',
    description TEXT DEFAULT NULL,
    performed_by VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery (delivery_id),
    INDEX idx_order (order_id),
    FOREIGN KEY (delivery_id) REFERENCES purchase_order_deliveries(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token para acesso público ao checklist de entrega
ALTER TABLE purchase_orders
    ADD COLUMN delivery_token VARCHAR(64) DEFAULT NULL COMMENT 'Token para acesso público ao checklist de entrega' AFTER approval_token;

-- Forma de pagamento combinada por fornecedor
ALTER TABLE purchase_order_suppliers
    ADD COLUMN payment_method ENUM('pix','boleto','cartao','transferencia','dinheiro','outro') DEFAULT NULL COMMENT 'Forma de pagamento combinada' AFTER delivery_notes,
    ADD COLUMN payment_condition VARCHAR(255) DEFAULT NULL COMMENT 'Condição: à vista, 30/60/90, 1+2x, etc' AFTER payment_method,
    ADD COLUMN payment_first_due DATE DEFAULT NULL COMMENT 'Vencimento da primeira parcela' AFTER payment_condition,
    ADD COLUMN payment_notes TEXT DEFAULT NULL COMMENT 'Observações de pagamento' AFTER payment_first_due;

