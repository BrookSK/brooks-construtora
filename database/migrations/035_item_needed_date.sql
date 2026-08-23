-- =====================================================
-- Data de necessidade POR ITEM (opcional)
-- Dentro da janela do ciclo, o responsável pode definir uma data específica
-- para cada material, priorizando os que precisa com maior antecedência.
-- =====================================================

-- Item da solicitação semanal
ALTER TABLE weekly_material_request_items
    ADD COLUMN needed_date DATE DEFAULT NULL COMMENT 'Data específica de necessidade do item (opcional, dentro da janela)' AFTER unit;

-- Item do Pedido (para o dado seguir no fluxo oficial de compras)
ALTER TABLE purchase_order_items
    ADD COLUMN needed_date DATE DEFAULT NULL COMMENT 'Data específica de necessidade do item (origem: Lista Semanal)' AFTER quantity;
