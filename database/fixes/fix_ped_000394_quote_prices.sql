-- =====================================================================
-- Correção manual dos preços de cotação do pedido PED-000394
-- Ajusta purchase_order_item_prices com os valores lidos dos PDFs dos
-- 3 fornecedores (CASA MIMOSA, NICOM, CASA TOGNINI).
--
-- COMO FUNCIONA:
--  - Resolve o order_id pelo código do pedido.
--  - Resolve o supplier_id pelo nome do fornecedor (ajuste os LIKE se
--    o nome cadastrado for diferente).
--  - Faz match do item pelo material_name EXATO do pedido.
--  - Regrava unit_price e recalcula total_price = unit_price * quantidade.
--
-- IMPORTANTE:
--  - Rode dentro de uma transação e confira o SELECT de verificação no
--    final antes de dar COMMIT.
--  - Os valores UNITÁRIOS abaixo vieram dos PDFs. O total é recalculado.
--  - Este script assume que os preços JÁ foram salvos (existe linha em
--    purchase_order_item_prices). Se ainda não foram salvos, use a
--    seção "INSERT alternativo" comentada no fim.
-- =====================================================================

START TRANSACTION;

-- ── Resolver IDs base ────────────────────────────────────────────────
SET @order_id := (SELECT id FROM purchase_orders WHERE code = 'PED-000394' LIMIT 1);

SET @mimosa  := (SELECT id FROM suppliers WHERE name LIKE '%MIMOSA%'  LIMIT 1);
SET @nicom   := (SELECT id FROM suppliers WHERE name LIKE '%NICOM%'   LIMIT 1);
SET @tognini := (SELECT id FROM suppliers WHERE name LIKE '%TOGNINI%' LIMIT 1);

-- =====================================================================
-- Procedure inline: atualiza um item/fornecedor pelo nome do material.
-- Como MySQL não tem função anônima, usamos UPDATE ... JOIN repetidos.
-- Padrão de cada bloco:
--   UPDATE purchase_order_item_prices p
--   JOIN purchase_order_items i ON i.id = p.item_id
--   SET p.unit_price = <UNIT>,
--       p.total_price = ROUND(<UNIT> * i.quantity, 2)
--   WHERE p.order_id = @order_id AND p.supplier_id = @sup
--     AND i.material_name = '<NOME EXATO>';
-- =====================================================================

-- =====================================================================
-- CASA MIMOSA  (unitários do PDF 3765431)
-- =====================================================================
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=5.32,  p.total_price=ROUND(5.32*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='mangueira pex de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.09,  p.total_price=ROUND(7.09*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='mangueira pex de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=26.07, p.total_price=ROUND(26.07*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='união de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=19.26, p.total_price=ROUND(19.26*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='união de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=22.69, p.total_price=ROUND(22.69*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='união de 20 mm x 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=46.45, p.total_price=ROUND(46.45*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='T pex de 20 mm com rosca macho de 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.29, p.total_price=ROUND(24.29*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos pex de 16 mm com rosca macho de 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.94,  p.total_price=ROUND(2.94*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='reduções de 3/4 pra 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.29, p.total_price=ROUND(24.29*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelo pex de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=35.36, p.total_price=ROUND(35.36*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelo pex de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.79, p.total_price=ROUND(24.79*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelo pex 16 mm com rosca fêmea de ½ com fixação para parafuso';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=12.42, p.total_price=ROUND(12.42*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo esgoto de 50';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=3.25,  p.total_price=ROUND(3.25*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=3.31,  p.total_price=ROUND(3.31*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos 45 graus de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=3.04,  p.total_price=ROUND(3.04*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='luvas de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.94,  p.total_price=ROUND(2.94*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='reduções de 50 esgoto pra 40';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.97,  p.total_price=ROUND(0.97*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='anel de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.63,  p.total_price=ROUND(7.63*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.12,  p.total_price=ROUND(2.12*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.12,  p.total_price=ROUND(2.12*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos 45 graus de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.51,  p.total_price=ROUND(1.51*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='luvas de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=6.32,  p.total_price=ROUND(6.32*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos de 40 esgoto com anel';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=4.65,  p.total_price=ROUND(4.65*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='parafusos com bucha 6 cabeças-chatas chatas para fita perfurada';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=39.45, p.total_price=ROUND(39.45*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='rolo de fita perfurada';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=30.16, p.total_price=ROUND(30.16*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo 3/4 marrom (4 barras)';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.61,  p.total_price=ROUND(1.61*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='T 3/4 marrom';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.92,  p.total_price=ROUND(1.92*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos 45 graus marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.87,  p.total_price=ROUND(0.87*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelo marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.87,  p.total_price=ROUND(0.87*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='luvas marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=5.14,  p.total_price=ROUND(5.14*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='isolamento térmico para os drenos de 28 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=41.53, p.total_price=ROUND(41.53*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo cpvc 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=19.82, p.total_price=ROUND(19.82*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='adaptadores cpvc com rosca macho de 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=11.33, p.total_price=ROUND(11.33*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='luvas com rosca fêmea 1/2 cpvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=17.89, p.total_price=ROUND(17.89*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo de cola pvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=46.52, p.total_price=ROUND(46.52*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo de cola cpvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=20.55, p.total_price=ROUND(20.55*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='tubo esgoto de 100';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=6.07,  p.total_price=ROUND(6.07*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='luvas de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.16,  p.total_price=ROUND(2.16*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='anéis de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=10.39, p.total_price=ROUND(10.39*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=12.09, p.total_price=ROUND(12.09*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='mangueira pex pra gás de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=27.89, p.total_price=ROUND(27.89*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos de 16 mm pra gás com rosca fêmea 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=36.35, p.total_price=ROUND(36.35*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='T pex pra gás de 16 mm';
-- Emenda gás qtd 1 (mangueira: "emendas de 16 mm pex pra gás") = UNIAO P/GAS 16x16 (15,58)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=15.58, p.total_price=ROUND(15.58*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=1;
-- Emenda gás qtd 2 = COT P/GAS 16x16 (22,21)  [ATENCAO: valor do PDF Mimosa mais próximo]
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=22.21, p.total_price=ROUND(22.21*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=2;
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=22.21, p.total_price=ROUND(22.21*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@mimosa AND i.material_name='cotovelos pex 16 mm pra gás.';

-- =====================================================================
-- NICOM  (unitários derivados do PDF orçamento 5553311; total/qtd)
-- =====================================================================
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.49,  p.total_price=ROUND(7.49*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='mangueira pex de 16 mm';   -- 149,80/20
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=8.90,  p.total_price=ROUND(8.90*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='mangueira pex de 20 mm';   -- 89,00/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=18.90, p.total_price=ROUND(18.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='união de 20 mm';           -- 189,00/10 (520489 UNIAO 20MM)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=13.90, p.total_price=ROUND(13.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='união de 16 mm';           -- 139,00/10 (520470 UNIAO 16MM)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=31.90, p.total_price=ROUND(31.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='união de 20 mm x 16 mm';    -- 319,00/10 (525685)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=59.90, p.total_price=ROUND(59.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='T pex de 20 mm com rosca macho de 3/4'; -- 479,20/8
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.90, p.total_price=ROUND(24.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos pex de 16 mm com rosca macho de 1/2'; -- 199,20/8
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=31.90, p.total_price=ROUND(31.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='reduções de 3/4 pra 1/2';  -- 255,20/8 (2a linha 525685 red 20x16)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=23.90, p.total_price=ROUND(23.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelo pex de 16 mm'; -- 239,00/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=26.90, p.total_price=ROUND(26.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelo pex de 20 mm'; -- 269,00/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=34.90, p.total_price=ROUND(34.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelo pex 16 mm com rosca fêmea de ½ com fixação para parafuso'; -- 523,50/15
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=26.63, p.total_price=ROUND(26.63*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo esgoto de 50';    -- 79,90/3 (barra 6m)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=4.50,  p.total_price=ROUND(4.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos de 50 esgoto'; -- 27,00/6
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=4.50,  p.total_price=ROUND(4.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos 45 graus de 50 esgoto'; -- 27,00/6
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=3.50,  p.total_price=ROUND(3.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='luvas de 50 esgoto'; -- 28,00/8
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=3.00,  p.total_price=ROUND(3.00*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='reduções de 50 esgoto pra 40'; -- 6,00/2
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.49,  p.total_price=ROUND(1.49*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='anel de 50 esgoto'; -- 14,90/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.97,  p.total_price=ROUND(7.97*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo 40 esgoto'; -- 23,90/3
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.50,  p.total_price=ROUND(2.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos 45 graus de 40 esgoto'; -- 15,00/6
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.00,  p.total_price=ROUND(2.00*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='luvas de 40 esgoto'; -- 8,00/4
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.50,  p.total_price=ROUND(7.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos de 40 esgoto com anel'; -- 30,00/4
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=59.90, p.total_price=ROUND(59.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='parafusos com bucha 6 cabeças-chatas chatas para fita perfurada'; -- 59,90 caixa 100un
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=6.65,  p.total_price=ROUND(6.65*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo 3/4 marrom (4 barras)'; -- 159,60/24 (4 barras)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.99,  p.total_price=ROUND(1.99*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='T 3/4 marrom'; -- 11,94/6
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.50,  p.total_price=ROUND(2.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos 45 graus marrom 3/4'; -- 20,00/8
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.90,  p.total_price=ROUND(0.90*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelo marrom 3/4'; -- 9,00/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.10,  p.total_price=ROUND(1.10*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='luvas marrom 3/4'; -- 6,60/6
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=39.90, p.total_price=ROUND(39.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo cpvc 3/4'; -- 39,90/1
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=19.90, p.total_price=ROUND(19.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='adaptadores cpvc com rosca macho de 1/2'; -- 59,70/3
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=11.90, p.total_price=ROUND(11.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='luvas com rosca fêmea 1/2 cpvc'; -- 35,70/3
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=22.90, p.total_price=ROUND(22.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo de cola pvc'; -- 22,90/1 (adesivo 175g)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=38.90, p.total_price=ROUND(38.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo de cola cpvc'; -- 38,90/1
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=34.95, p.total_price=ROUND(34.95*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='tubo esgoto de 100'; -- 69,90/2 (barra 3m)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.50,  p.total_price=ROUND(7.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='luvas de 100 esgoto'; -- 30,00/4
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.00,  p.total_price=ROUND(2.00*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='anéis de 100 esgoto'; -- 8,00/4
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=12.90, p.total_price=ROUND(12.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos de 100 esgoto'; -- 51,60/4
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=17.90, p.total_price=ROUND(17.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='mangueira pex pra gás de 16 mm'; -- 179,00/10
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=49.90, p.total_price=ROUND(49.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos de 16 mm pra gás com rosca fêmea 1/2'; -- 149,70/3
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=74.90, p.total_price=ROUND(74.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='T pex pra gás de 16 mm'; -- 224,70/3
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=52.90, p.total_price=ROUND(52.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=1; -- 105,80/2 (uniao gas)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=52.90, p.total_price=ROUND(52.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=2; -- 105,80/2
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=54.90, p.total_price=ROUND(54.90*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@nicom AND i.material_name='cotovelos pex 16 mm pra gás.'; -- 164,70/3

-- =====================================================================
-- CASA TOGNINI  (unitários do PDF orçamento 289989)
-- =====================================================================
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=7.25,  p.total_price=ROUND(7.25*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='mangueira pex de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=9.50,  p.total_price=ROUND(9.50*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='mangueira pex de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=14.80, p.total_price=ROUND(14.80*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='união de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=10.20, p.total_price=ROUND(10.20*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='união de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=12.51, p.total_price=ROUND(12.51*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='união de 20 mm x 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=31.57, p.total_price=ROUND(31.57*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='T pex de 20 mm com rosca macho de 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=17.37, p.total_price=ROUND(17.37*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos pex de 16 mm com rosca macho de 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=9.65,  p.total_price=ROUND(9.65*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='reduções de 3/4 pra 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=19.00, p.total_price=ROUND(19.00*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelo pex de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=20.04, p.total_price=ROUND(20.04*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelo pex de 20 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.02, p.total_price=ROUND(24.02*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelo pex 16 mm com rosca fêmea de ½ com fixação para parafuso';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=14.88, p.total_price=ROUND(14.88*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo esgoto de 50';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.82,  p.total_price=ROUND(2.82*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.87,  p.total_price=ROUND(2.87*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos 45 graus de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.62,  p.total_price=ROUND(2.62*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='luvas de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=2.54,  p.total_price=ROUND(2.54*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='reduções de 50 esgoto pra 40';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.78,  p.total_price=ROUND(0.78*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='anel de 50 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=9.13,  p.total_price=ROUND(9.13*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.78,  p.total_price=ROUND(1.78*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.83,  p.total_price=ROUND(1.83*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos 45 graus de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.31,  p.total_price=ROUND(1.31*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='luvas de 40 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=5.02,  p.total_price=ROUND(5.02*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos de 40 esgoto com anel';
-- Parafusos: Tognini separa parafuso (0,15) + bucha (0,08) = 0,23/un -> total 20un = 4,60
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.23,  p.total_price=ROUND(0.23*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='parafusos com bucha 6 cabeças-chatas chatas para fita perfurada';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=15.41, p.total_price=ROUND(15.41*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='rolo de fita perfurada';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.53, p.total_price=ROUND(24.53*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo 3/4 marrom (4 barras)'; -- 24,53/barra (4 barras = 98,12)
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.38,  p.total_price=ROUND(1.38*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='T 3/4 marrom';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.66,  p.total_price=ROUND(1.66*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos 45 graus marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.75,  p.total_price=ROUND(0.75*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelo marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=0.75,  p.total_price=ROUND(0.75*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='luvas marrom 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=5.65,  p.total_price=ROUND(5.65*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='isolamento térmico para os drenos de 28 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=35.97, p.total_price=ROUND(35.97*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo cpvc 3/4';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=12.60, p.total_price=ROUND(12.60*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='adaptadores cpvc com rosca macho de 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=9.51,  p.total_price=ROUND(9.51*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='luvas com rosca fêmea 1/2 cpvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=15.99, p.total_price=ROUND(15.99*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo de cola pvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=31.68, p.total_price=ROUND(31.68*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo de cola cpvc';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=24.62, p.total_price=ROUND(24.62*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='tubo esgoto de 100';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=5.26,  p.total_price=ROUND(5.26*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='luvas de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=1.17,  p.total_price=ROUND(1.17*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='anéis de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=8.25,  p.total_price=ROUND(8.25*i.quantity,2)  WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos de 100 esgoto';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=16.64, p.total_price=ROUND(16.64*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='mangueira pex pra gás de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=31.65, p.total_price=ROUND(31.65*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos de 16 mm pra gás com rosca fêmea 1/2';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=46.20, p.total_price=ROUND(46.20*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='T pex pra gás de 16 mm';
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=34.01, p.total_price=ROUND(34.01*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=1; -- UNIAO gas 34,01/un
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=34.01, p.total_price=ROUND(34.01*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='emendas de 16 mm pex pra gás' AND i.quantity=2;
UPDATE purchase_order_item_prices p JOIN purchase_order_items i ON i.id=p.item_id SET p.unit_price=30.32, p.total_price=ROUND(30.32*i.quantity,2) WHERE p.order_id=@order_id AND p.supplier_id=@tognini AND i.material_name='cotovelos pex 16 mm pra gás.'; -- JOELHO 90 gas 30,32/un

-- =====================================================================
-- Recalcular o total de cada fornecedor em purchase_order_suppliers
-- (soma dos total_price + frete; aqui só somamos itens; ajuste frete se
-- necessário: Mimosa frete 60,00)
-- =====================================================================
UPDATE purchase_order_suppliers pos
SET pos.total = (
    SELECT COALESCE(SUM(pip.total_price),0)
    FROM purchase_order_item_prices pip
    WHERE pip.order_id = pos.order_id AND pip.supplier_id = pos.supplier_id
)
WHERE pos.order_id = @order_id;

-- =====================================================================
-- VERIFICAÇÃO – rode e confira antes do COMMIT
-- =====================================================================
SELECT s.name AS fornecedor, i.material_name, i.quantity,
       pip.unit_price, pip.total_price
FROM purchase_order_item_prices pip
JOIN purchase_order_items i ON i.id = pip.item_id
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id = @order_id
ORDER BY s.name, i.id;

SELECT s.name AS fornecedor, pos.total
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id = @order_id;

-- Se estiver tudo certo:
-- COMMIT;
-- Se algo estiver errado:
-- ROLLBACK;
