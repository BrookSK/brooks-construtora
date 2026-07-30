-- Adicionar campo de telefone/WhatsApp na tabela pin_users
ALTER TABLE pin_users ADD COLUMN phone VARCHAR(20) NULL AFTER email;
