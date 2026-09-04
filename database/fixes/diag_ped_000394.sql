-- =====================================================================
-- DIAGNÓSTICO PED-000394
-- Rode cada SELECT e me mande o resultado (ou um print).
-- Serve para descobrir por que o UPDATE não alterou nada.
-- =====================================================================

-- 1) O pedido existe e qual o id?
SELECT id, code, status FROM purchase_orders WHERE code = 'PED-000394';

-- 2) Como estão cadastrados os nomes dos fornecedores?
SELECT id, name FROM suppliers
WHERE name LIKE '%MIMOSA%' OR name LIKE '%NICOM%' OR name LIKE '%TOGNINI%';

-- 3) EXISTE preço salvo para este pedido? (o mais importante)
--    Se voltar 0 linhas, os preços NÃO estão no banco (só na tela).
SELECT COUNT(*) AS total_precos_salvos
FROM purchase_order_item_prices pip
JOIN purchase_orders po ON po.id = pip.order_id
WHERE po.code = 'PED-000394';

-- 4) Amostra dos preços salvos (se houver)
SELECT s.name AS fornecedor, i.material_name, i.quantity,
       pip.unit_price, pip.total_price
FROM purchase_order_item_prices pip
JOIN purchase_orders po ON po.id = pip.order_id
JOIN purchase_order_items i ON i.id = pip.item_id
JOIN suppliers s ON s.id = pip.supplier_id
WHERE po.code = 'PED-000394'
ORDER BY s.name, i.id
LIMIT 20;

-- 5) Confirmar os nomes EXATOS dos itens do pedido (para o match do UPDATE)
SELECT i.id, i.material_name, i.quantity
FROM purchase_order_items i
JOIN purchase_orders po ON po.id = i.order_id
WHERE po.code = 'PED-000394'
ORDER BY i.id;
