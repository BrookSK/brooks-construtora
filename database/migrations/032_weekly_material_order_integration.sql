-- =====================================================
-- INTEGRAÇÃO: Lista Semanal de Materiais  ->  Pedidos
-- A Lista Semanal passa a ser apenas uma ORIGEM de solicitação
-- para o sistema de Pedidos já existente (purchase_orders).
-- Nenhum sistema paralelo de pedidos é criado.
-- =====================================================

-- 1. Rastreio de origem e vínculo no Pedido existente
--    origin: distingue 'manual' de 'weekly_list' (e futuras origens)
--    weekly_request_id: liga o pedido à solicitação semanal (idempotência)
ALTER TABLE purchase_orders
    ADD COLUMN origin VARCHAR(30) NOT NULL DEFAULT 'manual' COMMENT 'Origem do pedido: manual, weekly_list' AFTER order_type,
    ADD COLUMN weekly_request_id INT UNSIGNED DEFAULT NULL COMMENT 'FK para weekly_material_requests quando origem=weekly_list' AFTER origin,
    ADD INDEX idx_origin (origin),
    ADD INDEX idx_weekly_request (weekly_request_id);

-- 2. Metadados gerenciais na solicitação semanal
--    (dados operacionais permanecem no Pedido; aqui só o controle da rotina)
ALTER TABLE weekly_material_requests
    ADD COLUMN construction_site_id INT UNSIGNED DEFAULT NULL COMMENT 'Obra selecionada no preenchimento' AFTER manager_id,
    ADD COLUMN order_id INT UNSIGNED DEFAULT NULL COMMENT 'Pedido gerado no sistema existente (fonte oficial)' AFTER status,
    ADD COLUMN urgency ENUM('low','medium','high','critical') DEFAULT 'medium' AFTER order_id,
    ADD COLUMN needed_date DATE DEFAULT NULL COMMENT 'Data em que o material precisa estar na obra' AFTER urgency,
    ADD COLUMN urgency_reason_no_advance TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Motivo: não houve solicitação antecipada' AFTER needed_date,
    ADD COLUMN urgency_reason_site_occurrence TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Motivo: ocorrência em obra' AFTER urgency_reason_no_advance,
    ADD COLUMN urgency_description TEXT DEFAULT NULL COMMENT 'Descrição/justificativa da urgência' AFTER urgency_reason_site_occurrence,
    ADD COLUMN response_deadline DATE DEFAULT NULL COMMENT 'Prazo para o responsável responder' AFTER urgency_description,
    ADD COLUMN opened_at DATETIME DEFAULT NULL COMMENT 'Quando o formulário foi aberto' AFTER response_deadline,
    ADD COLUMN items_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantidade de itens solicitados' AFTER opened_at,
    ADD COLUMN link_channel VARCHAR(30) DEFAULT NULL COMMENT 'Canal usado no envio do link (whatsapp/email)' AFTER items_count,
    ADD INDEX idx_order (order_id),
    ADD INDEX idx_site (construction_site_id);

-- 3. Log de auditoria da rotina semanal (PARTE 31)
CREATE TABLE IF NOT EXISTS weekly_material_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED DEFAULT NULL,
    week_start DATE DEFAULT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'week_created, link_generated, link_sent, reminder_sent, form_opened, form_submitted, order_create_attempt, order_created, order_failed, marked_overdue',
    description TEXT DEFAULT NULL,
    order_id INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request (request_id),
    INDEX idx_week (week_start),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
