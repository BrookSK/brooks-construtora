-- Rascunho da Lista Semanal salvo NO SERVIDOR (autosave via token).
-- Complementa o autosave em localStorage: o rascunho passa a ficar disponível
-- em qualquer dispositivo e pode ser recuperado pelo administrador.
--
--  - draft_data:       JSON com o snapshot do formulário (itens, obra, data, notas)
--  - draft_updated_at: quando o rascunho foi salvo pela última vez
--
-- Não afeta os rascunhos que já existem no navegador das pessoas: o front-end
-- continua lendo o localStorage e passa a MESCLAR com o servidor, escolhendo o
-- mais recente entre os dois.

ALTER TABLE `weekly_material_requests`
    ADD COLUMN `draft_data` MEDIUMTEXT DEFAULT NULL COMMENT 'Snapshot JSON do rascunho (autosave)' AFTER `notes`,
    ADD COLUMN `draft_updated_at` DATETIME DEFAULT NULL COMMENT 'Última atualização do rascunho' AFTER `draft_data`;
