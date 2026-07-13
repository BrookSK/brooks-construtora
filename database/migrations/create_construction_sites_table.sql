-- Migration: Criar tabela de Obras (Construction Sites)
-- Executar no banco de dados brooks_construtora

CREATE TABLE IF NOT EXISTS construction_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome da obra',
    code VARCHAR(50) NULL COMMENT 'Código interno da obra',
    address VARCHAR(500) NULL COMMENT 'Endereço da obra',
    city VARCHAR(100) NULL COMMENT 'Cidade',
    state VARCHAR(2) NULL COMMENT 'UF',
    responsible_name VARCHAR(255) NULL COMMENT 'Nome do responsável pela obra',
    responsible_phone VARCHAR(30) NULL COMMENT 'Telefone do responsável',
    client_name VARCHAR(255) NULL COMMENT 'Nome do cliente/proprietário',
    description TEXT NULL COMMENT 'Descrição ou observações da obra',
    status ENUM('active', 'inactive', 'completed') NOT NULL DEFAULT 'active' COMMENT 'Status da obra',
    started_at DATE NULL COMMENT 'Data de início da obra',
    expected_end_at DATE NULL COMMENT 'Previsão de término',
    completed_at DATE NULL COMMENT 'Data de conclusão real',
    created_by INT NULL COMMENT 'ID do usuário que cadastrou',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro de Obras';

-- Adicionar coluna construction_site_id na tabela de pedidos
ALTER TABLE purchase_orders 
    ADD COLUMN construction_site_id INT NULL AFTER order_type,
    ADD INDEX idx_construction_site (construction_site_id),
    ADD CONSTRAINT fk_po_construction_site 
        FOREIGN KEY (construction_site_id) REFERENCES construction_sites(id) 
        ON DELETE SET NULL ON UPDATE CASCADE;
