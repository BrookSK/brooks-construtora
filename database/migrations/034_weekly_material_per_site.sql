-- =====================================================
-- Lista Semanal: um link por (responsável, obra, ciclo)
-- Antes havia apenas um registro por (responsável, semana). Agora cada
-- responsável recebe um link único para CADA obra em que participa.
-- =====================================================

-- IMPORTANTE: a FK de manager_id usa o índice antigo (uk_manager_week).
-- Por isso criamos o NOVO índice primeiro (a FK passa a poder usá-lo) e
-- só então removemos o antigo, evitando o erro 1553.

-- 1. Cria a nova chave única incluindo a obra
ALTER TABLE weekly_material_requests
    ADD UNIQUE KEY uk_manager_week_site (manager_id, week_start, construction_site_id);

-- 2. Agora é seguro remover a chave única antiga
ALTER TABLE weekly_material_requests
    DROP INDEX uk_manager_week;
