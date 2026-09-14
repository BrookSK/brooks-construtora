-- =====================================================
-- Status "no_items" (stand-by) na Lista Semanal de Materiais
-- =====================================================
-- Permite que o responsável ENCERRE o ciclo do link informando que
-- "não tem itens a solicitar" nesta semana, sem gerar Pedido.
--
-- Mudança ADITIVA e RETROCOMPATÍVEL:
--   - Apenas adiciona o valor 'no_items' ao ENUM de status existente.
--   - Mantém os valores atuais (pending, filled, overdue) e o DEFAULT.
--   - Não remove colunas, não altera dados existentes.
--
-- Semântica: 'no_items' = respondeu ao ciclo, porém sem itens (sem pedido).
--            Fica fora da cobrança/atraso (que só atuam sobre 'pending').
-- =====================================================

ALTER TABLE weekly_material_requests
    MODIFY COLUMN status ENUM('pending','filled','overdue','no_items')
        NOT NULL DEFAULT 'pending'
        COMMENT 'pending, filled (com pedido), overdue, no_items (encerrado sem itens)';
