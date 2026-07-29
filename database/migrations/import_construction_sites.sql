-- Migration: Importação de obras do sistema externo
-- Data: 2026-07-29
-- Descrição: Importa projetos da planilha de obras para a tabela construction_sites
-- IMPORTANTE: Já existem obras cadastradas (OBR-000001 a OBR-000010). Novos registros começam em OBR-000011.
-- Obras que já existem no sistema são ignoradas (INSERT IGNORE para evitar duplicatas por nome).

-- Importação das obras
-- Status mapeados: "Finalizado" = completed, "Em andamento" = active, "Nao iniciado" = active, "Atrasado" = active

INSERT IGNORE INTO construction_sites (name, code, status, started_at, expected_end_at, completed_at, created_at) VALUES
('Fernanda Calvo - Sadami e Cris', 'OBR-000011', 'completed', '2025-02-05', '2026-01-29', '2026-01-29', '2025-12-23 00:00:00'),
('Norah Arquitetura- Gustavo Penna', 'OBR-000012', 'completed', '2025-07-20', '2026-05-28', '2026-05-28', '2025-12-23 00:00:00'),
('Mari Coser- Lucas Cataldi', 'OBR-000013', 'active', '2025-06-03', '2026-03-26', NULL, '2025-12-29 00:00:00'),
('Studio Delar - Luciana Bergamini', 'OBR-000014', 'active', '2025-04-07', '2026-03-12', NULL, '2025-12-29 00:00:00'),
('Norah Arquitetura- Selma', 'OBR-000015', 'completed', '2025-06-04', '2026-04-29', '2026-04-29', '2025-12-29 00:00:00'),
('Brooks Construtora Ltda', 'OBR-000016', 'active', '2015-12-31', '2016-01-31', NULL, '2026-01-05 00:00:00'),
('Mari Coser - Sonia e Ademar', 'OBR-000017', 'active', '2025-07-20', '2026-09-29', NULL, '2026-01-05 00:00:00'),
('Brooks - Fasano', 'OBR-000018', 'active', '2024-12-04', '2026-09-29', NULL, '2026-01-05 00:00:00'),
('Videjex - João Batista e Debora', 'OBR-000019', 'completed', '2025-05-19', '2026-01-30', '2026-01-30', '2026-01-05 00:00:00'),
('Pastor e Isabel', 'OBR-000020', 'active', '2024-12-02', '2026-06-29', NULL, '2026-01-05 00:00:00'),
('Fernanda Moreira - Lucia Luppi Apartamento 32', 'OBR-000021', 'completed', '2026-01-04', '2026-05-05', '2026-05-05', '2026-01-05 00:00:00'),
('Brooks - George', 'OBR-000022', 'active', '2025-04-19', '2026-01-30', NULL, '2026-01-05 00:00:00'),
('P010 - Joia Bergamo - Fabio e Silvia - Apartamento 214', 'OBR-000023', 'active', '2025-11-03', '2026-08-30', NULL, '2026-01-07 00:00:00'),
('P011 - LPAA - Marco Mello', 'OBR-000024', 'active', '2026-02-03', '2027-02-01', NULL, '2026-02-02 00:00:00'),
('Fernanda Moreira - Lucia Luppi Apartamento 31', 'OBR-000025', 'active', '2026-02-04', NULL, NULL, '2026-02-05 00:00:00'),
('P012 - Luciana Zeitel - Renato de Faria Aguiar', 'OBR-000026', 'active', '2026-03-22', '2026-10-01', NULL, '2026-02-16 00:00:00'),
('P013 ID Móveis', 'OBR-000027', 'active', '2026-02-11', '2026-12-20', NULL, '2026-02-23 00:00:00'),
('P014 - Marilia - Miguel Esteban', 'OBR-000028', 'completed', '2026-03-22', '2026-04-09', '2026-04-09', '2026-03-18 00:00:00'),
('P015 - Helio Akitoshi', 'OBR-000029', 'completed', '2026-03-22', '2026-04-09', '2026-04-09', '2026-03-19 00:00:00'),
('P016 - Alberto Terrivel', 'OBR-000030', 'completed', '2025-12-31', '2026-06-25', '2026-06-25', '2026-04-05 00:00:00'),
('P017 - Juliana "Renan"', 'OBR-000031', 'active', '2026-04-04', '2026-06-29', NULL, '2026-04-05 00:00:00'),
('P018 - Estela', 'OBR-000032', 'active', '2026-01-31', '2026-09-29', NULL, '2026-04-06 00:00:00'),
('P019 - Dayanne Cavalcanti - Valentina', 'OBR-000033', 'active', '2026-02-28', '2026-05-30', NULL, '2026-04-06 00:00:00'),
('P020 - Dayanne Cavalcanti - Daniella', 'OBR-000034', 'completed', '2026-04-06', '2026-04-06', '2026-04-06', '2026-04-06 00:00:00'),
('P021 - Mari Coser - Kleber', 'OBR-000035', 'completed', '2026-03-22', '2026-04-22', '2026-04-22', '2026-04-09 00:00:00'),
('Programa Dophi', 'OBR-000036', 'active', '2026-04-16', '2026-12-30', NULL, '2026-04-17 00:00:00'),
('P022 - Alisson e Lidiane', 'OBR-000037', 'completed', '2026-04-14', '2026-04-16', '2026-04-16', '2026-04-17 00:00:00'),
('Assistência Vivian', 'OBR-000038', 'completed', '2026-04-21', '2026-04-21', '2026-04-21', '2026-04-22 00:00:00'),
('P024 - Fernanda Borques - Alphaville', 'OBR-000039', 'completed', '2026-04-27', '2026-05-11', '2026-05-11', '2026-04-23 00:00:00'),
('P025 - Videjex - João Batista e Debora', 'OBR-000040', 'active', '2026-05-10', '2026-05-10', NULL, '2026-05-06 00:00:00'),
('P026 - Obra Jóia Bergamo', 'OBR-000041', 'completed', '2026-05-10', '2026-07-23', '2026-07-23', '2026-05-11 00:00:00'),
('P028 - Mari Coser - Brunno Bagnariolli', 'OBR-000042', 'completed', '2026-05-24', '2026-06-10', '2026-06-10', '2026-05-25 00:00:00'),
('Ademar e Carla', 'OBR-000043', 'active', '2026-06-14', '2026-07-02', NULL, '2026-06-15 00:00:00'),
('P030 - Mari Coser - Vanessa Antunes', 'OBR-000044', 'active', '2026-06-25', NULL, NULL, '2026-06-26 00:00:00'),
('P 0006 - Lucas Narciso', 'OBR-000045', 'active', '2026-06-28', '2042-01-28', NULL, '2026-06-29 00:00:00'),
('P031 - Joia Bergamo - João Doria Residencia', 'OBR-000046', 'active', '2026-07-07', '2026-09-08', NULL, '2026-07-01 00:00:00'),
('P032 - Mariana Coser e Douglas', 'OBR-000047', 'active', '2026-07-19', '2027-02-25', NULL, '2026-07-01 00:00:00'),
('P 0007 - Cláudia', 'OBR-000048', 'active', '2027-01-11', '2027-09-28', NULL, '2026-07-02 00:00:00'),
('P033 - Mariana Maran - Vanessa e Caique', 'OBR-000049', 'active', '2026-07-07', '2028-03-30', NULL, '2026-07-08 00:00:00'),
('P034 - Hélio Akitoshi', 'OBR-000050', 'active', '2026-07-10', NULL, NULL, '2026-07-10 00:00:00'),
('A001 - Milena Niemeyer - Henrique Bredda', 'OBR-000051', 'completed', '2026-07-12', '2026-07-13', '2026-07-13', '2026-07-13 00:00:00'),
('Ana Lucia - Jd Europa', 'OBR-000052', 'active', '2026-07-13', '2027-06-13', NULL, '2026-07-14 00:00:00'),
('P035 - Mari Coser - Emerson One Navegações', 'OBR-000053', 'active', '2026-07-14', NULL, NULL, '2026-07-15 00:00:00'),
('A002 - Norah - Selma', 'OBR-000054', 'completed', '2026-07-16', '2026-07-26', '2026-07-26', '2026-07-17 00:00:00'),
('Orçamento Lareira Doria', 'OBR-000055', 'active', '2026-07-19', '2026-09-19', NULL, '2026-07-20 00:00:00'),
('ORÇAMENTO OKA- PATRICIA FROTA', 'OBR-000056', 'active', '2026-08-10', '2027-03-10', NULL, '2026-07-20 00:00:00'),
('A003 - Norah - Fabio Marcolini', 'OBR-000057', 'active', '2026-08-16', NULL, NULL, '2026-07-21 00:00:00'),
('A004 - Mari Coser - Germano', 'OBR-000058', 'active', '2026-07-22', '2026-08-06', NULL, '2026-07-23 00:00:00');
