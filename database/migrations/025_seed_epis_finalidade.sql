-- Seed: EPIs adicionais (lista com finalidade). Insere apenas se ainda não existir (por nome).
-- Data: 2026-07-06
-- A finalidade é registrada na coluna `category` para referência/agrupamento.

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Capacete de Segurança com jugular', 'Proteção contra impactos e queda de objetos', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Capacete de Segurança com jugular');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Óculos de Segurança incolor e escuro', 'Proteção dos olhos', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Óculos de Segurança incolor e escuro');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Protetor Facial incolor', 'Proteção da face', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Protetor Facial incolor');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Protetor Auricular Tipo Plug e concha', 'Proteção contra ruído', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Protetor Auricular Tipo Plug e concha');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Respirador PFF2', 'Proteção contra poeiras', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Respirador PFF2');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Luvas de Raspa', 'Proteção contra abrasão e calor', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Luvas de Raspa');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Luvas de Vaqueta', 'Proteção mecânica', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Luvas de Vaqueta');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Luvas Nitrílicas', 'Proteção química', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Luvas Nitrílicas');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Luvas de Látex (Mucambo)', 'Proteção leve', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Luvas de Látex (Mucambo)');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Botina de Segurança com biqueira', 'Proteção dos pés', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Botina de Segurança com biqueira');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Botina com biqueira de composite', 'Proteção específica', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Botina com biqueira de composite');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Perneira de Raspa', 'Proteção das pernas', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Perneira de Raspa');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Avental de Raspa', 'Proteção do tronco', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Avental de Raspa');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Cinto Tipo Paraquedista', 'Proteção contra quedas', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Cinto Tipo Paraquedista');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Talabarte em Y', 'Retenção de quedas', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Talabarte em Y');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Trava-quedas', 'Deslocamento seguro', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Trava-quedas');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Capa de Chuva', 'Proteção contra intempéries', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Capa de Chuva');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Colete Refletivo', 'Visibilidade', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Colete Refletivo');

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at)
SELECT 'Protetor Solar 5L', 'Proteção UV', NULL, 0, 1, 'Seed', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM epis WHERE name = 'Protetor Solar 5L');
