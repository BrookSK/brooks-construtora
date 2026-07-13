-- Vincular todos os pedidos existentes (sem obra definida) à obra "TESTE" (OBR-000002)

-- Passo 1: Garantir que a coluna construction_site_id existe em purchase_orders
-- (rode este comando primeiro, se der erro de "Duplicate column" pode ignorar)
ALTER TABLE purchase_orders 
    ADD COLUMN construction_site_id INT NULL AFTER order_type;

ALTER TABLE purchase_orders 
    ADD INDEX idx_construction_site (construction_site_id);

-- Passo 2: Vincular todos os pedidos à obra TESTE
UPDATE purchase_orders 
SET construction_site_id = (
    SELECT id FROM construction_sites WHERE code = 'OBR-000002' LIMIT 1
)
WHERE construction_site_id IS NULL;
