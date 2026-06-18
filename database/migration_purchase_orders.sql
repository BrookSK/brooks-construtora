-- =====================================================
-- MÓDULO DE PEDIDOS DE MATERIAIS - BROOKS CONSTRUTORA
-- Migration: Criação das tabelas
-- =====================================================
-- IMPORTANTE: Se você rodou uma versão anterior deste script,
-- execute antes: DROP TABLE IF EXISTS purchase_order_history, purchase_order_items, purchase_orders, materials, measurement_units, material_categories, suppliers;

-- Fornecedores
CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classificações de materiais
CREATE TABLE IF NOT EXISTS material_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unidades de medida
CREATE TABLE IF NOT EXISTS measurement_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    abbreviation VARCHAR(10) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materiais
CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specification VARCHAR(100) DEFAULT NULL COMMENT 'Ex: mat. Hidraulica, mat. Civil, madeira, limpeza',
    category_id INT UNSIGNED DEFAULT NULL,
    unit_id INT UNSIGNED DEFAULT NULL,
    classification VARCHAR(100) DEFAULT NULL COMMENT 'Ex: 100mm, 3/4", 50x40, 500L',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedidos de compra
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código do pedido: PED-000001',
    supplier_id INT UNSIGNED DEFAULT NULL,
    status ENUM('draft','pending_quote','quoted','pending_approval','approved','rejected','cancelled') DEFAULT 'draft',
    description TEXT DEFAULT NULL COMMENT 'Observações do pedido',
    total_estimated DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total estimado (preenchido na cotação)',
    
    created_by INT UNSIGNED DEFAULT NULL,
    created_by_name VARCHAR(255) DEFAULT NULL,
    
    quote_token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token para acesso público à cotação',
    quoted_by_name VARCHAR(255) DEFAULT NULL,
    quoted_at DATETIME DEFAULT NULL,
    quote_notes TEXT DEFAULT NULL,
    
    approval_token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token para acesso público à aprovação',
    approved_by_name VARCHAR(255) DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejected_by_name VARCHAR(255) DEFAULT NULL,
    rejected_at DATETIME DEFAULT NULL,
    approval_notes TEXT DEFAULT NULL,
    
    pdf_generated_at DATETIME DEFAULT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens do pedido
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED DEFAULT NULL,
    material_name VARCHAR(255) NOT NULL COMMENT 'Nome do material (snapshot)',
    specification VARCHAR(100) DEFAULT NULL,
    classification VARCHAR(100) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) DEFAULT NULL COMMENT 'Preço unitário (preenchido na cotação)',
    total_price DECIMAL(12,2) DEFAULT NULL COMMENT 'Preço total do item',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de ações do pedido
CREATE TABLE IF NOT EXISTS purchase_order_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'created, sent_to_quote, quoted, sent_to_approval, approved, rejected, cancelled',
    description TEXT DEFAULT NULL,
    performed_by_name VARCHAR(255) DEFAULT NULL,
    performed_by_user_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES para performance
-- =====================================================
ALTER TABLE purchase_orders ADD INDEX idx_status (status);
ALTER TABLE purchase_orders ADD INDEX idx_supplier (supplier_id);
ALTER TABLE purchase_orders ADD INDEX idx_created_by (created_by);
ALTER TABLE purchase_order_items ADD INDEX idx_order_id (order_id);
ALTER TABLE purchase_order_history ADD INDEX idx_order_id (order_id);
ALTER TABLE materials ADD INDEX idx_category (category_id);
ALTER TABLE materials ADD INDEX idx_unit (unit_id);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Categorias de materiais (baseado na planilha)
INSERT INTO material_categories (name) VALUES
('mat. Hidraulica'),
('mat. Civil'),
('mat. Metálico'),
('madeira'),
('limpeza'),
('implantação'),
('mat. Elétrico'),
('pintura'),
('ferramentas');

-- Unidades de medida
INSERT INTO measurement_units (name, abbreviation) VALUES
('Unidade', 'unid'),
('Metros', 'mts'),
('Metro Quadrado', 'm²'),
('Litro', 'L'),
('Quilograma', 'kg'),
('Pacote', 'pct'),
('Rolo', 'rolo'),
('Saco', 'saco'),
('Peça', 'pç');

-- Materiais pré-cadastrados (baseado na planilha enviada)
INSERT INTO materials (name, specification, category_id, unit_id, classification) VALUES
('joelho - esgoto', 'mat. Hidraulica', 1, 1, '100mm'),
('joelho - esgoto', 'mat. Hidraulica', 1, 1, '40mm'),
('derivação Y - esgoto', 'mat. Hidraulica', 1, 1, '100mm'),
('redução - esgoto', 'mat. Hidraulica', 1, 1, '100x50'),
('redução - esgoto', 'mat. Hidraulica', 1, 1, '50x40'),
('joelho com anel - esgoto', 'mat. Hidraulica', 1, 1, '40mm'),
('cano - esgoto', 'mat. Hidraulica', 1, 2, '100mm'),
('cano - esgoto', 'mat. Hidraulica', 1, 2, '40mm'),
('caixa sifonada', 'mat. Hidraulica', 1, 1, '100x100'),
('cap com anel - esgoto', 'mat. Hidraulica', 1, 1, '100mm'),
('tubo - esgoto', 'mat. Hidraulica', 1, 2, '50mm'),
('Te - esgoto', 'mat. Hidraulica', 1, 1, '100mm'),
('kit fossa septica 1500L/dia e leito de secagem', 'mat. Hidraulica', 1, 1, NULL),
('mangueira ar/agua preta pol 300lbs', 'mat. Hidraulica', 1, 2, '3/4 "'),
('Te interno triplo - mangueira preta', 'mat. Hidraulica', 1, 1, '3/4 "'),
('adaptador interno - mangueira preta', 'mat. Hidraulica', 1, 1, '3/4 "'),
('flange - soldavel', 'mat. Hidraulica', 1, 1, '50mm'),
('flange - soldavel', 'mat. Hidraulica', 1, 1, '3/4 "'),
('Te - soldavel', 'mat. Hidraulica', 1, 1, '50x3/4 "'),
('luva azul', 'mat. Hidraulica', 1, 1, '3/4 "'),
('cano - soldavel', 'mat. Hidraulica', 1, 2, '3/4 "'),
('joelho - soldavel', 'mat. Hidraulica', 1, 1, '3/4 "'),
('luva - soldavel', 'mat. Hidraulica', 1, 1, '3/4 "'),
('cola cano com pincel', 'mat. Hidraulica', 1, 1, NULL),
('lixa d\'água', 'mat. Hidraulica', 1, 1, '120'),
('caixa d\'água', 'mat. Hidraulica', 1, 1, '500L'),
('abraçadeira parafuso inox', 'mat. Metálico', 3, 1, '3/4 "'),
('boia p caixa d\'agua DECA', 'mat. Hidraulica', 1, 1, '3/4 "'),
('tambor plástico azul', 'mat. Hidraulica', 1, 1, '200L'),
('Brita 01', 'mat. Civil', 2, 2, '1'),
('prancha cedrinho - 4mts', 'madeira', 4, 2, '15x5'),
('tábua cedrinho - 2mts', 'madeira', 4, 2, '20x2'),
('escada extensiva 19 degraus tipo D e Fibra', 'mat. Civil', 2, 1, '3,60x6mts'),
('saco de lixo - pacote', 'limpeza', 5, 1, '200L'),
('bebedouro purificador', 'implantação', 6, 1, NULL);
