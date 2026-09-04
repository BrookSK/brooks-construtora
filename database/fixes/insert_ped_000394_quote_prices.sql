-- =====================================================================
-- Inserir/gravar a cotação do PED-000394 (order_id = 424) com os
-- valores corretos lidos dos 3 PDFs.
--
-- Fornecedores:  NICOM = 62 | CASA TOGNINI = 95 | CASA MIMOSA = 118
-- Itens: ids 1565..1609 (usados diretamente, sem match por nome).
--
-- O script:
--   1. Garante o vínculo do fornecedor em purchase_order_suppliers.
--   2. Limpa preços antigos destes fornecedores neste pedido.
--   3. Insere unit_price + total_price (= unit * qtd) por item.
--   4. Recalcula o total de cada fornecedor.
--
-- IMPORTANTE: este script roda SEM transação manual (autocommit).
-- Cada comando é confirmado na hora. Se rodar de novo, ele limpa os
-- dados anteriores destes fornecedores antes de reinserir (idempotente).
--
-- OBS: valores por BARRA/UNIDADE conforme PDF. Onde o pedido pede metros
-- mas o fornecedor cota por barra, o unit_price é por barra.
-- =====================================================================

SET @order_id := 424;
SET @mimosa   := 118;
SET @nicom    := 62;
SET @tognini  := 95;
SET @now      := NOW();

-- ── 1. Garantir vínculo dos fornecedores ─────────────────────────────
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @mimosa, 'quoted', 'Ajuste IA (revisado)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@mimosa);
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @nicom, 'quoted', 'Ajuste IA (revisado)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@nicom);
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @tognini, 'quoted', 'Ajuste IA (revisado)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@tognini);

-- ── 2. Limpar preços antigos destes fornecedores neste pedido ────────
DELETE FROM purchase_order_item_prices
WHERE order_id=@order_id AND supplier_id IN (@mimosa,@nicom,@tognini);

-- =====================================================================
-- 3. INSERT dos preços. Cada linha: (item_id, unit_price)
--    total_price é calculado = unit_price * quantidade do item.
-- =====================================================================

-- ---------- CASA MIMOSA (unitários do PDF 3765431) -------------------
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @mimosa, x.up, ROUND(x.up*i.quantity,2), @now
FROM (
  SELECT 1565 item_id, 5.32  up UNION ALL SELECT 1566, 7.09  UNION ALL SELECT 1567, 26.07 UNION ALL
  SELECT 1568, 19.26 UNION ALL SELECT 1569, 22.69 UNION ALL SELECT 1570, 46.45 UNION ALL
  SELECT 1571, 24.29 UNION ALL SELECT 1572, 2.94  UNION ALL SELECT 1573, 24.29 UNION ALL
  SELECT 1574, 35.36 UNION ALL SELECT 1575, 24.79 UNION ALL SELECT 1576, 12.42 UNION ALL
  SELECT 1577, 3.25  UNION ALL SELECT 1578, 3.31  UNION ALL SELECT 1579, 3.04  UNION ALL
  SELECT 1580, 2.94  UNION ALL SELECT 1581, 0.97  UNION ALL SELECT 1582, 7.63  UNION ALL
  SELECT 1583, 2.12  UNION ALL SELECT 1584, 2.12  UNION ALL SELECT 1585, 1.51  UNION ALL
  SELECT 1586, 6.32  UNION ALL SELECT 1587, 4.65  UNION ALL SELECT 1588, 39.45 UNION ALL
  SELECT 1589, 30.16 UNION ALL SELECT 1590, 1.61  UNION ALL SELECT 1591, 1.92  UNION ALL
  SELECT 1592, 0.87  UNION ALL SELECT 1593, 0.87  UNION ALL SELECT 1594, 5.14  UNION ALL
  SELECT 1595, 41.53 UNION ALL SELECT 1596, 19.82 UNION ALL SELECT 1597, 11.33 UNION ALL
  SELECT 1598, 17.89 UNION ALL SELECT 1599, 46.52 UNION ALL SELECT 1600, 20.55 UNION ALL
  SELECT 1601, 6.07  UNION ALL SELECT 1602, 2.16  UNION ALL SELECT 1603, 10.39 UNION ALL
  SELECT 1604, 12.09 UNION ALL SELECT 1605, 27.89 UNION ALL SELECT 1606, 36.35 UNION ALL
  SELECT 1607, 15.58 UNION ALL SELECT 1608, 22.21 UNION ALL SELECT 1609, 22.21
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;

-- ---------- NICOM (unitários do PDF 5553311; total/qtd) --------------
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @nicom, x.up, ROUND(x.up*i.quantity,2), @now
FROM (
  SELECT 1565 item_id, 7.49  up UNION ALL SELECT 1566, 8.90  UNION ALL SELECT 1567, 18.90 UNION ALL
  SELECT 1568, 13.90 UNION ALL SELECT 1569, 31.90 UNION ALL SELECT 1570, 59.90 UNION ALL
  SELECT 1571, 24.90 UNION ALL SELECT 1572, 31.90 UNION ALL SELECT 1573, 23.90 UNION ALL
  SELECT 1574, 26.90 UNION ALL SELECT 1575, 34.90 UNION ALL SELECT 1576, 26.63 UNION ALL
  SELECT 1577, 4.50  UNION ALL SELECT 1578, 4.50  UNION ALL SELECT 1579, 3.50  UNION ALL
  SELECT 1580, 3.00  UNION ALL SELECT 1581, 1.49  UNION ALL SELECT 1582, 7.97  UNION ALL
  SELECT 1584, 2.50  UNION ALL SELECT 1585, 2.00  UNION ALL
  SELECT 1586, 7.50  UNION ALL SELECT 1587, 59.90 UNION ALL
  SELECT 1589, 6.65  UNION ALL SELECT 1590, 1.99  UNION ALL SELECT 1591, 2.50  UNION ALL
  SELECT 1592, 0.90  UNION ALL SELECT 1593, 1.10  UNION ALL
  SELECT 1595, 39.90 UNION ALL SELECT 1596, 19.90 UNION ALL SELECT 1597, 11.90 UNION ALL
  SELECT 1598, 22.90 UNION ALL SELECT 1599, 38.90 UNION ALL SELECT 1600, 34.95 UNION ALL
  SELECT 1601, 7.50  UNION ALL SELECT 1602, 2.00  UNION ALL SELECT 1603, 12.90 UNION ALL
  SELECT 1604, 17.90 UNION ALL SELECT 1605, 49.90 UNION ALL SELECT 1606, 74.90 UNION ALL
  SELECT 1607, 52.90 UNION ALL SELECT 1608, 52.90 UNION ALL SELECT 1609, 54.90
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;
-- NOTA NICOM: não cotou fita perfurada (1588), cotovelo 90 40 esgoto (1583),
-- isolamento térmico (1594). Esses itens ficam sem preço (correto).

-- ---------- CASA TOGNINI (unitários do PDF 289989) -------------------
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @tognini, x.up, ROUND(x.up*i.quantity,2), @now
FROM (
  SELECT 1565 item_id, 7.25  up UNION ALL SELECT 1566, 9.50  UNION ALL SELECT 1567, 14.80 UNION ALL
  SELECT 1568, 10.20 UNION ALL SELECT 1569, 12.51 UNION ALL SELECT 1570, 31.57 UNION ALL
  SELECT 1571, 17.37 UNION ALL SELECT 1572, 9.65  UNION ALL SELECT 1573, 19.00 UNION ALL
  SELECT 1574, 20.04 UNION ALL SELECT 1575, 24.02 UNION ALL SELECT 1576, 14.88 UNION ALL
  SELECT 1577, 2.82  UNION ALL SELECT 1578, 2.87  UNION ALL SELECT 1579, 2.62  UNION ALL
  SELECT 1580, 2.54  UNION ALL SELECT 1581, 0.78  UNION ALL SELECT 1582, 9.13  UNION ALL
  SELECT 1583, 1.78  UNION ALL SELECT 1584, 1.83  UNION ALL SELECT 1585, 1.31  UNION ALL
  SELECT 1586, 5.02  UNION ALL SELECT 1587, 0.23  UNION ALL SELECT 1588, 15.41 UNION ALL
  SELECT 1589, 24.53 UNION ALL SELECT 1590, 1.38  UNION ALL SELECT 1591, 1.66  UNION ALL
  SELECT 1592, 0.75  UNION ALL SELECT 1593, 0.75  UNION ALL SELECT 1594, 5.65  UNION ALL
  SELECT 1595, 35.97 UNION ALL SELECT 1596, 12.60 UNION ALL SELECT 1597, 9.51  UNION ALL
  SELECT 1598, 15.99 UNION ALL SELECT 1599, 31.68 UNION ALL SELECT 1600, 24.62 UNION ALL
  SELECT 1601, 5.26  UNION ALL SELECT 1602, 1.17  UNION ALL SELECT 1603, 8.25  UNION ALL
  SELECT 1604, 16.64 UNION ALL SELECT 1605, 31.65 UNION ALL SELECT 1606, 46.20 UNION ALL
  SELECT 1607, 34.01 UNION ALL SELECT 1608, 34.01 UNION ALL SELECT 1609, 30.32
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;

-- ── 4. Recalcular total de cada fornecedor ───────────────────────────
-- Subtotal dos itens (soma dos total_price) para cada fornecedor
SET @sub_mimosa  := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@mimosa);
SET @sub_nicom   := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@nicom);
SET @sub_tognini := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@tognini);

-- Fretes conforme os orçamentos
SET @frete_mimosa  := 60.00;   -- Casa Mimosa: VALOR DO FRETE 60,00
SET @frete_nicom   := 0.00;    -- Nicom: FRETE 0,00
SET @frete_tognini := 0.00;    -- Tognini: retira (sem frete informado)

-- ── CASA MIMOSA: financeiro (dinheiro / a vista / frete 60 / vend. GABRIEL) ──
UPDATE purchase_order_suppliers SET
    subtotal_items    = @sub_mimosa,
    subtotal_final    = ROUND(@sub_mimosa + @frete_mimosa, 2),
    total             = ROUND(@sub_mimosa + @frete_mimosa, 2),
    discount_type     = 'percent', discount_value = 0,
    surcharge_type    = 'percent', surcharge_value = 0,
    ipi_percent       = 0, icms_percent = 0,
    freight           = @frete_mimosa,
    vendor_name       = 'GABRIEL',
    vendor_email      = 'gabriel@casamimosa.com.br',
    payment_method    = 'dinheiro',
    payment_condition = 'a vista'
WHERE order_id=@order_id AND supplier_id=@mimosa;

-- ── NICOM: financeiro (boleto / 45 DDL / sem frete / vend. RAYANE) ──
UPDATE purchase_order_suppliers SET
    subtotal_items    = @sub_nicom,
    subtotal_final    = ROUND(@sub_nicom + @frete_nicom, 2),
    total             = ROUND(@sub_nicom + @frete_nicom, 2),
    discount_type     = 'percent', discount_value = 0,
    surcharge_type    = 'percent', surcharge_value = 0,
    ipi_percent       = 0, icms_percent = 0,
    freight           = @frete_nicom,
    vendor_name       = 'RAYANE',
    payment_method    = 'boleto',
    payment_condition = '45 dias'
WHERE order_id=@order_id AND supplier_id=@nicom;

-- ── CASA TOGNINI: financeiro (a combinar / retira / vend. RODRIGUES) ──
UPDATE purchase_order_suppliers SET
    subtotal_items    = @sub_tognini,
    subtotal_final    = ROUND(@sub_tognini + @frete_tognini, 2),
    total             = ROUND(@sub_tognini + @frete_tognini, 2),
    discount_type     = 'percent', discount_value = 0,
    surcharge_type    = 'percent', surcharge_value = 0,
    ipi_percent       = 0, icms_percent = 0,
    freight           = @frete_tognini,
    vendor_name       = 'RODRIGUES',
    payment_condition = 'a combinar',
    payment_notes     = 'Validade 5 dias. Deposito: retira.'
WHERE order_id=@order_id AND supplier_id=@tognini;

-- ── 5. Historico de precos (material_price_history) ──────────────────
-- Limpar historico anterior destes fornecedores neste pedido (evita duplicata)
DELETE FROM material_price_history
WHERE order_id=@order_id AND supplier_id IN (@mimosa,@nicom,@tognini);

-- Reinserir a partir dos precos gravados (usa material_id/nome do item)
INSERT INTO material_price_history
    (material_id, material_name, supplier_id, order_id, unit_price, quantity, was_approved, quoted_at, created_at)
SELECT i.material_id, i.material_name, pip.supplier_id, pip.order_id,
       pip.unit_price, i.quantity, 0, @now, @now
FROM purchase_order_item_prices pip
JOIN purchase_order_items i ON i.id = pip.item_id
WHERE pip.order_id=@order_id AND pip.supplier_id IN (@mimosa,@nicom,@tognini);

-- =====================================================================
-- VERIFICACAO – confira antes do COMMIT
-- =====================================================================
-- Totais por fornecedor (esperado aprox.: Mimosa ~3.301,58 sem frete;
-- Tognini ~3.020,65; Nicom menor pois faltam itens não cotados)
SELECT s.name AS fornecedor,
       COUNT(*) AS itens_com_preco,
       ROUND(SUM(pip.total_price),2) AS total_itens
FROM purchase_order_item_prices pip
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id = @order_id
GROUP BY s.name;

-- Detalhe completo
SELECT s.name AS fornecedor, pip.item_id, i.material_name, i.quantity,
       pip.unit_price, pip.total_price
FROM purchase_order_item_prices pip
JOIN purchase_order_items i ON i.id = pip.item_id
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id = @order_id
ORDER BY s.name, pip.item_id;

-- Financeiro por fornecedor (frete, pagamento, vendedor, totais)
SELECT s.name AS fornecedor, pos.subtotal_items, pos.freight,
       pos.total, pos.vendor_name, pos.payment_method, pos.payment_condition
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id = @order_id;

-- Quantas linhas foram para o historico de precos
SELECT COUNT(*) AS linhas_historico
FROM material_price_history
WHERE order_id = @order_id;

-- Sem transacao manual: os dados JA estao salvos (autocommit).
-- Basta recarregar a tela do pedido PED-000394 para ver os valores.
