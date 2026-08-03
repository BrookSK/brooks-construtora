-- Expandir aprovadores por obra para todas as fases de notificação
-- Adicionar coluna 'phase' na tabela existente para diferenciar as fases
ALTER TABLE construction_site_approvers ADD COLUMN phase VARCHAR(20) NOT NULL DEFAULT 'approval' AFTER pin_user_id;

-- Remover unique key antiga e criar nova com phase
ALTER TABLE construction_site_approvers DROP INDEX uk_site_user;
ALTER TABLE construction_site_approvers ADD UNIQUE KEY uk_site_user_phase (construction_site_id, pin_user_id, phase);
