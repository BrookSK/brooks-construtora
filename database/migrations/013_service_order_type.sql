-- =====================================================
-- MÓDULO DE PEDIDOS - TIPO SERVIÇO
-- Migration: Adicionar tipo ao pedido (material/serviço) 
-- e tabela de materiais do fornecedor de serviço
-- =====================================================

-- 1. Adicionar coluna order_type na tabela de pedidos
ALTER TABLE purchase_orders
    ADD COLUMN order_type ENUM('material','service') DEFAULT 'material' COMMENT 'Tipo do pedido: material ou serviço' AFTER code;

-- 2. Tabela para armazenar PDFs enviados pelo fornecedor de serviço na cotação
CREATE TABLE IF NOT EXISTS purchase_order_supplier_pdfs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'Caminho do PDF salvo',
    original_name VARCHAR(255) DEFAULT NULL COMMENT 'Nome original do arquivo',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(255) DEFAULT NULL COMMENT 'Quem fez o upload',
    
    INDEX idx_order (order_id),
    INDEX idx_supplier (supplier_id),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela para armazenar materiais extraídos do PDF do fornecedor de serviço
CREATE TABLE IF NOT EXISTS purchase_order_supplier_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    pdf_id INT UNSIGNED DEFAULT NULL COMMENT 'Referência ao PDF de origem',
    material_id INT UNSIGNED DEFAULT NULL COMMENT 'Vínculo com material cadastrado (se encontrado)',
    material_name VARCHAR(255) NOT NULL COMMENT 'Nome do material conforme PDF',
    description TEXT DEFAULT NULL COMMENT 'Descrição adicional',
    specification VARCHAR(100) DEFAULT NULL,
    classification VARCHAR(100) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    quantity DECIMAL(10,3) DEFAULT 1 COMMENT 'Quantidade',
    weight DECIMAL(10,3) DEFAULT NULL COMMENT 'Peso (se informado)',
    unit_price DECIMAL(12,2) DEFAULT NULL COMMENT 'Preço unitário conforme PDF',
    total_price DECIMAL(12,2) DEFAULT NULL COMMENT 'Preço total do item',
    subtotal DECIMAL(12,2) DEFAULT NULL COMMENT 'Subtotal (se informado no PDF)',
    discount DECIMAL(12,2) DEFAULT NULL COMMENT 'Desconto (se informado no PDF)',
    freight DECIMAL(12,2) DEFAULT NULL COMMENT 'Frete (se informado no PDF)',
    ipi DECIMAL(12,2) DEFAULT NULL COMMENT 'IPI (se informado no PDF)',
    icms_st DECIMAL(12,2) DEFAULT NULL COMMENT 'ICMS-ST (se informado no PDF)',
    grand_total DECIMAL(12,2) DEFAULT NULL COMMENT 'Total geral (se informado no PDF)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_order (order_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_pdf (pdf_id),
    INDEX idx_material (material_id),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (pdf_id) REFERENCES purchase_order_supplier_pdfs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
