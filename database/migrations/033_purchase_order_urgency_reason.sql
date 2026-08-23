-- =====================================================
-- Justificativa de urgência no Pedido (Novo Pedido)
-- Quando o pedido é classificado como Alta ou Crítica, o solicitante deve
-- informar o motivo (ocorrência em obra / não previsto) e uma descrição.
-- =====================================================

ALTER TABLE purchase_orders
    ADD COLUMN urgency_reason_no_advance TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Motivo: não houve previsão/solicitação antecipada' AFTER urgency,
    ADD COLUMN urgency_reason_site_occurrence TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Motivo: ocorrência em obra' AFTER urgency_reason_no_advance,
    ADD COLUMN urgency_description TEXT DEFAULT NULL COMMENT 'Descrição/justificativa da urgência' AFTER urgency_reason_site_occurrence;
