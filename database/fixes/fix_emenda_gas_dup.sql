-- =====================================================================
-- Ajuste da EMENDA DE GÁS duplicada (itens 1607 qtd1 e 1608 qtd2).
-- O pedido lista "emendas de 16 mm pex pra gás" duas vezes, mas cada
-- fornecedor cotou UMA linha de união de gás no PDF.
--
-- DECISÃO: manter o valor da união de gás no item 1608 (qtd 2) e
-- ZERAR o item 1607 (qtd 1), evitando cobrar em dobro.
--
-- >>> SÓ RODE SE 1607 e 1608 forem REALMENTE o mesmo item repetido. <<<
-- Autocommit.
-- =====================================================================

SET @order_id := 424;

-- Zerar a emenda duplicada (1607) nos 3 fornecedores
UPDATE purchase_order_item_prices
SET unit_price = 0, total_price = 0
WHERE order_id = @order_id AND item_id = 1607 AND supplier_id IN (62,95,118);

-- (Opcional) remover do histórico para não sujar médias futuras
DELETE FROM material_price_history
WHERE order_id = @order_id AND supplier_id IN (62,95,118)
  AND material_name = 'emendas de 16 mm pex pra gás'
  AND quantity = 1;

-- Recalcular totais
SET @order_id := 424;
UPDATE purchase_order_suppliers pos
SET subtotal_items = (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=pos.order_id AND supplier_id=pos.supplier_id),
    subtotal_final = (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=pos.order_id AND supplier_id=pos.supplier_id) + pos.freight,
    total          = (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=pos.order_id AND supplier_id=pos.supplier_id) + pos.freight
WHERE pos.order_id=@order_id AND pos.supplier_id IN (62,95,118);

-- Verificação
SELECT s.name AS fornecedor, pos.subtotal_items, pos.freight, pos.total
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id=@order_id;
