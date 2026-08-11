-- Migration: Adicionar role 'stock' (Estoque) para usuários PIN
-- Data: 2026-08-10

ALTER TABLE pin_users
    MODIFY COLUMN role ENUM('buyer','quoter','approver','payment','delivery','epi','stock','all') NOT NULL DEFAULT 'all' COMMENT 'Papel/permissão';

ALTER TABLE pin_invite_links
    MODIFY COLUMN role ENUM('buyer','quoter','approver','payment','delivery','epi','stock','all') NOT NULL COMMENT 'Papel/permissão do convite';
