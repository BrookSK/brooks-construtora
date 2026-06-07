-- Migration: Adiciona configurações do cron
-- Data: 2026-06-07

INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`) VALUES
('cron_token', '', NOW()),
('cron_last_run', '', NOW()),
('cron_last_generated', '', NOW()),
('cron_last_magazine_id', '', NOW())
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
