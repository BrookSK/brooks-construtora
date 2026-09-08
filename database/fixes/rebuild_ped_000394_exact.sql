-- =====================================================================
-- RECONSTRUÇÃO EXATA da cotação PED-000394 (order_id = 424)
-- Cada total_price = TOTAL EXATO da linha no PDF do fornecedor.
-- unit_price = total_price / quantidade do item (referência).
-- Autocommit (sem transação manual): salva na hora.
--
-- Fornecedores: NICOM=62 | CASA TOGNINI=95 | CASA MIMOSA=118
-- Itens: 1565..1609
--
-- Onde o fornecedor NÃO cotou um item, a linha é OMITIDA (fica sem preço).
-- Onde a quantidade cotada difere da do pedido, usamos o TOTAL efetivamente
-- cotado no PDF (embalagem/barra/qtd diferente).
-- =====================================================================

SET @order_id := 424;
SET @mimosa   := 118;
SET @nicom    := 62;
SET @tognini  := 95;
SET @now      := NOW();

-- Limpar tudo deste pedido para reconstruir do zero (idempotente)
DELETE FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id IN (@mimosa,@nicom,@tognini);
DELETE FROM material_price_history     WHERE order_id=@order_id AND supplier_id IN (@mimosa,@nicom,@tognini);

-- Garantir vínculo dos fornecedores
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @mimosa, 'quoted', 'Ajuste manual (PDF)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@mimosa);
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @nicom, 'quoted', 'Ajuste manual (PDF)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@nicom);
INSERT INTO purchase_order_suppliers (order_id, supplier_id, status, quoted_by_name, quoted_at, created_at)
SELECT @order_id, @tognini, 'quoted', 'Ajuste manual (PDF)', @now, @now
WHERE NOT EXISTS (SELECT 1 FROM purchase_order_suppliers WHERE order_id=@order_id AND supplier_id=@tognini);

-- =====================================================================
-- CASA MIMOSA — total_price = TOTAL da linha do PDF 3765431
-- =====================================================================
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @mimosa, ROUND(x.tot/i.quantity,4), x.tot, @now
FROM (
  SELECT 1565 item_id, 106.40 tot UNION ALL SELECT 1566, 70.90  UNION ALL SELECT 1567, 260.70 UNION ALL
  SELECT 1568, 192.60 UNION ALL SELECT 1569, 226.90 UNION ALL SELECT 1570, 371.60 UNION ALL
  SELECT 1571, 194.32 UNION ALL SELECT 1572, 23.52  UNION ALL SELECT 1573, 242.90 UNION ALL
  SELECT 1574, 353.60 UNION ALL SELECT 1575, 371.85 UNION ALL SELECT 1576, 37.26  UNION ALL
  SELECT 1577, 19.50  UNION ALL SELECT 1578, 19.86  UNION ALL SELECT 1579, 24.32  UNION ALL
  SELECT 1580, 5.88   UNION ALL SELECT 1581, 9.70   UNION ALL SELECT 1582, 22.89  UNION ALL
  SELECT 1583, 12.24  UNION ALL SELECT 1584, 12.72  UNION ALL SELECT 1585, 6.04   UNION ALL
  SELECT 1586, 25.28  UNION ALL SELECT 1587, 18.60  UNION ALL SELECT 1588, 39.45  UNION ALL
  SELECT 1589, 120.64 UNION ALL SELECT 1590, 9.66   UNION ALL SELECT 1591, 15.36  UNION ALL
  SELECT 1592, 8.70   UNION ALL SELECT 1593, 5.22   UNION ALL SELECT 1594, 20.56  UNION ALL
  SELECT 1595, 41.53  UNION ALL SELECT 1596, 59.46  UNION ALL SELECT 1597, 33.99  UNION ALL
  SELECT 1598, 17.89  UNION ALL SELECT 1599, 46.52  UNION ALL SELECT 1600, 41.10  UNION ALL
  SELECT 1601, 24.28  UNION ALL SELECT 1602, 12.96  UNION ALL SELECT 1603, 41.56  UNION ALL
  SELECT 1604, 60.45  UNION ALL SELECT 1605, 83.67  UNION ALL SELECT 1606, 109.05 UNION ALL
  SELECT 1607, 31.16  UNION ALL SELECT 1608, 66.63
  -- 1609 (cotovelo pex gás): Mimosa não tem linha separada -> OMITIDO
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;

-- =====================================================================
-- NICOM — total_price = TOTAL da linha do PDF 5553311
-- =====================================================================
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @nicom, ROUND(x.tot/i.quantity,4), x.tot, @now
FROM (
  SELECT 1565 item_id, 149.80 tot UNION ALL SELECT 1566, 89.00  UNION ALL SELECT 1567, 189.00 UNION ALL
  SELECT 1568, 139.00 UNION ALL SELECT 1569, 319.00 UNION ALL SELECT 1570, 479.20 UNION ALL
  SELECT 1571, 199.20 UNION ALL
  -- 1572 reduções 3/4->1/2: Nicom NAO cotou (a 2a linha 525685=255,20 é união 20x16 repetida) -> OMITIDO
  SELECT 1573, 239.00 UNION ALL SELECT 1574, 269.00 UNION ALL SELECT 1575, 523.50 UNION ALL
  SELECT 1576, 79.90  UNION ALL SELECT 1577, 27.00  UNION ALL SELECT 1578, 27.00  UNION ALL
  SELECT 1579, 28.00  UNION ALL SELECT 1580, 6.00   UNION ALL SELECT 1581, 14.90  UNION ALL
  SELECT 1582, 23.90  UNION ALL
  -- 1583 cotovelo 90 de 40 esgoto: Nicom só cotou o 45 -> OMITIDO
  SELECT 1584, 15.00  UNION ALL SELECT 1585, 8.00   UNION ALL SELECT 1586, 30.00  UNION ALL
  SELECT 1587, 59.90  UNION ALL
  -- 1588 fita perfurada: Nicom NAO cotou -> OMITIDO
  SELECT 1589, 159.60 UNION ALL SELECT 1590, 11.94  UNION ALL SELECT 1591, 20.00  UNION ALL
  SELECT 1592, 9.00   UNION ALL SELECT 1593, 6.60   UNION ALL
  -- 1594 isolamento térmico: Nicom NAO cotou -> OMITIDO
  SELECT 1595, 39.90  UNION ALL SELECT 1596, 59.70  UNION ALL SELECT 1597, 35.70  UNION ALL
  SELECT 1598, 22.90  UNION ALL SELECT 1599, 38.90  UNION ALL SELECT 1600, 69.90  UNION ALL
  SELECT 1601, 30.00  UNION ALL SELECT 1602, 8.00   UNION ALL SELECT 1603, 51.60  UNION ALL
  SELECT 1604, 179.00 UNION ALL SELECT 1605, 149.70 UNION ALL SELECT 1606, 224.70 UNION ALL
  SELECT 1607, 105.80 UNION ALL SELECT 1608, 105.80 UNION ALL SELECT 1609, 164.70
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;

-- =====================================================================
-- CASA TOGNINI — total_price = TOTAL da linha do PDF 289989
-- =====================================================================
INSERT INTO purchase_order_item_prices (order_id,item_id,supplier_id,unit_price,total_price,created_at)
SELECT @order_id, x.item_id, @tognini, ROUND(x.tot/i.quantity,4), x.tot, @now
FROM (
  SELECT 1565 item_id, 145.00 tot UNION ALL SELECT 1566, 95.00  UNION ALL SELECT 1567, 148.00 UNION ALL
  SELECT 1568, 102.00 UNION ALL SELECT 1569, 125.10 UNION ALL SELECT 1570, 252.56 UNION ALL
  SELECT 1571, 138.96 UNION ALL SELECT 1572, 77.20  UNION ALL SELECT 1573, 190.00 UNION ALL
  SELECT 1574, 200.40 UNION ALL SELECT 1575, 360.30 UNION ALL SELECT 1576, 44.64  UNION ALL
  SELECT 1577, 16.92  UNION ALL SELECT 1578, 17.22  UNION ALL SELECT 1579, 20.96  UNION ALL
  SELECT 1580, 5.08   UNION ALL SELECT 1581, 7.80   UNION ALL SELECT 1582, 27.39  UNION ALL
  SELECT 1583, 10.68  UNION ALL SELECT 1584, 10.98  UNION ALL SELECT 1585, 5.24   UNION ALL
  SELECT 1586, 20.08  UNION ALL SELECT 1587, 4.60   UNION ALL SELECT 1588, 15.41  UNION ALL
  SELECT 1589, 98.12  UNION ALL SELECT 1590, 8.28   UNION ALL SELECT 1591, 13.28  UNION ALL
  SELECT 1592, 7.50   UNION ALL SELECT 1593, 4.50   UNION ALL SELECT 1594, 28.25  UNION ALL
  SELECT 1595, 35.97  UNION ALL SELECT 1596, 37.80  UNION ALL SELECT 1597, 28.53  UNION ALL
  SELECT 1598, 15.99  UNION ALL SELECT 1599, 31.68  UNION ALL SELECT 1600, 49.24  UNION ALL
  SELECT 1601, 21.04  UNION ALL SELECT 1602, 7.02   UNION ALL SELECT 1603, 33.00  UNION ALL
  SELECT 1604, 166.40 UNION ALL SELECT 1605, 94.95  UNION ALL SELECT 1606, 138.60 UNION ALL
  SELECT 1607, 68.02  UNION ALL SELECT 1608, 68.02  UNION ALL SELECT 1609, 90.96
) x
JOIN purchase_order_items i ON i.id = x.item_id AND i.order_id = @order_id;

-- =====================================================================
-- Financeiro por fornecedor + recálculo de totais
-- =====================================================================
SET @sub_mimosa  := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@mimosa);
SET @sub_nicom   := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@nicom);
SET @sub_tognini := (SELECT COALESCE(SUM(total_price),0) FROM purchase_order_item_prices WHERE order_id=@order_id AND supplier_id=@tognini);

UPDATE purchase_order_suppliers SET
    subtotal_items=@sub_mimosa, subtotal_final=ROUND(@sub_mimosa+60.00,2), total=ROUND(@sub_mimosa+60.00,2),
    discount_type='percent', discount_value=0, surcharge_type='percent', surcharge_value=0,
    ipi_percent=0, icms_percent=0, freight=60.00,
    vendor_name='GABRIEL', vendor_email='gabriel@casamimosa.com.br',
    payment_method='dinheiro', payment_condition='a vista'
WHERE order_id=@order_id AND supplier_id=@mimosa;

UPDATE purchase_order_suppliers SET
    subtotal_items=@sub_nicom, subtotal_final=ROUND(@sub_nicom+0.00,2), total=ROUND(@sub_nicom+0.00,2),
    discount_type='percent', discount_value=0, surcharge_type='percent', surcharge_value=0,
    ipi_percent=0, icms_percent=0, freight=0.00,
    vendor_name='RAYANE', payment_method='boleto', payment_condition='45 dias'
WHERE order_id=@order_id AND supplier_id=@nicom;

UPDATE purchase_order_suppliers SET
    subtotal_items=@sub_tognini, subtotal_final=ROUND(@sub_tognini+0.00,2), total=ROUND(@sub_tognini+0.00,2),
    discount_type='percent', discount_value=0, surcharge_type='percent', surcharge_value=0,
    ipi_percent=0, icms_percent=0, freight=0.00,
    vendor_name='RODRIGUES', payment_condition='a combinar',
    payment_notes='Validade 5 dias. Deposito: retira.'
WHERE order_id=@order_id AND supplier_id=@tognini;

-- Histórico de preços
INSERT INTO material_price_history
    (material_id, material_name, supplier_id, order_id, unit_price, quantity, was_approved, quoted_at, created_at)
SELECT i.material_id, i.material_name, pip.supplier_id, pip.order_id,
       pip.unit_price, i.quantity, 0, @now, @now
FROM purchase_order_item_prices pip
JOIN purchase_order_items i ON i.id = pip.item_id
WHERE pip.order_id=@order_id AND pip.supplier_id IN (@mimosa,@nicom,@tognini);

-- =====================================================================
-- VERIFICAÇÃO
-- =====================================================================
-- Totais por fornecedor (SÓ itens, sem frete)
SELECT s.name AS fornecedor, COUNT(*) AS itens, ROUND(SUM(pip.total_price),2) AS total_itens
FROM purchase_order_item_prices pip
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id=@order_id
GROUP BY s.name;

-- Detalhe completo (confira célula a célula com os PDFs)
SELECT s.name AS fornecedor, pip.item_id, i.material_name, i.quantity,
       pip.unit_price, pip.total_price
FROM purchase_order_item_prices pip
JOIN purchase_order_items i ON i.id = pip.item_id
JOIN suppliers s ON s.id = pip.supplier_id
WHERE pip.order_id=@order_id
ORDER BY s.name, pip.item_id;

-- Financeiro por fornecedor
SELECT s.name AS fornecedor, pos.subtotal_items, pos.freight, pos.total,
       pos.vendor_name, pos.payment_method, pos.payment_condition
FROM purchase_order_suppliers pos
JOIN suppliers s ON s.id = pos.supplier_id
WHERE pos.order_id=@order_id;
