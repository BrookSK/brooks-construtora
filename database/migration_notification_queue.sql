-- =====================================================
-- Fila de Notificações (e-mail + webhook)
-- Resolve problema de concorrência no envio
-- =====================================================

CREATE TABLE IF NOT EXISTS notification_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('email','webhook') NOT NULL,
    status ENUM('pending','processing','sent','failed') DEFAULT 'pending',
    
    -- Para e-mail
    to_email VARCHAR(255) DEFAULT NULL,
    subject VARCHAR(500) DEFAULT NULL,
    body LONGTEXT DEFAULT NULL,
    
    -- Para webhook
    webhook_url VARCHAR(500) DEFAULT NULL,
    webhook_payload LONGTEXT DEFAULT NULL,
    
    -- Controle
    attempts TINYINT DEFAULT 0,
    max_attempts TINYINT DEFAULT 3,
    last_error TEXT DEFAULT NULL,
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notification_queue ADD INDEX idx_status (status);
ALTER TABLE notification_queue ADD INDEX idx_scheduled (scheduled_at);
