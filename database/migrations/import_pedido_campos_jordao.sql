-- Migration: Pedido de Material - Campos do Jordão (v2)
-- Data do pedido: 27/07/2026
-- Descrição: Pedido de materiais diversos (limpeza, EPIs, sinalização) - 13 itens

-- Gerar tokens únicos para o pedido
SET @quote_token = SHA2(CONCAT(UUID(), NOW(), RAND()), 256);
SET @approval_token = SHA2(CONCAT(UUID(), NOW(), RAND(), 'approval'), 256);

-- Obter o próximo código de pedido
SET @last_code = (SELECT COALESCE(MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)), 0) FROM purchase_orders WHERE code LIKE 'PED-%');
SET @next_code = CONCAT('PED-', LPAD(@last_code + 1, 6, '0'));

-- Buscar o ID da obra "Casa Da Montanha" (Campos do Jordão) - OBR-000001
SET @site_id = (SELECT id FROM construction_sites WHERE code = 'OBR-000001' LIMIT 1);

-- Inserir o pedido
INSERT INTO purchase_orders (code, order_type, supplier_id, construction_site_id, status, description, created_by, created_by_name, quote_token, approval_token, created_at)
VALUES (
    @next_code,
    'material',
    NULL,
    @site_id,
    'pending_quote',
    'PEDIDO DE MATERIAL - CAMPOS DO JORDÃO - Data: 27/07/2026',
    1,
    'Admin',
    @quote_token,
    @approval_token,
    '2026-07-27 00:00:00'
);

SET @order_id = LAST_INSERT_ID();

-- Inserir os 13 itens do pedido (busca material_id pelo nome, se não achar fica NULL)
INSERT INTO purchase_order_items (order_id, material_id, material_name, specification, classification, unit, quantity, source_type, created_at) VALUES
(@order_id, (SELECT id FROM materials WHERE name LIKE '%bafômetro%' AND active = 1 LIMIT 1), 'Aparelho Bafômetro', 'Equipamento', '', 'unid', 1, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%sabão%líquido%' AND active = 1 LIMIT 1), 'Sabão Líquido 5L', 'limpeza', '5L', 'unid', 2, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%desinfetante%' AND active = 1 LIMIT 1), 'Desinfetante 5L', 'limpeza', '5L', 'unid', 2, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%água sanitária%' AND active = 1 LIMIT 1), 'Água Sanitária 5L', 'limpeza', '5L', 'unid', 1, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%pano de chão%' AND active = 1 LIMIT 1), 'Pano de Chão', 'limpeza', '', 'unid', 10, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%saco de lixo%' AND classification LIKE '%20%' AND active = 1 LIMIT 1), 'Saco de Lixo 20L', 'limpeza', '20L', 'unid', 100, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%saco de lixo%' AND classification LIKE '%100%' AND active = 1 LIMIT 1), 'Saco de Lixo 100L', 'limpeza', '100L', 'unid', 100, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%protetor%vergalhão%' AND active = 1 LIMIT 1), 'Protetor de Vergalhão', 'implantação', '', 'unid', 400, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%placa%fumôdromo%' AND active = 1 LIMIT 1), 'Placa Fumôdromo (Padrão Brooks) 25x30cm', 'implantação', '25x30cm', 'unid', 1, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%placa%banheiro%feminino%' AND active = 1 LIMIT 1), 'Placa Banheiro Feminino (Padrão Brooks) 15x20cm', 'implantação', '15x20cm', 'unid', 1, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%luva%vaqueta%' AND active = 1 LIMIT 1), 'Luva Vaqueta', 'EPI', '', 'unid', 20, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%luva%pigmentada%' AND active = 1 LIMIT 1), 'Luva Pigmentada', 'EPI', '', 'unid', 30, 'purchase', '2026-07-27 00:00:00'),
(@order_id, (SELECT id FROM materials WHERE name LIKE '%óculos%proteção%escur%' AND active = 1 LIMIT 1), 'Óculos de Proteção Escuros', 'EPI', '', 'unid', 24, 'purchase', '2026-07-27 00:00:00');

-- Registrar no histórico
INSERT INTO purchase_order_history (order_id, action, description, performed_by_name, performed_by_user_id, created_at)
VALUES (@order_id, 'created', 'Pedido criado via importação direta - Campos do Jordão', 'Admin', 1, '2026-07-27 00:00:00');

-- Resultado
SELECT CONCAT('Pedido criado: ', @next_code, ' | Obra: ', COALESCE((SELECT name FROM construction_sites WHERE id = @site_id), 'N/A'), ' | Itens: 13') AS resultado;
