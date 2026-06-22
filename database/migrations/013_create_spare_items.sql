-- Migration: Itens sobressalentes / comprados na hora
-- Data: 2026-06-22
-- Controle de gastos avulsos vinculados a pedidos, com orçamento semanal

-- Tabela de itens sobressalentes
CREATE TABLE IF NOT EXISTS purchase_order_spare_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL COMMENT 'Nome/descrição do item',
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit VARCHAR(50) DEFAULT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    supplier_name VARCHAR(255) DEFAULT NULL COMMENT 'Onde comprou (texto livre)',
    payment_method ENUM('pix','boleto','cartao','transferencia','dinheiro','outro') DEFAULT NULL,
    purchased_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem comprou',
    purchased_at DATE DEFAULT NULL COMMENT 'Data da compra',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_order (order_id),
    INDEX idx_purchased_at (purchased_at),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
