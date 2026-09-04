-- Verifica se os dados do INSERT persistiram (foram commitados).
-- Se as duas contagens vierem 0, houve ROLLBACK: rode o insert de novo E dê COMMIT no fim.

SELECT COUNT(*) AS precos_salvos
FROM purchase_order_item_prices
WHERE order_id = 424;

SELECT COUNT(*) AS fornecedores_vinculados
FROM purchase_order_suppliers
WHERE order_id = 424;
