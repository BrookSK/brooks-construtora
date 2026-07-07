-- Migration: Devoluções de EPI e distribuição para terceiros
-- Data: 2026-07-07

-- Tipo de destinatário na entrega: colaborador ou terceiro.
-- Mantém compatibilidade total: registros existentes ficam como 'worker'.
ALTER TABLE epi_deliveries
    ADD COLUMN recipient_type ENUM('worker','third_party') NOT NULL DEFAULT 'worker'
        COMMENT 'Destinatário: colaborador ou terceiro' AFTER id,
    ADD INDEX idx_recipient_type (recipient_type);

-- Registros de devolução de EPI (movimentação independente; não altera entregas)
CREATE TABLE IF NOT EXISTS epi_returns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_item_id INT UNSIGNED NOT NULL COMMENT 'Item de entrega original devolvido',
    epi_id INT UNSIGNED NOT NULL,
    epi_name VARCHAR(255) NOT NULL,
    ca VARCHAR(50) DEFAULT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    worker_name VARCHAR(255) NOT NULL,
    worker_document VARCHAR(100) NOT NULL,
    performed_by VARCHAR(255) NOT NULL COMMENT 'Responsável pelo registro',
    performed_by_id INT UNSIGNED DEFAULT NULL,
    photo_path VARCHAR(500) DEFAULT NULL COMMENT 'Foto do EPI devolvido',
    signature_path VARCHAR(500) DEFAULT NULL COMMENT 'Assinatura (opcional)',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery_item (delivery_item_id),
    FOREIGN KEY (delivery_item_id) REFERENCES epi_delivery_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
