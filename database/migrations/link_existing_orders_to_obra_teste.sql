-- Vincular todos os pedidos existentes (sem obra definida) à obra "TESTE" (OBR-000002)
-- Busca o ID da obra pelo código para garantir que funciona independente do auto_increment

UPDATE purchase_orders 
SET construction_site_id = (
    SELECT id FROM construction_sites WHERE code = 'OBR-000002' LIMIT 1
)
WHERE construction_site_id IS NULL;
