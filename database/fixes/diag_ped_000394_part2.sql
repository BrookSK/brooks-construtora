-- Confirmar nomes exatos dos itens restantes (1590 em diante)
SELECT i.id, i.material_name, i.quantity
FROM purchase_order_items i
WHERE i.order_id = 424 AND i.id >= 1590
ORDER BY i.id;

-- Os fornecedores já estão vinculados ao pedido?
SELECT pos.id, pos.supplier_id, s.name, pos.status, pos.total
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id = 424;
