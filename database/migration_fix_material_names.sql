-- =====================================================
-- FIX: Corrigir nomes dos materiais para primeira letra maiúscula
-- =====================================================

UPDATE materials SET name = CONCAT(UCASE(LEFT(name, 1)), SUBSTRING(name, 2)) WHERE id > 0;

-- Correções específicas para nomes compostos que ficam melhor capitalizados
UPDATE materials SET name = 'Joelho - Esgoto' WHERE name = 'Joelho - esgoto';
UPDATE materials SET name = 'Derivação Y - Esgoto' WHERE name = 'Derivação y - esgoto';
UPDATE materials SET name = 'Redução - Esgoto' WHERE name = 'Redução - esgoto';
UPDATE materials SET name = 'Joelho com Anel - Esgoto' WHERE name = 'Joelho com anel - esgoto';
UPDATE materials SET name = 'Cano - Esgoto' WHERE name = 'Cano - esgoto';
UPDATE materials SET name = 'Caixa Sifonada' WHERE name = 'Caixa sifonada';
UPDATE materials SET name = 'Cap com Anel - Esgoto' WHERE name = 'Cap com anel - esgoto';
UPDATE materials SET name = 'Tubo - Esgoto' WHERE name = 'Tubo - esgoto';
UPDATE materials SET name = 'Te - Esgoto' WHERE name = 'Te - esgoto';
UPDATE materials SET name = 'Kit Fossa Séptica 1500L/dia e Leito de Secagem' WHERE name = 'Kit fossa septica 1500L/dia e leito de secagem';
UPDATE materials SET name = 'Mangueira Ar/Água Preta Pol 300lbs' WHERE name = 'Mangueira ar/agua preta pol 300lbs';
UPDATE materials SET name = 'Te Interno Triplo - Mangueira Preta' WHERE name = 'Te interno triplo - mangueira preta';
UPDATE materials SET name = 'Adaptador Interno - Mangueira Preta' WHERE name = 'Adaptador interno - mangueira preta';
UPDATE materials SET name = 'Flange - Soldável' WHERE name = 'Flange - soldavel';
UPDATE materials SET name = 'Te - Soldável' WHERE name = 'Te - soldavel';
UPDATE materials SET name = 'Luva Azul' WHERE name = 'Luva azul';
UPDATE materials SET name = 'Cano - Soldável' WHERE name = 'Cano - soldavel';
UPDATE materials SET name = 'Joelho - Soldável' WHERE name = 'Joelho - soldavel';
UPDATE materials SET name = 'Luva - Soldável' WHERE name = 'Luva - soldavel';
UPDATE materials SET name = 'Cola Cano com Pincel' WHERE name = 'Cola cano com pincel';
UPDATE materials SET name = 'Lixa D\'Água' WHERE name = 'Lixa d\'água';
UPDATE materials SET name = 'Caixa D\'Água' WHERE name = 'Caixa d\'água';
UPDATE materials SET name = 'Abraçadeira Parafuso Inox' WHERE name = 'Abraçadeira parafuso inox';
UPDATE materials SET name = 'Boia p/ Caixa D\'Água DECA' WHERE name = 'Boia p caixa d\'agua DECA';
UPDATE materials SET name = 'Tambor Plástico Azul' WHERE name = 'Tambor plástico azul';
UPDATE materials SET name = 'Prancha Cedrinho - 4mts' WHERE name = 'Prancha cedrinho - 4mts';
UPDATE materials SET name = 'Tábua Cedrinho - 2mts' WHERE name = 'Tábua cedrinho - 2mts';
UPDATE materials SET name = 'Escada Extensiva 19 Degraus Tipo D e Fibra' WHERE name = 'Escada extensiva 19 degraus tipo D e Fibra';
UPDATE materials SET name = 'Saco de Lixo - Pacote' WHERE name = 'Saco de lixo - pacote';
UPDATE materials SET name = 'Bebedouro Purificador' WHERE name = 'Bebedouro purificador';
