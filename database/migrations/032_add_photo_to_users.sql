-- Migration: Adiciona coluna photo à tabela users
-- Data: 2026-08-19

ALTER TABLE `users`
    ADD COLUMN `photo` VARCHAR(500) NULL DEFAULT NULL AFTER `role`;
