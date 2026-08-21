-- Tabelas para o sistema de Lista de Materiais Semanal
-- Gerentes enviam semanalmente a lista de materiais que precisam na semana seguinte

-- Cadastro dos gerentes que devem preencher a lista semanal
CREATE TABLE IF NOT EXISTS weekly_material_managers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    construction_site_id INT UNSIGNED DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro semanal: cada gerente tem um registro por semana
CREATE TABLE IF NOT EXISTS weekly_material_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    manager_id INT UNSIGNED NOT NULL,
    week_start DATE NOT NULL COMMENT 'Segunda-feira da semana de referência',
    token VARCHAR(64) NOT NULL COMMENT 'Token único para acesso ao formulário',
    status ENUM('pending','filled','overdue') NOT NULL DEFAULT 'pending',
    filled_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    audio_filename VARCHAR(255) DEFAULT NULL,
    notified_at DATETIME DEFAULT NULL COMMENT 'Quando foi enviada a notificação (terça)',
    reminder_sent_at DATETIME DEFAULT NULL COMMENT 'Quando foi enviado o lembrete (quinta)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_manager_week (manager_id, week_start),
    INDEX idx_week (week_start),
    INDEX idx_status (status),
    INDEX idx_token (token),
    CONSTRAINT fk_wmr_manager FOREIGN KEY (manager_id) REFERENCES weekly_material_managers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens de cada lista semanal
CREATE TABLE IF NOT EXISTS weekly_material_request_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_request (request_id),
    CONSTRAINT fk_wmri_request FOREIGN KEY (request_id) REFERENCES weekly_material_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
