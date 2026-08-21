-- Adiciona flag de gerente semanal na tabela de usuários PIN
-- Quem estiver marcado como is_weekly_manager recebe a notificação semanal

ALTER TABLE pin_users
    ADD COLUMN is_weekly_manager TINYINT(1) NOT NULL DEFAULT 0 AFTER active;
