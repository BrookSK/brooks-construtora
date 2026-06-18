-- =====================================================
-- MÓDULO DE PEDIDOS - MÚLTIPLOS FORNECEDORES POR PEDIDO
-- Migration: Novas tabelas e alterações
-- =====================================================

-- Fornecedores vinculados ao pedido (N:N)
CREATE TABLE IF NOT EXISTS purchase_order_suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    status ENUM('pending','quoted','approved','rejected') DEFAULT 'pending',
    total DECIMAL(12,2) DEFAULT NULL COMMENT 'Total cotado por este fornecedor',
    quoted_by_name VARCHAR(255) DEFAULT NULL,
    quoted_at DATETIME DEFAULT NULL,
    quote_notes TEXT DEFAULT NULL,
    approved TINYINT(1) DEFAULT 0 COMMENT '1 se este fornecedor foi o aprovado',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preços por item POR FORNECEDOR (substitui o unit_price direto no item)
CREATE TABLE IF NOT EXISTS purchase_order_item_prices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) DEFAULT NULL,
    total_price DECIMAL(12,2) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de preços por material/fornecedor (para consulta futura)
CREATE TABLE IF NOT EXISTS material_price_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED DEFAULT NULL,
    material_name VARCHAR(255) NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    was_approved TINYINT(1) DEFAULT 0 COMMENT '1 se este fornecedor foi o aprovado neste pedido',
    quoted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices
ALTER TABLE purchase_order_suppliers ADD INDEX idx_order (order_id);
ALTER TABLE purchase_order_suppliers ADD INDEX idx_supplier (supplier_id);
ALTER TABLE purchase_order_item_prices ADD INDEX idx_order_item (order_id, item_id);
ALTER TABLE purchase_order_item_prices ADD INDEX idx_supplier (supplier_id);
ALTER TABLE material_price_history ADD INDEX idx_material (material_id);
ALTER TABLE material_price_history ADD INDEX idx_supplier (supplier_id);
ALTER TABLE material_price_history ADD INDEX idx_material_supplier (material_id, supplier_id);

-- A coluna supplier_id na tabela purchase_orders agora armazena o fornecedor APROVADO (preenchido na aprovação)
-- As colunas unit_price e total_price em purchase_order_items continuam existindo para o fornecedor aprovado (snapshot final)
