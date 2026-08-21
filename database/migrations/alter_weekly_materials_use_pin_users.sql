-- Migra o sistema de lista semanal para usar pin_users ao invés de weekly_material_managers
-- A coluna is_weekly_manager já foi adicionada na migration add_is_weekly_manager_to_pin_users.sql

-- Alterar FK da weekly_material_requests para apontar para pin_users
ALTER TABLE weekly_material_requests
    DROP FOREIGN KEY fk_wmr_manager,
    ADD CONSTRAINT fk_wmr_pin_user FOREIGN KEY (manager_id) REFERENCES pin_users(id) ON DELETE CASCADE;

-- A tabela weekly_material_managers pode ser removida se não tiver dados importantes
-- DROP TABLE IF EXISTS weekly_material_managers;
