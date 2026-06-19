-- =====================================================
-- NF e Boleto - Controle de pagamento
-- =====================================================

CREATE TABLE IF NOT EXISTS purchase_order_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    type ENUM('nf','boleto') NOT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    number VARCHAR(100) DEFAULT NULL COMMENT 'Número da NF ou boleto',
    amount DECIMAL(12,2) DEFAULT NULL,
    due_date DATE DEFAULT NULL COMMENT 'Data de vencimento',
    paid_at DATE DEFAULT NULL COMMENT 'Data do pagamento',
    paid TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    uploaded_by VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE purchase_order_payments ADD INDEX idx_order (order_id);
ALTER TABLE purchase_order_payments ADD INDEX idx_type (type);
ALTER TABLE purchase_order_payments ADD INDEX idx_paid (paid);
