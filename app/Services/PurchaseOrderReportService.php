<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;
use ZipArchive;

/**
 * Gera o relatório analítico de pedidos de compra em formato .xlsx (Open XML),
 * sem dependências externas (usa apenas ext-zip).
 *
 * Abas geradas:
 *   1. Resumo Geral (totais)
 *   2. Média mensal de pedidos
 *   3. Tempo médio de cotação
 *   4. Tempo médio de aprovação
 *   5. Profissionais com maior número de pedidos
 *   6. Materiais mais consumidos (maior -> menor)
 *   7. Resumo por obra (itens, valor total, nº de pedidos)
 *   8. Número de pedidos diários
 *   9. Participação de cada material no custo total de cada obra
 *  10. Pedidos com status crítico / urgente
 *  11. Profissional com mais pedidos críticos / urgentes
 *
 * Uso:
 *   $binary = PurchaseOrderReportService::buildXlsx();   // string binária .xlsx
 */
class PurchaseOrderReportService
{
    /** Cláusula que exclui pedidos cancelados dos totais. */
    private const NOT_CANCELLED = "po.status <> 'cancelled'";

    /**
     * Monta todas as abas e devolve o conteúdo binário do arquivo .xlsx.
     */
    public static function buildXlsx(?string $from = null, ?string $to = null): string
    {
        $sheets = self::collect($from, $to);
        return self::writeXlsx($sheets);
    }

    /**
     * Devolve os mesmos dados usados no Excel, em forma estruturada,
     * para renderização na tela (dashboard). Cada chave é uma aba/seção
     * com 'headers' e 'rows'.
     *
     * @param string|null $from Data inicial (YYYY-MM-DD), inclusive.
     * @param string|null $to   Data final (YYYY-MM-DD), inclusive.
     * @return array<string,array{headers:string[],rows:array<int,array<int,string>>}>
     */
    public static function collectData(?string $from = null, ?string $to = null): array
    {
        return self::collect($from, $to);
    }

    /**
     * Nome de arquivo sugerido para download.
     */
    public static function suggestedFilename(?string $from = null, ?string $to = null): string
    {
        $sufixo = '';
        if ($from || $to) {
            $sufixo = '_' . ($from ?: 'inicio') . '_a_' . ($to ?: 'hoje');
        }
        return 'relatorio_pedidos' . $sufixo . '_' . date('Y-m-d_His') . '.xlsx';
    }

    /**
     * Normaliza uma data recebida (querystring) para o formato YYYY-MM-DD.
     * Retorna null se vazia ou inválida (evita SQL malformado/injeção).
     */
    public static function normalizeDate(?string $d): ?string
    {
        if (!$d) return null;
        $d = trim($d);
        if ($d === '') return null;
        // Aceita apenas AAAA-MM-DD.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) !== 1) return null;
        // Valida se é uma data real.
        [$y, $m, $day] = array_map('intval', explode('-', $d));
        if (!checkdate($m, $day, $y)) return null;
        return $d;
    }

    // =================================================================
    // COLETA DOS DADOS
    // =================================================================

    /**
     * @param string|null $from Data inicial (YYYY-MM-DD), inclusive.
     * @param string|null $to   Data final (YYYY-MM-DD), inclusive.
     * @return array<string,array{headers:string[],rows:array<int,array<int,string>>}>
     */
    private static function collect(?string $from = null, ?string $to = null): array
    {
        $poCols = self::tableColumns('purchase_orders');
        if (empty($poCols)) {
            return [
                'Erro' => [
                    'headers' => ['Aviso'],
                    'rows'    => [['Tabela purchase_orders não encontrada no banco.']],
                ],
            ];
        }

        $hasUrgency    = in_array('urgency', $poCols, true);
        $hasSiteId     = in_array('construction_site_id', $poCols, true);
        $hasQuoteStart = in_array('quote_started_at', $poCols, true);
        $hasDeadline   = in_array('deadline', $poCols, true);

        // Normaliza o período. As datas já vêm validadas (YYYY-MM-DD) e são
        // interpoladas com segurança pois o formato é garantido.
        $from = self::normalizeDate($from);
        $to   = self::normalizeDate($to);
        // Filtro por data sobre po.created_at (com alias "po").
        $dPo = '';
        // Filtro por data sem alias (tabela purchase_orders direta).
        $dBare = '';
        if ($from) {
            $dPo   .= " AND po.created_at >= '$from 00:00:00'";
            $dBare .= " AND created_at >= '$from 00:00:00'";
        }
        if ($to) {
            $dPo   .= " AND po.created_at <= '$to 23:59:59'";
            $dBare .= " AND created_at <= '$to 23:59:59'";
        }

        $nc = self::NOT_CANCELLED . $dPo;
        $sheets = [];

        // Rótulo do período para exibição.
        if ($from && $to)      $periodoLabel = "De $from até $to";
        elseif ($from)         $periodoLabel = "A partir de $from";
        elseif ($to)           $periodoLabel = "Até $to";
        else                   $periodoLabel = 'Todo o histórico';

        // --- 0. Período aplicado --------------------------------------
        $sheets['0. Periodo'] = [
            'headers' => ['Filtro aplicado', 'Valor'],
            'rows'    => [
                ['Período', $periodoLabel],
                ['Data inicial', $from ?: '—'],
                ['Data final', $to ?: '—'],
                ['Gerado em', date('d/m/Y H:i')],
            ],
        ];

        // --- 1. Resumo geral ------------------------------------------
        $total        = (int) (self::fetch("SELECT COUNT(*) c FROM purchase_orders po WHERE 1=1$dPo")['c'] ?? 0);
        $totalValidos = (int) (self::fetch("SELECT COUNT(*) c FROM purchase_orders po WHERE $nc")['c'] ?? 0);
        $porStatus    = self::all("SELECT status, COUNT(*) c FROM purchase_orders po WHERE 1=1$dPo GROUP BY status ORDER BY c DESC");
        $valorTotal   = (float) (self::fetch("SELECT COALESCE(SUM(i.total_price),0) v
                            FROM purchase_order_items i
                            JOIN purchase_orders po ON po.id = i.order_id
                            WHERE $nc")['v'] ?? 0);

        $resumo = [];
        $resumo[] = ['Total de pedidos (todos)', (string) $total];
        $resumo[] = ['Total de pedidos (excluindo cancelados)', (string) $totalValidos];
        $resumo[] = ['', ''];
        $resumo[] = ['Pedidos por status', ''];
        foreach ($porStatus as $r) {
            $resumo[] = ['  ' . $r['status'], (string) $r['c']];
        }
        $resumo[] = ['', ''];
        $resumo[] = ['Valor total dos itens (R$)', self::money($valorTotal)];

        $sheets['1. Resumo Geral'] = [
            'headers' => ['Indicador', 'Valor'],
            'rows'    => $resumo,
        ];

        // --- 2. Média mensal ------------------------------------------
        $porMes = self::all("SELECT DATE_FORMAT(created_at,'%Y-%m') mes, COUNT(*) c
                             FROM purchase_orders po WHERE $nc GROUP BY mes ORDER BY mes");
        $somaMeses = array_sum(array_map(fn($r) => (int) $r['c'], $porMes));
        $qtdeMeses = count($porMes);
        $mediaMensal = $qtdeMeses > 0 ? $somaMeses / $qtdeMeses : 0;

        $rows2 = [];
        foreach ($porMes as $r) {
            $rows2[] = [$r['mes'], (string) $r['c']];
        }
        $rows2[] = ['', ''];
        $rows2[] = ['MÉDIA MENSAL', self::dec($mediaMensal, 1)];
        $sheets['2. Media Mensal'] = [
            'headers' => ['Mês (AAAA-MM)', 'Qtd. Pedidos'],
            'rows'    => $rows2,
        ];

        // --- 3. Tempo médio de cotação --------------------------------
        $fimCot = 'po.quoted_at';
        $iniCot = $hasQuoteStart ? 'COALESCE(po.quote_started_at, po.created_at)' : 'po.created_at';
        $cot = self::fetch("SELECT
                AVG(TIMESTAMPDIFF(HOUR, $iniCot, $fimCot)) media_h,
                MIN(TIMESTAMPDIFF(HOUR, $iniCot, $fimCot)) min_h,
                MAX(TIMESTAMPDIFF(HOUR, $iniCot, $fimCot)) max_h,
                COUNT(*) n
            FROM purchase_orders po
            WHERE po.quoted_at IS NOT NULL AND $nc") ?? [];
        $rows3 = [
            ['Base de cálculo', $hasQuoteStart ? 'Início da cotação → cotação concluída' : 'Criação → cotação concluída'],
            ['Pedidos considerados (com cotação)', (string) ($cot['n'] ?? 0)],
            ['Tempo MÉDIO de cotação', self::horas(isset($cot['media_h']) ? (float) $cot['media_h'] : null)],
            ['Tempo mínimo', self::horas(isset($cot['min_h']) ? (float) $cot['min_h'] : null)],
            ['Tempo máximo', self::horas(isset($cot['max_h']) ? (float) $cot['max_h'] : null)],
        ];
        $sheets['3. Tempo Medio Cotacao'] = [
            'headers' => ['Indicador', 'Valor'],
            'rows'    => $rows3,
        ];

        // --- 4. Tempo médio de aprovação ------------------------------
        $aprov = self::fetch("SELECT
                AVG(TIMESTAMPDIFF(HOUR, COALESCE(po.quoted_at, po.created_at), po.approved_at)) media_h,
                MIN(TIMESTAMPDIFF(HOUR, COALESCE(po.quoted_at, po.created_at), po.approved_at)) min_h,
                MAX(TIMESTAMPDIFF(HOUR, COALESCE(po.quoted_at, po.created_at), po.approved_at)) max_h,
                COUNT(*) n
            FROM purchase_orders po
            WHERE po.approved_at IS NOT NULL AND $nc") ?? [];
        $leadTotal = self::fetch("SELECT AVG(TIMESTAMPDIFF(HOUR, po.created_at, po.approved_at)) media_h
            FROM purchase_orders po WHERE po.approved_at IS NOT NULL AND $nc") ?? [];
        $rows4 = [
            ['Base de cálculo', 'Cotação concluída → aprovação'],
            ['Pedidos considerados (aprovados)', (string) ($aprov['n'] ?? 0)],
            ['Tempo MÉDIO de aprovação', self::horas(isset($aprov['media_h']) ? (float) $aprov['media_h'] : null)],
            ['Tempo mínimo', self::horas(isset($aprov['min_h']) ? (float) $aprov['min_h'] : null)],
            ['Tempo máximo', self::horas(isset($aprov['max_h']) ? (float) $aprov['max_h'] : null)],
            ['', ''],
            ['Lead time total (criação → aprovação)', self::horas(isset($leadTotal['media_h']) ? (float) $leadTotal['media_h'] : null)],
        ];
        $sheets['4. Tempo Medio Aprovacao'] = [
            'headers' => ['Indicador', 'Valor'],
            'rows'    => $rows4,
        ];

        // --- 5. Profissionais com maior número de pedidos -------------
        $prof = self::all("SELECT COALESCE(NULLIF(TRIM(po.created_by_name),''),'(sem nome)') profissional,
                COUNT(*) qtd, COALESCE(SUM(po.total_estimated),0) valor_total
            FROM purchase_orders po WHERE $nc
            GROUP BY profissional ORDER BY qtd DESC, valor_total DESC");
        $rows5 = [];
        foreach ($prof as $r) {
            $rows5[] = [$r['profissional'], (string) $r['qtd'], self::money((float) $r['valor_total'])];
        }
        $sheets['5. Profissionais por Pedidos'] = [
            'headers' => ['Profissional', 'Qtd. Pedidos', 'Valor Estimado (R$)'],
            'rows'    => $rows5,
        ];

        // --- 6. Materiais mais consumidos -----------------------------
        $mat = self::all("SELECT
                COALESCE(NULLIF(TRIM(i.material_name),''),'(sem nome)') material,
                COUNT(DISTINCT i.order_id) qtd_pedidos,
                SUM(i.quantity) qtd_total,
                COALESCE(SUM(i.total_price),0) valor_total
            FROM purchase_order_items i
            JOIN purchase_orders po ON po.id = i.order_id
            WHERE $nc
            GROUP BY material ORDER BY qtd_total DESC, valor_total DESC");
        $rows6 = [];
        foreach ($mat as $r) {
            $rows6[] = [
                $r['material'],
                (string) $r['qtd_pedidos'],
                self::qty((float) $r['qtd_total']),
                self::money((float) $r['valor_total']),
            ];
        }
        $sheets['6. Materiais Mais Consumidos'] = [
            'headers' => ['Material', 'Nº Pedidos', 'Qtd. Total', 'Valor Total (R$)'],
            'rows'    => $rows6,
        ];

        // --- 7. Resumo por obra ---------------------------------------
        if ($hasSiteId) {
            $obraNome  = "COALESCE(NULLIF(TRIM(cs.name),''), '(sem obra vinculada)')";
            $obraJoin  = "LEFT JOIN construction_sites cs ON cs.id = po.construction_site_id";
            $obraGroup = "cs.id, cs.name";
        } else {
            $obraNome  = "'(coluna de obra ausente nesta base)'";
            $obraJoin  = "";
            $obraGroup = "1";
        }
        $obras = self::all("SELECT
                $obraNome obra,
                COUNT(DISTINCT po.id) qtd_pedidos,
                COUNT(i.id) qtd_itens,
                COALESCE(SUM(i.total_price),0) valor_total
            FROM purchase_orders po
            $obraJoin
            LEFT JOIN purchase_order_items i ON i.order_id = po.id
            WHERE $nc
            GROUP BY $obraGroup ORDER BY valor_total DESC, qtd_pedidos DESC");
        $rows7 = [];
        foreach ($obras as $r) {
            $rows7[] = [
                $r['obra'],
                (string) $r['qtd_pedidos'],
                (string) $r['qtd_itens'],
                self::money((float) $r['valor_total']),
            ];
        }
        $sheets['7. Resumo por Obra'] = [
            'headers' => ['Obra', 'Nº Pedidos', 'Nº Itens', 'Valor Total Gasto (R$)'],
            'rows'    => $rows7,
        ];

        // --- 8. Pedidos diários ---------------------------------------
        $diario = self::all("SELECT DATE(po.created_at) dia, COUNT(*) c
            FROM purchase_orders po WHERE $nc GROUP BY dia ORDER BY dia");
        $rows8 = [];
        $somaDias = 0;
        $qtdeDias = count($diario);
        foreach ($diario as $r) {
            $rows8[] = [date('d/m/Y', strtotime($r['dia'])), (string) $r['c']];
            $somaDias += (int) $r['c'];
        }
        $rows8[] = ['', ''];
        $rows8[] = ['MÉDIA DIÁRIA (dias com pedidos)', $qtdeDias > 0 ? self::dec($somaDias / $qtdeDias, 1) : '0'];
        $sheets['8. Pedidos Diarios'] = [
            'headers' => ['Dia', 'Qtd. Pedidos'],
            'rows'    => $rows8,
        ];

        // --- 9. Material x custo da obra ------------------------------
        if ($hasSiteId) {
            $part = self::all("SELECT
                    COALESCE(NULLIF(TRIM(cs.name),''), '(sem obra vinculada)') obra,
                    COALESCE(NULLIF(TRIM(i.material_name),''),'(sem nome)') material,
                    COALESCE(SUM(i.total_price),0) valor_material
                FROM purchase_order_items i
                JOIN purchase_orders po ON po.id = i.order_id
                LEFT JOIN construction_sites cs ON cs.id = po.construction_site_id
                WHERE $nc
                GROUP BY cs.id, cs.name, material
                HAVING valor_material > 0
                ORDER BY obra ASC, valor_material DESC");
            $totObra = [];
            foreach ($part as $r) {
                $totObra[$r['obra']] = ($totObra[$r['obra']] ?? 0) + (float) $r['valor_material'];
            }
            $rows9 = [];
            foreach ($part as $r) {
                $tot = $totObra[$r['obra']] ?: 1;
                $pct = ((float) $r['valor_material'] / $tot) * 100;
                $rows9[] = [
                    $r['obra'],
                    $r['material'],
                    self::money((float) $r['valor_material']),
                    self::dec($pct, 2) . '%',
                ];
            }
            $sheets['9. Material x Custo Obra'] = [
                'headers' => ['Obra', 'Material', 'Valor Gasto (R$)', '% do Custo da Obra'],
                'rows'    => $rows9,
            ];
        } else {
            $sheets['9. Material x Custo Obra'] = [
                'headers' => ['Aviso'],
                'rows'    => [['A coluna construction_site_id não existe nesta base; não é possível quebrar por obra.']],
            ];
        }

        // --- 10. Pedidos críticos / urgentes --------------------------
        if ($hasUrgency) {
            $deadlineSel = $hasDeadline ? "po.deadline," : "NULL AS deadline,";
            $criticos = self::all("SELECT
                    po.code, po.urgency, po.status,
                    COALESCE(NULLIF(TRIM(po.created_by_name),''),'(sem nome)') profissional,
                    po.created_at, $deadlineSel COALESCE(po.total_estimated,0) total
                FROM purchase_orders po
                WHERE po.urgency IN ('high','critical') AND $nc
                ORDER BY FIELD(po.urgency,'critical','high'), po.created_at DESC");
            $rows10 = [];
            foreach ($criticos as $r) {
                $rows10[] = [
                    $r['code'],
                    $r['urgency'] === 'critical' ? 'Crítico' : 'Alta/Urgente',
                    $r['status'],
                    $r['profissional'],
                    date('d/m/Y H:i', strtotime($r['created_at'])),
                    $r['deadline'] ? date('d/m/Y', strtotime($r['deadline'])) : '—',
                    self::money((float) $r['total']),
                ];
            }
            $conta = self::fetch("SELECT
                    SUM(urgency='critical') criticos, SUM(urgency='high') altos
                FROM purchase_orders po WHERE po.urgency IN ('high','critical') AND $nc") ?? [];
            array_unshift($rows10, ['', '', '', '', '', '', '']);
            array_unshift($rows10, ['TOTAL Alta/Urgente', (string) ($conta['altos'] ?? 0), '', '', '', '', '']);
            array_unshift($rows10, ['TOTAL Crítico', (string) ($conta['criticos'] ?? 0), '', '', '', '', '']);
            $sheets['10. Pedidos Criticos'] = [
                'headers' => ['Código', 'Urgência', 'Status', 'Profissional', 'Criado em', 'Prazo', 'Valor (R$)'],
                'rows'    => $rows10,
            ];
        } else {
            $sheets['10. Pedidos Criticos'] = [
                'headers' => ['Aviso'],
                'rows'    => [['A coluna urgency não existe nesta base; não há como identificar pedidos críticos/urgentes.']],
            ];
        }

        // --- 11. Profissional x críticos ------------------------------
        if ($hasUrgency) {
            $profCrit = self::all("SELECT
                    COALESCE(NULLIF(TRIM(po.created_by_name),''),'(sem nome)') profissional,
                    SUM(po.urgency='critical') criticos,
                    SUM(po.urgency='high') altos,
                    COUNT(*) total_crit
                FROM purchase_orders po
                WHERE po.urgency IN ('high','critical') AND $nc
                GROUP BY profissional ORDER BY total_crit DESC, criticos DESC");
            $rows11 = [];
            foreach ($profCrit as $r) {
                $rows11[] = [
                    $r['profissional'],
                    (string) $r['criticos'],
                    (string) $r['altos'],
                    (string) $r['total_crit'],
                ];
            }
            if (!empty($profCrit)) {
                $lider = $profCrit[0];
                array_unshift($rows11, ['', '', '', '']);
                array_unshift($rows11, [
                    '➤ Profissional com MAIS pedidos críticos/urgentes',
                    $lider['profissional'] . ' (' . $lider['total_crit'] . ' pedidos)', '', '',
                ]);
            }
            $sheets['11. Profissional x Criticos'] = [
                'headers' => ['Profissional', 'Críticos', 'Alta/Urgente', 'Total Crít./Urg.'],
                'rows'    => $rows11,
            ];
        } else {
            $sheets['11. Profissional x Criticos'] = [
                'headers' => ['Aviso'],
                'rows'    => [['A coluna urgency não existe nesta base.']],
            ];
        }

        // --- 12. Materiais aprovados por categoria (menor preço cotado) --
        // Uma linha por material com o MENOR preço unitário aprovado no período.
        // A quantidade mostrada é o total pedido do material dentro do período.
        $sheets['12. Aprovados por Categoria'] = self::approvedByCategory($dPo);

        return $sheets;
    }

    /**
     * Seção 12: materiais dos pedidos APROVADOS, listados um a um, com o
     * preço UNITÁRIO real aprovado (do fornecedor vencedor de cada item).
     *
     * Não agrega por material: cada aparição do material em um pedido aprovado
     * vira uma linha própria. Ex.: "Cinta lombar ergonômica" que aparece em
     * dois pedidos aprovados gera duas linhas (uma com preço unitário de cada
     * pedido). As linhas ficam agrupadas por categoria do material.
     *
     * Regras:
     *   - Considera apenas pedidos aprovados (status = 'approved').
     *   - Usa o unit_price/total_price gravado no item (snapshot do fornecedor
     *     aprovado, preenchido no momento da aprovação).
     *   - Ignora itens sem preço (não cotados) e itens de estoque/transferência.
     *   - A categoria vem de material_categories.name (via materials.category_id).
     *     Sem categoria, usa a classificação/especificação como rótulo.
     *
     * @param string $dateFilter Fragmento SQL de filtro de data (alias "po"),
     *                           ex.: " AND po.created_at >= '...'". Pode ser vazio.
     * @return array{headers:string[],rows:array<int,array<int,string>>}
     */
    private static function approvedByCategory(string $dateFilter = ''): array
    {
        $itemCols = self::tableColumns('purchase_order_items');
        if (empty($itemCols)) {
            return [
                'headers' => ['Aviso'],
                'rows'    => [['A tabela purchase_order_items não existe nesta base.']],
            ];
        }

        $poCols   = self::tableColumns('purchase_orders');
        $matCols  = self::tableColumns('materials');
        $hasMaterials  = !empty($matCols);
        $hasCategories = !empty(self::tableColumns('material_categories'));

        // Rótulo da categoria: categoria cadastrada do material; depois
        // classification/specification; senão "(sem categoria)".
        $categoriaParts = [];
        if ($hasMaterials && $hasCategories && in_array('category_id', $matCols, true)) {
            $categoriaParts[] = 'NULLIF(TRIM(mc.name), \'\')';
        }
        if ($hasMaterials && in_array('specification', $matCols, true)) {
            $categoriaParts[] = 'NULLIF(TRIM(m.specification), \'\')';
        }
        if (in_array('classification', $itemCols, true)) {
            $categoriaParts[] = 'NULLIF(TRIM(i.classification), \'\')';
        }
        if (in_array('specification', $itemCols, true)) {
            $categoriaParts[] = 'NULLIF(TRIM(i.specification), \'\')';
        }
        $categoriaExpr = empty($categoriaParts)
            ? "'(sem categoria)'"
            : 'COALESCE(' . implode(', ', $categoriaParts) . ", '(sem categoria)')";

        $joinMaterial = ($hasMaterials && in_array('material_id', $itemCols, true))
            ? 'LEFT JOIN materials m ON m.id = i.material_id'
            : '';
        $joinCategory = ($joinMaterial !== '' && $hasCategories && in_array('category_id', $matCols, true))
            ? 'LEFT JOIN material_categories mc ON mc.id = m.category_id'
            : '';

        $materialExpr = in_array('material_name', $itemCols, true)
            ? "COALESCE(NULLIF(TRIM(i.material_name), ''), '(sem nome)')"
            : "'(sem nome)'";
        $qtdExpr      = in_array('quantity', $itemCols, true) ? 'i.quantity' : '1';
        $codeExpr     = in_array('code', $poCols, true) ? 'po.code' : "CONCAT('#', po.id)";

        // Preço unitário: usa unit_price; se ausente, deriva de total_price/qtd.
        $hasUnit  = in_array('unit_price', $itemCols, true);
        $hasTotal = in_array('total_price', $itemCols, true);
        if ($hasUnit && $hasTotal) {
            $unitExpr  = "COALESCE(i.unit_price, i.total_price / NULLIF($qtdExpr,0))";
            $totalExpr = "COALESCE(i.total_price, i.unit_price * $qtdExpr)";
            $priceFilter = "(i.unit_price > 0 OR i.total_price > 0)";
        } elseif ($hasUnit) {
            $unitExpr  = "i.unit_price";
            $totalExpr = "i.unit_price * $qtdExpr";
            $priceFilter = "i.unit_price > 0";
        } elseif ($hasTotal) {
            $unitExpr  = "i.total_price / NULLIF($qtdExpr,0)";
            $totalExpr = "i.total_price";
            $priceFilter = "i.total_price > 0";
        } else {
            return [
                'headers' => ['Aviso'],
                'rows'    => [['A tabela purchase_order_items não possui colunas de preço (unit_price/total_price).']],
            ];
        }

        // Exclui itens de estoque/transferência (não são compras cotadas).
        $sourceFilter = in_array('source_type', $itemCols, true)
            ? "AND (i.source_type IS NULL OR i.source_type = 'purchase')"
            : '';

        // Exclui obras de construção que não devem entrar neste relatório
        // (P033 - Mariana Maran - Vanessa e Caique / P027 - Casa Da Montanha).
        // Filtra pelo código da obra em construction_sites; ignora se a base
        // não tiver o vínculo de obra.
        if (in_array('construction_site_id', $poCols, true)
            && !empty(self::tableColumns('construction_sites'))) {
            // As obras a excluir aparecem como "P027 - ..." e "P033 - ..." no
            // NOME da obra (o code interno é OBR-000001 / OBR-000049).
            // Filtramos por code e por prefixo do nome, cobrindo os dois casos.
            $excluirCodes = ['P033', 'P027', 'OBR-000049', 'OBR-000001'];
            $excluirNomes = ['P033%', 'P027%', '%Mariana Maran%', '%Casa Da Montanha%'];
            $inCodes  = implode(',', array_fill(0, count($excluirCodes), '?'));
            $likeNome = implode(' OR ', array_fill(0, count($excluirNomes), 'cs.name LIKE ?'));
            $excludeSitesSql = "AND po.construction_site_id NOT IN (
                    SELECT cs.id FROM construction_sites cs
                    WHERE cs.code IN ($inCodes) OR $likeNome
                )";
            // Parâmetros de UMA ocorrência da cláusula, na ordem: codes, nomes.
            // A repetição (para cada subquery) é feita na montagem de $params.
            $excludeParams = array_merge($excluirCodes, $excluirNomes);
            $sourceFilter .= ' ' . $excludeSitesSql;
        } else {
            $excludeParams = [];
        }

        // Filtro de status aprovado + período (data). O período usa o alias "po".
        $whereBase = "po.status = 'approved' AND $priceFilter $sourceFilter $dateFilter";

        // Uma linha por material: pega o MENOR preço unitário aprovado no
        // período. Também traz a QUANTIDADE total pedida do material dentro
        // do período (soma de todas as ocorrências aprovadas). O pedido
        // mostrado é aquele onde o menor preço foi encontrado.
        $sql = "SELECT
                    x.categoria,
                    x.material,
                    x.preco_unit,
                    x.pedido,
                    q.qtd_total
                FROM (
                    SELECT
                        $categoriaExpr AS categoria,
                        $materialExpr  AS material,
                        $codeExpr      AS pedido,
                        ROUND($unitExpr, 2) AS preco_unit
                    FROM purchase_order_items i
                    JOIN purchase_orders po ON po.id = i.order_id
                    $joinMaterial
                    $joinCategory
                    WHERE $whereBase
                ) x
                JOIN (
                    SELECT categoria, material, MIN(preco_unit) AS menor_unit
                    FROM (
                        SELECT
                            $categoriaExpr AS categoria,
                            $materialExpr  AS material,
                            ROUND($unitExpr, 2) AS preco_unit
                        FROM purchase_order_items i
                        JOIN purchase_orders po ON po.id = i.order_id
                        $joinMaterial
                        $joinCategory
                        WHERE $whereBase
                    ) y
                    GROUP BY categoria, material
                ) mn ON mn.categoria = x.categoria AND mn.material = x.material AND mn.menor_unit = x.preco_unit
                JOIN (
                    SELECT categoria, material, COALESCE(SUM(qtd),0) AS qtd_total
                    FROM (
                        SELECT
                            $categoriaExpr AS categoria,
                            $materialExpr  AS material,
                            $qtdExpr AS qtd
                        FROM purchase_order_items i
                        JOIN purchase_orders po ON po.id = i.order_id
                        $joinMaterial
                        $joinCategory
                        WHERE $whereBase
                    ) z
                    GROUP BY categoria, material
                ) q ON q.categoria = x.categoria AND q.material = x.material
                GROUP BY x.categoria, x.material
                ORDER BY x.categoria ASC, x.material ASC";

        // A cláusula $whereBase (com $excludeParams) aparece 3x na SQL, então
        // repetimos os parâmetros de exclusão de obra na mesma ordem.
        $params = array_merge($excludeParams, $excludeParams, $excludeParams);

        try {
            $data = self::all($sql, $params);
        } catch (Throwable $e) {
            return [
                'headers' => ['Aviso'],
                'rows'    => [['Não foi possível calcular esta seção: ' . $e->getMessage()]],
            ];
        }

        // Agrupa por categoria (uma linha por material, com o menor preço).
        $porCategoria = [];
        foreach ($data as $r) {
            $porCategoria[(string) $r['categoria']][] = $r;
        }
        // Ordena categorias pelo somatório dos menores preços (desc).
        $subtotalCat = [];
        foreach ($porCategoria as $cat => $itens) {
            $subtotalCat[$cat] = array_sum(array_map(fn($x) => (float) $x['preco_unit'], $itens));
        }
        arsort($subtotalCat);

        $rows = [];
        $totalGeral = 0.0;
        $totalLinhas = 0;
        foreach (array_keys($subtotalCat) as $cat) {
            $itens    = $porCategoria[$cat];
            $subTotal = 0.0;
            $subQtd   = 0.0;
            $rows[] = ['▸ ' . $cat, '', '', ''];
            foreach ($itens as $it) {
                $unit = (float) $it['preco_unit'];
                $qtd  = (float) $it['qtd_total'];
                $subTotal += $unit;
                $subQtd   += $qtd;
                $totalLinhas++;
                $rows[] = [
                    '   ' . (string) $it['material'],
                    (string) $it['pedido'],
                    self::qty($qtd),
                    self::money($unit),
                ];
            }
            $rows[] = ['   Subtotal ' . $cat, '', self::qty($subQtd), self::money($subTotal)];
            $rows[] = ['', '', '', ''];
            $totalGeral += $subTotal;
        }

        if (empty($rows)) {
            $rows[] = ['(nenhum item aprovado com preço)', '', '', self::money(0)];
        } else {
            $rows[] = ['TOTAL GERAL (' . $totalLinhas . ' materiais)', '', '', self::money($totalGeral)];
        }

        return [
            'headers' => ['Categoria / Material', 'Pedido (menor preço)', 'Qtd. no período', 'Menor Preço Unit. (R$)'],
            'rows'    => $rows,
        ];
    }

    // =================================================================
    // ACESSO A DADOS (via Database do projeto)
    // =================================================================

    private static function all(string $sql, array $params = []): array
    {
        return Database::fetchAll($sql, $params);
    }

    private static function fetch(string $sql, array $params = []): ?array
    {
        return Database::fetch($sql, $params);
    }

    private static function tableColumns(string $table): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            return array_map(fn($r) => $r['COLUMN_NAME'], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    // =================================================================
    // FORMATAÇÃO
    // =================================================================

    private static function money(float $v): string
    {
        return number_format($v, 2, ',', '.');
    }

    private static function dec(float $v, int $casas): string
    {
        return number_format($v, $casas, ',', '.');
    }

    private static function qty(float $v): string
    {
        $s = number_format($v, 2, ',', '.');
        // remove casas decimais nulas: 12,00 -> 12
        return preg_replace('/,00$/', '', $s);
    }

    private static function horas(?float $horas): string
    {
        if ($horas === null) return '—';
        if ($horas < 24) return round($horas, 1) . ' h';
        $dias = $horas / 24;
        return round($dias, 1) . ' dias (' . round($horas, 1) . ' h)';
    }

    // =================================================================
    // ESCRITOR XLSX (Open XML, apenas ext-zip)
    // =================================================================

    /**
     * @param array<string,array{headers:string[],rows:array<int,array<int,string>>}> $sheets
     */
    private static function writeXlsx(array $sheets): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException("Extensão PHP 'zip' não está disponível no servidor.");
        }

        $sharedStrings = [];
        $ssIndex = [];
        $addStr = function (string $s) use (&$sharedStrings, &$ssIndex): int {
            if (isset($ssIndex[$s])) return $ssIndex[$s];
            $i = count($sharedStrings);
            $sharedStrings[] = $s;
            $ssIndex[$s] = $i;
            return $i;
        };

        $sheetXmls = [];
        $sheetNames = [];
        $idx = 0;
        foreach ($sheets as $name => $data) {
            $idx++;
            $sheetNames[] = self::sanitizeSheetName($name, $idx);
            $rowsXml = '';
            $r = 1;
            $rowsXml .= self::rowXml($r++, $data['headers'], $addStr, true);
            foreach ($data['rows'] as $row) {
                $rowsXml .= self::rowXml($r++, $row, $addStr, false);
            }
            $sheetXmls[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>' . $rowsXml . '</sheetData></worksheet>';
        }

        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
            . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
        foreach ($sharedStrings as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . self::esc($s) . '</t></si>';
        }
        $ssXml .= '</sst>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $wbSheets = '';
        foreach ($sheetNames as $i => $sn) {
            $wbSheets .= '<sheet name="' . self::esc($sn) . '" sheetId="' . ($i + 1)
                . '" r:id="rId' . ($i + 1) . '"/>';
        }
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $wbSheets . '</sheets></workbook>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheetNames as $i => $sn) {
            $wbRels .= '<Relationship Id="rId' . ($i + 1)
                . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                . 'Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $rIdSS = count($sheetNames) + 1;
        $rIdStyles = count($sheetNames) + 2;
        $wbRels .= '<Relationship Id="rId' . $rIdSS
            . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $wbRels .= '<Relationship Id="rId' . $rIdStyles
            . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $wbRels .= '</Relationships>';

        $ctOverrides = '';
        foreach ($sheetNames as $i => $sn) {
            $ctOverrides .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1)
                . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $ctOverrides
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        // Grava num arquivo temporário e lê de volta (ZipArchive precisa de arquivo).
        $tmp = tempnam(sys_get_temp_dir(), 'poxlsx');
        if ($tmp === false) {
            throw new \RuntimeException('Não consegui criar arquivo temporário para o Excel.');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não consegui abrir o pacote XLSX para escrita.');
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        foreach ($sheetXmls as $i => $xml) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);
        if ($binary === false) {
            throw new \RuntimeException('Falha ao ler o arquivo XLSX gerado.');
        }
        return $binary;
    }

    private static function rowXml(int $rowNum, array $cells, callable $addStr, bool $bold): string
    {
        $xml = '<row r="' . $rowNum . '">';
        $col = 0;
        foreach ($cells as $val) {
            $ref = self::colLetter($col) . $rowNum;
            $val = (string) $val;
            $style = $bold ? ' s="1"' : '';
            if ($val === '') {
                $xml .= '<c r="' . $ref . '"' . $style . '/>';
            } elseif (preg_match('/^-?\d+$/', $val) === 1) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $val . '</v></c>';
            } else {
                $si = $addStr($val);
                $xml .= '<c r="' . $ref . '"' . $style . ' t="s"><v>' . $si . '</v></c>';
            }
            $col++;
        }
        return $xml . '</row>';
    }

    private static function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }
        return $letter;
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function sanitizeSheetName(string $name, int $idx): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name);
        $name = trim($name);
        if ($name === '') $name = 'Aba' . $idx;
        // Limite de 31 caracteres do Excel. Usa mbstring se disponível, senão corta por bytes.
        if (function_exists('mb_strlen')) {
            if (mb_strlen($name, 'UTF-8') > 31) {
                $name = mb_substr($name, 0, 31, 'UTF-8');
            }
        } elseif (strlen($name) > 31) {
            $name = substr($name, 0, 31);
        }
        return $name;
    }
}
