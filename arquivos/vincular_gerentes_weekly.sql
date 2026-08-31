-- Vincula gerentes à fase 'weekly' das obras (lista semanal)
-- Gerado automaticamente a partir de obras_colaboradores.xlsx
-- Idempotente: só insere se ainda não existe o vínculo weekly

-- OBR-000001: P027 - OBRA - Casa Da Montanha → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000001' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000002: P008 - Joia Bergamo - Rodrigo e Talita → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000002' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000002' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000003: P004 - LPAA - Andre e Angela → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000003' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000003' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000004: P001 - Monalisa Cauli - Marcelo Rica → Eduardo Carvalho + Eduardo Andrade
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000004' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000004' AND pu.pin = '9518'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000005: P002 - Mari Coser - Roberto Lippi → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000005' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000006: P003 - Mari Coser - Saulo Borborema → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000006' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000007: P007 - Joia Bergamo - Antonio e Andrea → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000007' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000007' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000008: P009 - Mariana Coser - Vanduir e Kassia → Eduardo Carvalho + Gleice Aline Bernardi
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000008' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000008' AND pu.pin = '1501'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000009: P006 - Obra ArchDuo - Thauani → Eduardo Carvalho + Eduardo Andrade
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000009' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000009' AND pu.pin = '9518'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000010: P010 - Joia Bergamo - Fabio e Silvia - Estúdios → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000010' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000010' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000011: Fernanda Calvo - Sadami e Cris → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000011' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000012: Norah Arquitetura- Gustavo Penna → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000012' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000013: Mari Coser- Lucas Cataldi → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000013' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000013' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000014: Studio Delar - Luciana Bergamini → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000014' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000014' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000015: Norah Arquitetura- Selma → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000015' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000015' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000017: Mari Coser - Sonia e Ademar → Eduardo Carvalho + Gleice Aline Bernardi
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000017' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000017' AND pu.pin = '1501'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000018: Brooks - Fasano → Eduardo Carvalho + Gleice Aline Bernardi
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000018' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000018' AND pu.pin = '1501'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000019: Videjex - João Batista e Debora → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000019' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000020: Pastor e Isabel → Eduardo Carvalho + Eduardo Andrade + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000020' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000020' AND pu.pin = '9518'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000020' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000021: Fernanda Moreira - Lucia Luppi Apartamento 32 → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000021' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000022: Brooks - George → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000022' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000023: P010 - Joia Bergamo - Fabio e Silvia - Apartamento 214 → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000023' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000023' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000024: P011 - LPAA - Marco Mello → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000024' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000024' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000025: Fernanda Moreira - Lucia Luppi Apartamento 31 → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000025' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000026: P012 - Luciana Zeitel - Renato de Faria Aguiar → Eduardo Carvalho + Eduardo Andrade
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000026' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000026' AND pu.pin = '9518'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000027: P013 ID Móveis → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000027' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000027' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000028: P014 - Marilia - Miguel Esteban → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000028' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000029: P015 - Helio Akitoshi → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000029' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000030: P016 - Alberto Terrivel → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000030' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000031: P017 - Juliana "Renan" → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000031' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000031' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000032: P018 - Estela → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000032' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000032' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000033: P019 - Dayanne Cavalcanti - Valentina → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000033' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000033' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000034: P020 - Dayanne Cavalcanti - Daniella → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000034' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000035: P021 - Mari Coser - Kleber → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000035' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000037: P022 - Alisson e Lidiane → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000037' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000038: Assistência Vivian → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000038' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000039: P024 - Fernanda Borques - Alphaville → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000039' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000040: P025 - Videjex - João Batista e Debora → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000040' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000041: P026 - Obra Jóia Bergamo → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000041' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000041' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000042: P028 - Mari Coser - Brunno Bagnariolli → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000042' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000042' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000044: P030 - Mari Coser - Vanessa Antunes → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000044' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000044' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000046: P031 - Joia Bergamo - João Doria Residencia → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000046' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000046' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000047: P032 - Mariana Coser e Douglas → Eduardo Carvalho + Gleice Aline Bernardi
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000047' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000047' AND pu.pin = '1501'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000048: P 0007 - Cláudia → Eduardo Carvalho
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000048' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000049: P033 - Mariana Maran - Vanessa e Caique → Eduardo Carvalho + Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000049' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000049' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000050: P034 - Hélio Akitoshi → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000050' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000050' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000051: A001 - Milena Niemeyer - Henrique Bredda → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000051' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000051' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000053: P035 - Mari Coser - Emerson One Navegações → Eduardo Carvalho + Eduardo Andrade
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000053' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000053' AND pu.pin = '9518'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000054: A002 - Norah - Selma → Eduardo Carvalho + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000054' AND pu.pin = '1013'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000054' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000055: Orçamento Lareira Doria → Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000055' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000057: A003 - Norah - Fabio Marcolini → JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000057' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000058: A004 - Mari Coser - Germano → Gleice Aline Bernardi + JEFFERSON DUARTE DO NASCIMENTO
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000058' AND pu.pin = '1501'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000058' AND pu.pin = '2505'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

-- OBR-000061: P039 - Tabata - Kenny → Rodrigo Bastos
INSERT INTO construction_site_approvers (construction_site_id, pin_user_id, phase, created_at)
SELECT cs.id, pu.id, 'weekly', NOW() FROM construction_sites cs, pin_users pu
WHERE cs.code = 'OBR-000061' AND pu.pin = '2301'
  AND NOT EXISTS (SELECT 1 FROM construction_site_approvers x WHERE x.construction_site_id = cs.id AND x.pin_user_id = pu.id AND x.phase = 'weekly');

