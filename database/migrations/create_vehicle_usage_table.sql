-- Tabela de controle de uso do veículo (Saveiro)
-- Cada registro representa uma retirada. A devolução é preenchida depois.

CREATE TABLE IF NOT EXISTS vehicle_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(150) NOT NULL,
    registered_by VARCHAR(150) NOT NULL,
    registered_by_user_id INT UNSIGNED DEFAULT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    pickup_km INT UNSIGNED NOT NULL,
    destination VARCHAR(255) NOT NULL,
    pickup_notes TEXT DEFAULT NULL,
    return_date DATE DEFAULT NULL,
    return_time TIME DEFAULT NULL,
    return_km INT UNSIGNED DEFAULT NULL,
    return_notes TEXT DEFAULT NULL,
    returned_by VARCHAR(150) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,

    INDEX idx_driver (driver_name),
    INDEX idx_pickup_date (pickup_date),
    INDEX idx_return_date (return_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
