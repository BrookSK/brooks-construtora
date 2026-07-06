-- Seed: Catálogo inicial de EPIs organizados por categoria (sem duplicatas)
-- Data: 2026-07-06
-- min_replacement_days = 0 por padrão (ajustável na tela de cadastro)

INSERT INTO epis (name, category, ca, min_replacement_days, active, created_by, created_at) VALUES
-- Proteção da Cabeça
('Capacete de segurança classe A', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),
('Capacete de segurança classe B (isolamento elétrico)', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),
('Capacete para eletricistas', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),
('Capacete para trabalho em altura (jugular 3 ou 4 pontos)', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),
('Jugular para capacete', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),
('Protetor de nuca para capacete (contra sol)', 'Proteção da Cabeça', NULL, 0, 1, 'Seed', NOW()),

-- Proteção dos Olhos
('Óculos de segurança incolor', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos fumê', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos amarelo (baixa luminosidade)', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos contra impacto', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos ampla visão', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos antiembaçante', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos para produtos químicos', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),
('Óculos com proteção UV', 'Proteção dos Olhos', NULL, 0, 1, 'Seed', NOW()),

-- Proteção Facial
('Protetor facial (Face Shield)', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),
('Viseira de policarbonato', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),
('Máscara de solda manual', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),
('Máscara de solda automática', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),
('Escudo facial para esmerilhamento', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),
('Escudo facial para produtos químicos', 'Proteção Facial', NULL, 0, 1, 'Seed', NOW()),

-- Proteção Auditiva
('Protetor auricular tipo plug silicone', 'Proteção Auditiva', NULL, 0, 1, 'Seed', NOW()),
('Protetor auricular descartável espuma', 'Proteção Auditiva', NULL, 0, 1, 'Seed', NOW()),
('Protetor auricular reutilizável', 'Proteção Auditiva', NULL, 0, 1, 'Seed', NOW()),
('Abafador tipo concha', 'Proteção Auditiva', NULL, 0, 1, 'Seed', NOW()),
('Abafador acoplado ao capacete', 'Proteção Auditiva', NULL, 0, 1, 'Seed', NOW()),

-- Proteção Respiratória
('Máscara descartável PFF1', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Máscara descartável PFF2', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Máscara descartável PFF2 com válvula', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Máscara descartável PFF3', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Máscara cirúrgica', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Respirador semifacial', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Respirador facial inteiro', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro para poeira', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro para névoa', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro para fumos metálicos', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro para vapores orgânicos', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro para gases ácidos', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Filtro combinado', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Cartucho químico', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),
('Respirador químico', 'Proteção Respiratória', NULL, 0, 1, 'Seed', NOW()),

-- Proteção das Mãos
('Luva de couro vaqueta', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de couro raspa', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva mista couro/lona', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de borracha látex', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de borracha nitrílica', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de borracha neoprene', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de borracha PVC', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva de borracha butílica', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível A', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível B', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível C', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível D', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível E', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva anticorte nível F', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 00', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 0', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 1', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 2', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 3', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para eletricista classe 4', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva isolante', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva térmica', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva aluminizada', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para soldador', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para alta temperatura', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva para criogenia', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva impermeável', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Luva descartável', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Sobreluvas de couro', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),
('Manga isolante', 'Proteção das Mãos', NULL, 0, 1, 'Seed', NOW()),

-- Proteção dos Braços
('Mangote de raspa', 'Proteção dos Braços', NULL, 0, 1, 'Seed', NOW()),
('Mangote anticorte', 'Proteção dos Braços', NULL, 0, 1, 'Seed', NOW()),
('Mangote UV', 'Proteção dos Braços', NULL, 0, 1, 'Seed', NOW()),
('Mangote para solda', 'Proteção dos Braços', NULL, 0, 1, 'Seed', NOW()),
('Mangote térmico', 'Proteção dos Braços', NULL, 0, 1, 'Seed', NOW()),

-- Proteção do Tronco
('Avental de raspa', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Avental PVC', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Avental impermeável', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Avental aluminizado', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Colete refletivo', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Colete alta visibilidade', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Jaleco de proteção', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Jaqueta retardante a chamas', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Jaqueta impermeável', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Jaqueta de raspa', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),
('Capa de chuva', 'Proteção do Tronco', NULL, 0, 1, 'Seed', NOW()),

-- Proteção das Pernas
('Perneira de raspa', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),
('Perneira PVC', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),
('Perneira anticorte', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),
('Perneira para motosserra', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),
('Calça anti-chama', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),
('Calça impermeável', 'Proteção das Pernas', NULL, 0, 1, 'Seed', NOW()),

-- Proteção dos Pés
('Botina com biqueira de aço', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Botina com biqueira de composite', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Botina sem biqueira', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Botina impermeável', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Botina eletricista', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Botina antiderrapante', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota PVC', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota borracha', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota cano longo', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota para produtos químicos', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota para concreto', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Bota dielétrica', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Polaina', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Protetor de metatarso', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Palmilha anti perfuração', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),
('Cobre-botas descartável', 'Proteção dos Pés', NULL, 0, 1, 'Seed', NOW()),

-- Trabalho em Altura (NR-35)
('Cinturão paraquedista', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Cinturão abdominal', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Arnês', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Talabarte simples', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Talabarte duplo em Y', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Talabarte com absorvedor de energia', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Trava-quedas retrátil', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Trava-quedas deslizante', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Linha de vida móvel', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Linha de vida fixa', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Conector', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Mosquetão', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Gancho dupla trava', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Fita de ancoragem', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Corda de segurança', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Capacete específico para altura', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),
('Porta-ferramentas para cinturão', 'Trabalho em Altura (NR-35)', NULL, 0, 1, 'Seed', NOW()),

-- Proteção para Soldagem
('Capuz para solda', 'Proteção para Soldagem', NULL, 0, 1, 'Seed', NOW()),

-- Proteção para Eletricistas (NR-10)
('Vestimenta anti-chama', 'Proteção para Eletricistas (NR-10)', NULL, 0, 1, 'Seed', NOW()),
('Vestimenta ATPV', 'Proteção para Eletricistas (NR-10)', NULL, 0, 1, 'Seed', NOW()),
('Protetor facial contra arco elétrico', 'Proteção para Eletricistas (NR-10)', NULL, 0, 1, 'Seed', NOW()),
('Balaclava anti-chama', 'Proteção para Eletricistas (NR-10)', NULL, 0, 1, 'Seed', NOW()),

-- Espaço Confinado (NR-33)
('Respirador autônomo (SCBA)', 'Espaço Confinado (NR-33)', NULL, 0, 1, 'Seed', NOW()),
('Respirador de linha de ar', 'Espaço Confinado (NR-33)', NULL, 0, 1, 'Seed', NOW()),
('Detector portátil de gases', 'Espaço Confinado (NR-33)', NULL, 0, 1, 'Seed', NOW()),
('Lanterna intrinsecamente segura', 'Espaço Confinado (NR-33)', NULL, 0, 1, 'Seed', NOW()),

-- Vestimentas Especiais
('Macacão Tyvek', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Macacão descartável', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Macacão impermeável', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Macacão antiestático', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Macacão retardante à chama', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Macacão aluminizado', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Balaclava', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Touca árabe', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),
('Capuz impermeável', 'Vestimentas Especiais', NULL, 0, 1, 'Seed', NOW()),

-- Proteção Contra Intempéries
('Protetor solar', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),
('Chapéu árabe', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),
('Boné com proteção de nuca', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),
('Conjunto impermeável', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),
('Roupa térmica', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),
('Jaqueta corta-vento', 'Proteção Contra Intempéries', NULL, 0, 1, 'Seed', NOW()),

-- Acessórios Complementares
('Porta-ferramentas', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Cordão para ferramentas', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Faixa refletiva', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Joelheira', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Cotoveleira', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Cinto ergonômico (apoio lombar)', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW()),
('Bolsa porta-EPI', 'Acessórios Complementares', NULL, 0, 1, 'Seed', NOW());
