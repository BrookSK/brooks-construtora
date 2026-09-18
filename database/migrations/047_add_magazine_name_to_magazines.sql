-- Nome da revista usado nas NOTIFICAÇÕES (e-mail e WhatsApp).
--
-- É um campo próprio, separado de:
--   - title/subtitle  → aparecem na CAPA/PDF da revista
--   - topic_id (tema)  → assunto que gerou o conteúdo (só referência)
--
-- Assim o gestor controla o nome que o cliente vê na notificação, sem alterar
-- a capa nem depender do título do tema.

ALTER TABLE `magazines`
    ADD COLUMN `magazine_name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome exibido nas notificações (e-mail/WhatsApp)' AFTER `subtitle`;
