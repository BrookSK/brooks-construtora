-- Migration: Sistema de perguntas/observações entre aprovador e cotador
-- Data: 2026-06-23

CREATE TABLE IF NOT EXISTS purchase_order_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    author_name VARCHAR(255) NOT NULL COMMENT 'Quem escreveu',
    author_role ENUM('approver','quoter') NOT NULL COMMENT 'Papel: aprovador ou cotador',
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
