-- Tabela para armazenar áudios gravados nos pedidos de compra
-- Cada pedido pode ter múltiplos áudios em cada etapa (criação, cotação, aprovação, financeiro)

CREATE TABLE IF NOT EXISTS purchase_order_audios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    stage ENUM('create', 'quote', 'approval', 'financial') NOT NULL DEFAULT 'create',
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    duration_seconds INT UNSIGNED DEFAULT NULL,
    recorded_by VARCHAR(150) DEFAULT NULL,
    recorded_by_user_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_order_id (order_id),
    INDEX idx_order_stage (order_id, stage),
    CONSTRAINT fk_po_audio_order FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
