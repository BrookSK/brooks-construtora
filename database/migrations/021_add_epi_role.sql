-- Migration: Adicionar role 'epi' para usuários com acesso exclusivo à parte de EPI
-- Data: 2026-07-06

ALTER TABLE pin_users
    MODIFY COLUMN role ENUM('buyer','quoter','approver','payment','delivery','epi','all') NOT NULL DEFAULT 'all' COMMENT 'Papel/permissão';

ALTER TABLE pin_invite_links
    MODIFY COLUMN role ENUM('buyer','quoter','approver','payment','delivery','epi','all') NOT NULL COMMENT 'Papel/permissão do convite';
