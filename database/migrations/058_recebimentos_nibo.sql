-- Permite confirmar recebimento de receitas (a receber) vindas do Nibo, além de entrada/parcela do planejamento.
ALTER TABLE `painel_obras_recebimentos`
  MODIFY COLUMN `tipo` ENUM('entrada','parcela','nibo') NOT NULL,
  ADD COLUMN `nibo_schedule_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'ID do agendamento (receita) no Nibo, quando tipo = nibo' AFTER `numero_parcela`,
  ADD INDEX `idx_nibo_schedule` (`obra_id`, `nibo_schedule_id`);
