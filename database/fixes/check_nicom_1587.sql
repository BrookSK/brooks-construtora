-- Conferir o que está salvo para o parafuso (item 1587) na NICOM (62)
SELECT supplier_id, item_id, unit_price, total_price
FROM purchase_order_item_prices
WHERE order_id = 424 AND item_id = 1587;
