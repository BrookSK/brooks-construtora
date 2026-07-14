-- Vincular todos os pedidos existentes (sem obra) à obra ID 1 (OBR-000001 - tetse 4)
UPDATE purchase_orders SET construction_site_id = 1 WHERE construction_site_id IS NULL;
