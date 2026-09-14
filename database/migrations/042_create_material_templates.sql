-- =====================================================
-- LISTAS DE MATERIAIS PRÉ-DEFINIDAS (TEMPLATES)
-- Migration: 042
-- =====================================================
-- Permite montar "listas" de materiais reutilizáveis
-- (ex: "Início de Obra", "Elétrica", "Hidráulica") que
-- podem ser aplicadas de uma vez na criação de um pedido,
-- vindo com materiais, especificações, classificação e
-- quantidades pré-selecionadas. O gerente ativa/desativa
-- itens e ajusta quantidades antes de enviar o pedido.
-- =====================================================

-- Cabeçalho da lista
CREATE TABLE IF NOT EXISTS material_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL COMMENT 'Ex: Início de Obra, Elétrica, Hidráulica',
    description VARCHAR(255) DEFAULT NULL COMMENT 'Descrição/observação da lista',
    active TINYINT(1) DEFAULT 1,
    created_by_name VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens da lista
CREATE TABLE IF NOT EXISTS material_template_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED DEFAULT NULL COMMENT 'Vínculo ao material cadastrado (pode ser nulo para item avulso)',
    material_name VARCHAR(255) NOT NULL COMMENT 'Snapshot do nome do material',
    specification VARCHAR(100) DEFAULT NULL,
    classification VARCHAR(100) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    default_quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Quantidade padrão pré-selecionada',
    sort_order INT UNSIGNED DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para performance
ALTER TABLE material_templates ADD INDEX idx_active (active);
ALTER TABLE material_template_items ADD INDEX idx_template (template_id);
ALTER TABLE material_template_items ADD INDEX idx_material (material_id);

-- =====================================================
-- LISTAS INICIAIS (baseadas nas categorias existentes)
-- =====================================================
-- Cria uma lista para cada categoria já cadastrada e
-- popula automaticamente com os materiais ativos daquela
-- categoria (quantidade padrão = 1). Idempotente: só cria
-- listas que ainda não existam pelo nome.

INSERT INTO material_templates (name, description, created_by_name)
SELECT CONCAT('Início de Obra'), 'Itens comuns para o início de uma obra', 'Sistema'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM material_templates WHERE name = 'Início de Obra');

-- Uma lista por categoria de material existente
INSERT INTO material_templates (name, description, created_by_name)
SELECT mc.name, CONCAT('Lista de materiais da categoria ', mc.name), 'Sistema'
FROM material_categories mc
WHERE NOT EXISTS (
    SELECT 1 FROM material_templates mt WHERE mt.name = mc.name
);

-- Popular cada lista de categoria com os materiais ativos correspondentes
INSERT INTO material_template_items
    (template_id, material_id, material_name, specification, classification, unit, default_quantity, sort_order)
SELECT
    mt.id,
    m.id,
    m.name,
    COALESCE(mc.name, m.specification),
    m.classification,
    mu.abbreviation,
    1.00,
    m.id
FROM materials m
JOIN material_categories mc ON m.category_id = mc.id
JOIN material_templates mt ON mt.name = mc.name
LEFT JOIN measurement_units mu ON m.unit_id = mu.id
WHERE m.active = 1
  AND NOT EXISTS (
      SELECT 1 FROM material_template_items mti
      WHERE mti.template_id = mt.id AND mti.material_id = m.id
  );
