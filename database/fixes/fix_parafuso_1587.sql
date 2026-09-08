-- =====================================================================
-- Correção do item 1587 (parafusos com bucha) — caso especial de embalagem
-- Roda em autocommit (sem transação manual).
--
-- Contexto: qtd do pedido = 20. O total_price = unit * 20 estava inflando
-- fornecedores que vendem por CAIXA/menor quantidade.
--
--   NICOM (62):  cotou 1 caixa de 100un por R$ 59,90 -> total = 59,90
--   MIMOSA (118): cotou 4un a 4,65 = R$ 18,60 (PDF seq 021) -> total = 18,60
--   TOGNINI (95): 0,23/un * 20 = 4,60 (já correto, mas normalizamos)
-- =====================================================================

SET @order_id := 424;

-- NICOM: total fixo 59,90 (unit ajustado para bater com qtd 20)
UPDATE purchase_order_item_prices
SET unit_price = ROUND(59.90/20, 4), total_price = 59.90
WHERE order_id = @order_id AND supplier_id = 62 AND item_id = 1587;

-- CASA MIMOSA: total fixo 18,60 (4un a 4,65 no PDF)
UPDATE purchase_order_item_prices
SET unit_price = ROUND(18.60/20, 4), total_price = 18.60
WHERE order_id = @order_id AND supplier_id = 118 AND item_id = 1587;

-- CASA TOGNINI: 0,23/un * 20 = 4,60
UPDATE purchase_order_item_prices
SET unit_price = 0.23, total_price = 4.60
WHERE order_id = @order_id AND supplier_id = 95 AND item_id = 1587;

-- Recalcular totais dos 3 fornecedores
UPDATE purchase_order_suppliers pos
SET subtotal_items = (
        SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices
        WHERE order_id = pos.order_id AND supplier_id = pos.supplier_id
    ),
    subtotal_final = (
        SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices
        WHERE order_id = pos.order_id AND supplier_id = pos.supplier_id
    ) + pos.freight,
    total = (
        SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices
        WHERE order_id = pos.order_id AND supplier_id = pos.supplier_id
    ) + pos.freight
WHERE pos.order_id = @order_id AND pos.supplier_id IN (62,118,95);

-- Verificação
SELECT s.name AS fornecedor, pip.unit_price, pip.total_price
FROM purchase_order_item_prices pip
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id = @order_id AND pip.item_id = 1587;

SELECT s.name AS fornecedor, pos.subtotal_items, pos.freight, pos.total
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id = @order_id;
