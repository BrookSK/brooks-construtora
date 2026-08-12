-- =====================================================
-- Campo "Observações do Financeiro" para pedidos
-- Permite ao financeiro adicionar observações ao editar
-- itens do pedido aprovado
-- =====================================================

ALTER TABLE purchase_orders 
    ADD COLUMN financial_notes TEXT DEFAULT NULL COMMENT 'Observações do financeiro' AFTER approval_notes;
