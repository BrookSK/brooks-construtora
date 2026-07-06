-- Migration: Controle de EPIs (catálogo, entregas e substituições)
-- Data: 2026-07-06

-- Catálogo de EPIs disponíveis para seleção
CREATE TABLE IF NOT EXISTS epis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome do EPI',
    ca VARCHAR(50) DEFAULT NULL COMMENT 'Certificado de Aprovação (CA)',
    min_replacement_days INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Dias mínimos até poder solicitar substituição',
    active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(255) DEFAULT NULL COMMENT 'Nome do usuário PIN que cadastrou',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de entrega de EPIs a um colaborador
CREATE TABLE IF NOT EXISTS epi_deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    worker_name VARCHAR(255) NOT NULL COMMENT 'Nome do colaborador',
    worker_document VARCHAR(100) NOT NULL COMMENT 'CPF ou Matrícula',
    worker_role VARCHAR(255) NOT NULL COMMENT 'Cargo',
    delivered_by VARCHAR(255) NOT NULL COMMENT 'Responsável pela entrega (usuário PIN)',
    delivered_by_id INT UNSIGNED DEFAULT NULL COMMENT 'ID do pin_user responsável',
    selfie_path VARCHAR(500) DEFAULT NULL COMMENT 'Selfie do colaborador',
    epis_photo_path VARCHAR(500) DEFAULT NULL COMMENT 'Foto do colaborador com os EPIs',
    signature_path VARCHAR(500) DEFAULT NULL COMMENT 'Assinatura desenhada na tela',
    confirmed TINYINT(1) DEFAULT 0 COMMENT 'Confirmação do responsável',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_worker (worker_document),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens (EPIs) de cada entrega
CREATE TABLE IF NOT EXISTS epi_delivery_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT UNSIGNED NOT NULL,
    epi_id INT UNSIGNED NOT NULL,
    epi_name VARCHAR(255) NOT NULL COMMENT 'Snapshot do nome do EPI',
    ca VARCHAR(50) DEFAULT NULL COMMENT 'Snapshot do CA',
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    min_replacement_days INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Snapshot dos dias mínimos',
    delivered_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento da entrega deste item',
    replaced TINYINT(1) DEFAULT 0 COMMENT 'Já foi substituído',
    INDEX idx_delivery (delivery_id),
    INDEX idx_epi (epi_id),
    FOREIGN KEY (delivery_id) REFERENCES epi_deliveries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitações/registros de substituição de EPI
CREATE TABLE IF NOT EXISTS epi_replacements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_item_id INT UNSIGNED NOT NULL COMMENT 'Item de entrega original substituído',
    epi_id INT UNSIGNED NOT NULL,
    epi_name VARCHAR(255) NOT NULL,
    ca VARCHAR(50) DEFAULT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    worker_name VARCHAR(255) NOT NULL,
    worker_document VARCHAR(100) NOT NULL,
    performed_by VARCHAR(255) NOT NULL COMMENT 'Responsável (usuário PIN)',
    performed_by_id INT UNSIGNED DEFAULT NULL,
    old_item_photo_path VARCHAR(500) DEFAULT NULL COMMENT 'Foto do material substituído (devolvido)',
    new_delivery_photo_path VARCHAR(500) DEFAULT NULL COMMENT 'Foto da entrega ao operário',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery_item (delivery_item_id),
    FOREIGN KEY (delivery_item_id) REFERENCES epi_delivery_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
