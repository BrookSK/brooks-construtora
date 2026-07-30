-- Tabela pivot para vincular aprovadores (pin_users) a obras
CREATE TABLE IF NOT EXISTS construction_site_approvers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    construction_site_id INT NOT NULL,
    pin_user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_site_user (construction_site_id, pin_user_id),
    INDEX idx_site (construction_site_id),
    INDEX idx_user (pin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
