-- Migration: Usuários com login por PIN (acesso rápido às telas públicas)
-- Data: 2026-06-23

CREATE TABLE IF NOT EXISTS pin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    pin VARCHAR(4) NOT NULL UNIQUE COMMENT 'PIN de 4 dígitos (único por usuário)',
    role ENUM('buyer','quoter','approver','payment','delivery','all') NOT NULL DEFAULT 'all' COMMENT 'Papel/permissão',
    recovery_phrase VARCHAR(255) DEFAULT NULL COMMENT 'Frase de recuperação',
    session_token VARCHAR(64) DEFAULT NULL COMMENT 'Token de sessão persistente',
    session_expires_at DATETIME DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pin (pin),
    INDEX idx_session (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Links de convite para cadastro
CREATE TABLE IF NOT EXISTS pin_invite_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    role ENUM('buyer','quoter','approver','payment','delivery','all') NOT NULL,
    description VARCHAR(255) DEFAULT NULL COMMENT 'Descrição do convite',
    max_uses INT DEFAULT NULL COMMENT 'Máximo de usos (NULL = ilimitado)',
    uses INT DEFAULT 0,
    created_by INT UNSIGNED DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
