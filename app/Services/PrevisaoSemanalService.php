<?php

namespace App\Services;

use App\Core\Database;

/**
 * Previsão semanal acumulativa por obra e categoria.
 *
 * Três grandezas distintas por categoria e semana (nunca somadas entre si):
 *  - ORÇADO: custo previsto da categoria distribuído igualmente entre as semanas da obra.
 *  - PREVISTO: despesas do Nibo ainda NÃO pagas (em aberto ou vencidas), pela data prevista
 *    (forecast_date; se ausente, vencimento). Quando o lançamento é pago ele SAI do Previsto.
 *  - PAGO: despesas já baixadas, pela data real de pagamento (payment_date).
 *  Cada lançamento está em um só lugar: ou é Previsto (não pago) ou é Pago. Pagamento parcial se divide:
 *  a parte paga vai para Pago e o restante continua em Previsto.
 *
 * Saldo realizado:  saldo_n = saldo_(n-1) + orçado_n - pago_n   (só semanas até a corrente)
 * Saldo projetado:  desconta apenas o previsto AINDA NÃO PAGO (aberto); um lançamento pago
 *                   nunca é contado como previsto e pago ao mesmo tempo.
 *
 * Alerta 'realizado': saldo realizado da semana corrente < 0.
 * Alerta 'projetado': saldo realizado >= 0, mas o saldo projetado fica < 0 em alguma semana
 *                     corrente/futura (compromissos em aberto estouram o orçamento acumulado).
 *
 * calcular() é uma função pura (centavos inteiros) usada tanto na visão atual quanto no
 * congelamento de snapshots.
 */
class PrevisaoSemanalService
{
    // ─── Cálculo puro ────────────────────────────────────────────────

    public static function totalSemanas(string $ini, string $fim): int
    {
        $dias = self::diasEntre($ini, $fim) + 1;
        return $dias > 0 ? (int) ceil($dias / 7) : 0;
    }

    /** Semana (1..total) que contém $hoje; 0 se a obra ainda não começou. */
    public static function semanaAtual(string $ini, string $hoje, int $total): int
    {
        $dias = self::diasEntre($ini, $hoje);
        if ($dias < 0) return 0;
        return min($total, intdiv($dias, 7) + 1);
    }

    /** Divide $totalCents em $semanas partes; sobras de centavos vão para as primeiras semanas. */
    public static function distribuir(int $totalCents, int $semanas): array
    {
        if ($semanas <= 0) return [];
        $base = intdiv($totalCents, $semanas);
        $resto = $totalCents - $base * $semanas;
        $out = [];
        for ($i = 0; $i < $semanas; $i++) $out[] = $base + ($i < $resto ? 1 : 0);
        return $out;
    }

    /** Semana (1..total) de uma data dentro da janela da obra; 0 se fora. */
    public static function semanaDe(string $ini, string $fim, int $total, string $data): int
    {
        $data = substr($data, 0, 10);
        if ($data === '' || $data < $ini || $data > $fim) return 0;
        $w = intdiv(self::diasEntre($ini, $data), 7) + 1;
        return $w <= $total ? $w : 0;
    }

    /**
     * Calcula a previsão semanal de uma obra. Não acessa banco.
     *
     * @param array  $obra        id, data_inicio, data_prev_fim
     * @param array  $custos      linhas com categoria_nibo_id, categoria_nome, valor_previsto
     * @param array  $lancamentos lançamentos enriquecidos (PainelObrasService::dadosIntervalo)
     * @param string $hoje        Y-m-d de referência (data da sync, no caso de snapshots)
     * @return array{total:int,atual:int,pagos_sem_data:int,categorias:array<string,array>}
     *         Valores em centavos.
     */
    public static function calcular(array $obra, array $custos, array $lancamentos, string $hoje): array
    {
        $ini = (string) ($obra['data_inicio'] ?? '');
        $fim = (string) ($obra['data_prev_fim'] ?? '');
        $total = ($ini !== '' && $fim !== '' && $fim >= $ini) ? self::totalSemanas($ini, $fim) : 0;
        if ($total === 0) return ['total' => 0, 'atual' => 0, 'pagos_sem_data' => 0, 'categorias' => []];

        $atual = self::semanaAtual($ini, $hoje, $total);
        $ag = self::agrupar($lancamentos, (int) $obra['id'], $ini, $fim, $total, $custos);

        $categorias = [];
        foreach ($custos as $c) {
            $valorCents = (int) round(((float) $c['valor_previsto']) * 100);
            if ($valorCents <= 0) continue;
            $catId = (string) $c['categoria_nibo_id'];
            $orc = self::distribuir($valorCents, $total);
            $g = $ag['cats'][$catId] ?? ['prog' => [], 'aberto' => [], 'pago' => []];

            $pend = 0; // previsto não pago até a semana corrente
            for ($n = 1; $n <= $atual; $n++) $pend += $g['aberto'][$n] ?? 0;

            $semanas = [];
            $saldo = 0;          // realizado
            $proj = 0;           // projetado (carrega de semana em semana)
            $cumOrc = $cumPago = $cumAberto = 0;
            $cumOrcAte = $cumPagoAte = $cumAbertoAte = []; // acumulados por semana (para alertas)
            $saldoAtual = null;

            for ($n = 1; $n <= $total; $n++) {
                $o = $orc[$n - 1];
                $prog = $g['prog'][$n] ?? 0;
                $aberto = $g['aberto'][$n] ?? 0;
                $pago = $g['pago'][$n] ?? 0;
                $cumOrc += $o; $cumPago += $pago; $cumAberto += $aberto;
                $cumOrcAte[$n] = $cumOrc; $cumPagoAte[$n] = $cumPago; $cumAbertoAte[$n] = $cumAberto;
                [$si, $sf] = self::periodoSemana($ini, $fim, $n);

                if ($n <= $atual) {
                    $ant = $saldo;
                    $disp = $ant + $o;
                    $saldo = $disp - $pago;
                    $saldoSem = $saldo;
                    $proj = ($n === $atual) ? $saldo - $pend : $saldo;
                    if ($n === $atual) $saldoAtual = $saldo;
                } else {
                    $ant = $proj;
                    $disp = $ant + $o;
                    $proj = $disp - $aberto - $pago; // pago com data futura também é gasto esperado
                    $saldoSem = null;
                }

                $semanas[] = [
                    'n' => $n, 'inicio' => $si, 'fim' => $sf,
                    'orcado' => $o, 'saldo_anterior' => $ant, 'disponivel' => $disp,
                    'programado' => $prog, 'aberto' => $aberto, 'pago' => $pago,
                    'saldo' => $saldoSem, 'projetado' => $proj, 'atual' => $n === $atual,
                ];
            }

            // Alerta
            $alerta = null;
            if ($atual >= 1 && $saldoAtual < 0) {
                $s = $semanas[$atual - 1];
                $alerta = [
                    'tipo' => 'realizado', 'semana' => $atual, 'inicio' => $s['inicio'], 'fim' => $s['fim'],
                    'previsto_acum' => $cumOrcAte[$atual], 'gasto_acum' => $cumPagoAte[$atual],
                    'comprometido' => $pend, 'desvio' => $saldoAtual,
                ];
            } else {
                for ($n = max($atual, 1); $n <= $total; $n++) {
                    $s = $semanas[$n - 1];
                    if ($s['projetado'] < 0) {
                        $futuroPago = $cumPagoAte[$n] - ($atual >= 1 ? $cumPagoAte[$atual] : 0);
                        $alerta = [
                            'tipo' => 'projetado', 'semana' => $n, 'inicio' => $s['inicio'], 'fim' => $s['fim'],
                            'previsto_acum' => $cumOrcAte[$n],
                            'gasto_acum' => $atual >= 1 ? $cumPagoAte[$atual] : 0,
                            'comprometido' => $cumAbertoAte[$n] + $futuroPago,
                            'desvio' => $s['projetado'],
                        ];
                        break;
                    }
                }
            }

            $categorias[$catId] = [
                'nome' => $c['categoria_nome'], 'previsto_total' => $valorCents,
                'semanas' => $semanas, 'alerta' => $alerta,
            ];
        }

        return ['total' => $total, 'atual' => $atual, 'pagos_sem_data' => $ag['pagos_sem_data'], 'categorias' => $categorias];
    }

    /** Linhas planas (reais) na ordem das colunas de painel_obras_previsao_semanal (sem calculado_em). */
    public static function linhasPlanas(int $obraId, array $res): array
    {
        $out = [];
        foreach ($res['categorias'] as $catId => $c) {
            foreach ($c['semanas'] as $s) {
                $out[] = [
                    $obraId, (string) $catId, $c['nome'], $s['n'], $s['inicio'], $s['fim'],
                    $s['orcado'] / 100, $s['saldo_anterior'] / 100, $s['disponivel'] / 100,
                    $s['programado'] / 100, $s['aberto'] / 100, $s['pago'] / 100,
                    $s['saldo'] === null ? null : $s['saldo'] / 100, $s['projetado'] / 100,
                    $s['atual'] ? 1 : 0,
                ];
            }
        }
        return $out;
    }

    public const COLUNAS = [
        'obra_id', 'categoria_nibo_id', 'categoria_nome', 'semana_num', 'semana_inicio', 'semana_fim',
        'orcado_semana', 'saldo_anterior', 'disponivel', 'programado_semana', 'programado_aberto',
        'pago_semana', 'saldo_acumulado', 'saldo_projetado', 'is_atual',
    ];

    /** Alertas (reais) por categoria, no formato de painel_obras_alertas_orcamento. */
    public static function alertasPlanos(array $res): array
    {
        $out = [];
        foreach ($res['categorias'] as $catId => $c) {
            if (!$c['alerta']) continue;
            $a = $c['alerta'];
            $out[(string) $catId] = [
                'categoria_nome' => $c['nome'], 'tipo' => $a['tipo'],
                'semana_num' => $a['semana'], 'semana_inicio' => $a['inicio'], 'semana_fim' => $a['fim'],
                'previsto_acumulado' => $a['previsto_acum'] / 100, 'gasto_acumulado' => $a['gasto_acum'] / 100,
                'comprometido' => $a['comprometido'] / 100, 'desvio' => $a['desvio'] / 100,
            ];
        }
        return $out;
    }

    // ─── Visão atual (persistida, recalculável) ──────────────────────

    /** Recalcula todas as obras ativas com datas (usado após cada sync do Nibo). */
    public static function recalcularTodas(): int
    {
        $obras = Database::fetchAll(
            "SELECT id, company, data_inicio, data_prev_fim FROM painel_obras
             WHERE situacao = 'Ativa' AND data_inicio IS NOT NULL AND data_prev_fim IS NOT NULL"
        );
        $porCompany = [];
        foreach ($obras as $o) $porCompany[$o['company'] ?: 'brooks'][] = $o;

        $n = 0;
        foreach ($porCompany as $company => $lista) {
            $lancs = self::carregarLancamentos($company, $lista);
            foreach ($lista as $o) {
                try {
                    self::recalcularObra((int) $o['id'], $lancs);
                    $n++;
                } catch (\Throwable $e) {
                    error_log('[PrevisaoSemanal] Erro na obra ' . $o['id'] . ': ' . $e->getMessage());
                }
            }
        }
        return $n;
    }

    /** Recalcula só se houver sync novo, virada de dia ou obra sem cálculo. */
    public static function garantirAtualizado(): void
    {
        $hoje = date('Y-m-d');
        $obras = Database::fetchAll(
            "SELECT o.id,
                    (SELECT MAX(p.calculado_em) FROM painel_obras_previsao_semanal p WHERE p.obra_id = o.id) AS ult
             FROM painel_obras o
             WHERE o.situacao = 'Ativa' AND o.data_inicio IS NOT NULL AND o.data_prev_fim IS NOT NULL
               AND o.data_inicio <= o.data_prev_fim
               AND EXISTS (SELECT 1 FROM painel_obras_custos_previstos c WHERE c.obra_id = o.id AND c.valor_previsto > 0)"
        );
        if (!$obras) return;

        $snap = Database::fetch("SELECT MAX(created_at) AS m FROM nibo_sync_snapshots");
        $snapEm = $snap['m'] ?? null;

        foreach ($obras as $o) {
            $ult = $o['ult'];
            if (!$ult || substr($ult, 0, 10) < $hoje || ($snapEm && $ult < $snapEm)) {
                self::recalcularTodas();
                return;
            }
        }
    }

    /** Recalcula e persiste a visão atual de uma obra. Não toca em snapshots congelados. */
    public static function recalcularObra(int $obraId, ?array $lancamentos = null): void
    {
        $obra = Database::fetch("SELECT id, company, situacao, data_inicio, data_prev_fim FROM painel_obras WHERE id = ?", [$obraId]);
        if (!$obra) return;

        $custos = Database::fetchAll(
            "SELECT categoria_nibo_id, categoria_nome, valor_previsto FROM painel_obras_custos_previstos
             WHERE obra_id = ? AND valor_previsto > 0",
            [$obraId]
        );

        $temJanela = $obra['data_inicio'] && $obra['data_prev_fim'] && $obra['data_prev_fim'] >= $obra['data_inicio'];
        if (!$custos || !$temJanela) {
            self::substituirLinhas($obraId, []);
            self::sincronizarAlertas($obraId, []);
            return;
        }

        if ($lancamentos === null) {
            $lancamentos = self::carregarLancamentos($obra['company'] ?: 'brooks', [$obra]);
        }

        $res = self::calcular($obra, $custos, $lancamentos, date('Y-m-d'));
        $agora = date('Y-m-d H:i:s');
        $linhas = array_map(fn($l) => array_merge($l, [$agora]), self::linhasPlanas($obraId, $res));
        self::substituirLinhas($obraId, $linhas);
        self::sincronizarAlertas($obraId, self::alertasPlanos($res));
    }

    // ─── Leitura ─────────────────────────────────────────────────────

    /** Detalhe semanal da obra (visão atual), para a aba Custos Previstos. */
    public static function detalhe(int $obraId): array
    {
        $obra = Database::fetch("SELECT id, data_inicio, data_prev_fim FROM painel_obras WHERE id = ?", [$obraId]);
        if (!$obra) return ['ok' => false, 'error' => 'Obra não encontrada.'];

        $ini = $obra['data_inicio'];
        $fim = $obra['data_prev_fim'];
        if (!$ini || !$fim || $fim < $ini) {
            return ['ok' => true, 'configurada' => false, 'motivo' => 'Informe a data de início e a data prevista de fim da obra.', 'categorias' => []];
        }

        self::recalcularObra($obraId);

        $custos = Database::fetchAll(
            "SELECT categoria_nibo_id, categoria_nome, valor_previsto FROM painel_obras_custos_previstos
             WHERE obra_id = ? AND valor_previsto > 0 ORDER BY categoria_nome",
            [$obraId]
        );
        $rows = Database::fetchAll(
            "SELECT * FROM painel_obras_previsao_semanal WHERE obra_id = ? ORDER BY categoria_nome, semana_num",
            [$obraId]
        );
        $alertas = [];
        foreach (Database::fetchAll("SELECT categoria_nibo_id, tipo FROM painel_obras_alertas_orcamento WHERE obra_id = ? AND status = 'ativo'", [$obraId]) as $a) {
            $alertas[$a['categoria_nibo_id']] = $a['tipo'];
        }
        return self::montarDetalhe($ini, $fim, $custos, $rows, $alertas);
    }

    /**
     * Monta o detalhe a partir de linhas com as colunas de painel_obras_previsao_semanal
     * (usado na visão atual e nos snapshots congelados).
     */
    public static function montarDetalhe(string $ini, string $fim, array $custos, array $rows, array $alertas): array
    {
        $total = self::totalSemanas($ini, $fim);
        $porCat = [];
        $atual = 0;
        foreach ($rows as $r) {
            $porCat[$r['categoria_nibo_id']][] = $r;
            if (!empty($r['is_atual'])) $atual = max($atual, (int) $r['semana_num']);
        }

        $cats = [];
        foreach ($custos as $c) {
            $id = (string) $c['categoria_nibo_id'];
            $semanas = [];
            foreach ($porCat[$id] ?? [] as $r) {
                $semanas[] = [
                    'semana' => (int) $r['semana_num'], 'inicio' => $r['semana_inicio'], 'fim' => $r['semana_fim'],
                    'orcado' => (float) $r['orcado_semana'], 'saldo_anterior' => (float) $r['saldo_anterior'],
                    'disponivel' => (float) $r['disponivel'],
                    'previsto' => (float) $r['programado_aberto'],   // só o ainda não pago; o que foi pago está em 'pago'
                    'pago' => (float) $r['pago_semana'],
                    'saldo' => $r['saldo_acumulado'] === null ? null : (float) $r['saldo_acumulado'],
                    'projetado' => $r['saldo_projetado'] === null ? null : (float) $r['saldo_projetado'],
                    'atual' => !empty($r['is_atual']),
                    'futura' => $r['saldo_acumulado'] === null,
                ];
            }
            $saldoAtual = null;
            foreach ($semanas as $s) if ($s['atual']) $saldoAtual = $s['saldo'];
            $cats[] = [
                'categoria_id' => $id,
                'categoria' => $c['categoria_nome'],
                'previsto_total' => (float) $c['valor_previsto'],
                'orcado_semanal' => $total > 0 ? round(((float) $c['valor_previsto']) / $total, 2) : 0,
                'saldo_atual' => $saldoAtual,
                'alerta' => $alertas[$id] ?? null,
                'semanas' => $semanas,
            ];
        }

        return [
            'ok' => true, 'configurada' => true,
            'inicio' => $ini, 'fim' => $fim,
            'total_semanas' => $total, 'semana_atual' => $atual,
            'categorias' => $cats,
        ];
    }

    /** Alertas ativos de obras ativas (visão atual), para o dashboard. */
    public static function alertasAtivos(?string $company = null): array
    {
        // Mesmo recorte do snapshot congelado: só obras da empresa selecionada
        $rows = Database::fetchAll(
            "SELECT a.*, o.codigo, o.nome AS obra_nome
             FROM painel_obras_alertas_orcamento a
             INNER JOIN painel_obras o ON o.id = a.obra_id
             WHERE a.status = 'ativo' AND o.situacao = 'Ativa'" . ($company ? " AND o.company = ?" : "") . "
             ORDER BY a.tipo ASC, a.desvio ASC",
            $company ? [$company] : []
        );
        return array_map(fn($a) => [
            'obra_id' => (int) $a['obra_id'],
            'obra_codigo' => $a['codigo'],
            'obra_nome' => $a['obra_nome'],
            'categoria_id' => $a['categoria_nibo_id'],
            'categoria' => $a['categoria_nome'],
            'tipo' => $a['tipo'],
            'semana' => (int) $a['semana_num'],
            'semana_inicio' => $a['semana_inicio'],
            'semana_fim' => $a['semana_fim'],
            'previsto_acumulado' => (float) $a['previsto_acumulado'],
            'gasto_acumulado' => (float) $a['gasto_acumulado'],
            'comprometido' => (float) $a['comprometido'],
            'desvio' => (float) $a['desvio'],
            'detectado_em' => $a['detectado_em'],
        ], $rows);
    }

    /** Orçado x previsto x pago da semana de referência (is_atual) agregado por nome de categoria. */
    public static function semanaAtualPorCategoria(?array $rows = null, ?string $company = null): array
    {
        if ($rows === null) {
            $rows = Database::fetchAll(
                "SELECT p.categoria_nome, p.orcado_semana, p.programado_aberto, p.pago_semana, p.saldo_acumulado,
                        p.saldo_projetado, p.is_atual
                 FROM painel_obras_previsao_semanal p
                 INNER JOIN painel_obras o ON o.id = p.obra_id
                 WHERE p.is_atual = 1 AND o.situacao = 'Ativa'" . ($company ? " AND o.company = ?" : ""),
                $company ? [$company] : []
            );
        }
        $out = [];
        foreach ($rows as $r) {
            if (empty($r['is_atual'])) continue;
            $k = $r['categoria_nome'];
            $out[$k] ??= ['orcado' => 0.0, 'previsto' => 0.0, 'pago' => 0.0, 'saldo' => 0.0, 'projetado' => 0.0, 'obras' => 0];
            $out[$k]['orcado'] += (float) $r['orcado_semana'];
            $out[$k]['previsto'] += (float) ($r['programado_aberto'] ?? 0);
            $out[$k]['pago'] += (float) $r['pago_semana'];
            $out[$k]['saldo'] += (float) ($r['saldo_acumulado'] ?? 0);
            $out[$k]['projetado'] += (float) ($r['saldo_projetado'] ?? 0);
            $out[$k]['obras']++;
        }
        foreach ($out as &$v) foreach (['orcado', 'previsto', 'pago', 'saldo', 'projetado'] as $f) $v[$f] = round($v[$f], 2);
        return $out;
    }

    // ─── Internos ────────────────────────────────────────────────────

    /**
     * Lançamentos (do snapshot atual ou do contexto congelado) cobrindo as janelas das obras
     * e, opcionalmente, um período extra (ex.: semana consultada, mesmo antes do início das obras).
     */
    public static function carregarLancamentos(string $company, array $obras, ?string $extraIni = null, ?string $extraFim = null): array
    {
        if (!PainelObrasService::snapshotAtual($company)) return [];
        $min = $extraIni;
        $max = $extraFim;
        foreach ($obras as $o) {
            if (!$o['data_inicio'] || !$o['data_prev_fim']) continue;
            if ($min === null || $o['data_inicio'] < $min) $min = $o['data_inicio'];
            if ($max === null || $o['data_prev_fim'] > $max) $max = $o['data_prev_fim'];
        }
        if ($min === null || $max === null) return [];
        return PainelObrasService::dadosIntervalo($company, $min, $max)['lancamentos'] ?? [];
    }

    /** Nome normalizado para comparar categorias (o Mês agrupa por NOME; ids podem diferir entre grupos do Nibo). */
    private static function normNome(?string $n): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $n)));
    }

    /** Índices id/nome das categorias com custo previsto de uma obra. */
    private static function indiceCustos(array $custos): array
    {
        $porId = $porNome = [];
        foreach ($custos as $c) {
            $id = (string) $c['categoria_nibo_id'];
            $porId[$id] = $id;
            $nm = self::normNome($c['categoria_nome'] ?? '');
            if ($nm !== '' && !isset($porNome[$nm])) $porNome[$nm] = $id;
        }
        return [$porId, $porNome];
    }

    /**
     * Categoria (id do custo previsto) a que um lançamento pertence: primeiro pelo ID do Nibo; se a obra não
     * tem custo com esse ID, pelo NOME da categoria (mesma regra do quadro mensal, que compara por nome).
     */
    private static function resolverCategoria(array $l, array $porId, array $porNome): ?string
    {
        $id = (string) ($l['categoria_nibo_id'] ?? '');
        if ($id !== '' && isset($porId[$id])) return $id;
        foreach ([$l['categoria_obra'] ?? null, $l['categoria_nibo_nome'] ?? null] as $nome) {
            $nm = self::normNome($nome);
            if ($nm !== '' && isset($porNome[$nm])) return $porNome[$nm];
        }
        return null;
    }

    /**
     * Eventos de UM lançamento (centavos), sem considerar janela de obra nem categoria:
     *  prog   = valor integral, na data prevista (forecast_date; senão vencimento)
     *  aberto = parte ainda não paga, na data prevista
     *  pago   = parte paga, na data real de cada baixa (GET /payments); sem baixa, usa a prevista e marca sem_data
     * "prog" e "pago" são perspectivas do mesmo lançamento; o saldo usa só "pago" (e "aberto" no projetado).
     *
     * @return array{ev:array<int,array{tipo:string,data:string,c:int,r:array}>,sem_data:int}
     */
    private static function eventosLancamento(array $l): array
    {
        $cents = (int) round(((float) $l['valor_rateado']) * 100);
        $paga = ($l['status'] ?? '') === 'paid';
        $dataPrev = substr((string) (($l['forecast_date'] ?? '') ?: ($l['due_date'] ?? '')), 0, 10);

        $baixas = is_array($l['payments'] ?? null) ? array_values($l['payments']) : [];
        $somaBaixas = 0.0;
        foreach ($baixas as $b) $somaBaixas += (float) ($b['value'] ?? 0);

        // Parte paga / em aberto (nunca somam mais que o valor do lançamento):
        //  pago (isPaid) = 100% pago; parcial (baixas) = baixas / valor total do agendamento; sem baixas = 100% aberto
        if ($paga) {
            $pagoC = $cents;
        } elseif ($baixas && $somaBaixas > 0) {
            $orig = (float) ($l['valor_original'] ?? 0);
            $pagoC = $orig > 0 ? (int) round($cents * min(1.0, $somaBaixas / $orig)) : 0;
        } else {
            $pagoC = 0;
        }
        $abertoC = $cents - $pagoC;

        $ev = [];
        $semData = 0;
        // Referência do lançamento de origem, usada para mostrar "quais lançamentos formam este número"
        $r = ['d' => (string) ($l['description'] ?? ''), 'f' => (string) ($l['contact_name'] ?? ''), 'st' => (string) ($l['status'] ?? ''), 'id' => $l['schedule_id'] ?? null];
        if ($dataPrev !== '') {
            $ev[] = ['tipo' => 'prog', 'data' => $dataPrev, 'c' => $cents, 'r' => $r];
            if ($abertoC > 0) $ev[] = ['tipo' => 'aberto', 'data' => $dataPrev, 'c' => $abertoC, 'r' => $r];
        }
        if ($pagoC > 0) {
            if ($baixas && $somaBaixas > 0) {
                $resto = $pagoC;
                $ultimo = count($baixas) - 1;
                foreach ($baixas as $i => $b) {
                    $parte = $i === $ultimo ? $resto : (int) round($pagoC * ((float) $b['value']) / $somaBaixas);
                    $resto -= $parte;
                    $d = substr((string) ($b['date'] ?? ''), 0, 10);
                    if ($d !== '') $ev[] = ['tipo' => 'pago', 'data' => $d, 'c' => $parte, 'r' => $r];
                }
            } else {
                $dataPago = substr((string) ($l['payment_date'] ?? ''), 0, 10);
                if ($dataPago === '') { $dataPago = $dataPrev; $semData++; }
                if ($dataPago !== '') $ev[] = ['tipo' => 'pago', 'data' => $dataPago, 'c' => $pagoC, 'r' => $r];
            }
        }
        return ['ev' => $ev, 'sem_data' => $semData];
    }

    /**
     * Eventos de uma obra: só despesas da obra, com categoria que tenha custo previsto, dentro da janela
     * [ini, fim] da obra (cada um com a semana 1..total).
     *
     * @return array{ev:array<int,array{cat:string,tipo:string,data:string,w:int,c:int}>,pagos_sem_data:int}
     */
    private static function eventos(array $lancs, int $obraId, string $ini, string $fim, int $total, array $custos): array
    {
        [$porId, $porNome] = self::indiceCustos($custos);
        $ev = [];
        $semData = 0;
        foreach ($lancs as $l) {
            if (($l['obra_id'] ?? null) != $obraId) continue;
            if (($l['type'] ?? '') !== 'payable') continue;
            $cat = self::resolverCategoria($l, $porId, $porNome);
            if ($cat === null) continue;

            $r = self::eventosLancamento($l);
            $semData += $r['sem_data'];
            foreach ($r['ev'] as $e) {
                $w = self::semanaDe($ini, $fim, $total, $e['data']);
                if ($w) $ev[] = ['cat' => $cat, 'tipo' => $e['tipo'], 'data' => $e['data'], 'w' => $w, 'c' => $e['c'], 'r' => $e['r']];
            }
        }
        return ['ev' => $ev, 'pagos_sem_data' => $semData];
    }

    /** Eventos agrupados por categoria e semana (base da previsão semanal acumulativa). */
    private static function agrupar(array $lancs, int $obraId, string $ini, string $fim, int $total, array $custos): array
    {
        $r = self::eventos($lancs, $obraId, $ini, $fim, $total, $custos);
        $cats = [];
        foreach ($r['ev'] as $e) {
            $cats[$e['cat']][$e['tipo']][$e['w']] = ($cats[$e['cat']][$e['tipo']][$e['w']] ?? 0) + $e['c'];
        }
        return ['cats' => $cats, 'pagos_sem_data' => $r['pagos_sem_data']];
    }

    /** Lista de detalhe ordenada por valor, limitada; o excedente vira {n, valor} para o total fechar. */
    private static function capLista(array $itens, int $max = 14): array
    {
        usort($itens, fn($a, $b) => $b['valor'] <=> $a['valor']);
        $mais = null;
        if (count($itens) > $max) {
            $resto = array_slice($itens, $max);
            $mais = ['n' => count($resto), 'valor' => round(array_sum(array_column($resto, 'valor')), 2)];
            $itens = array_slice($itens, 0, $max);
        }
        return ['itens' => $itens, 'mais' => $mais];
    }

    public const MOTIVOS_FORA = [
        'sem_obra'    => 'Sem obra vinculada',
        'sem_datas'   => 'Obra sem datas de início e fim',
        'sem_custo'   => 'Obra sem custo previsto para a categoria',
        'fora_janela' => 'Fora do período da obra (antes do início ou depois do fim)',
    ];

    /**
     * Valores do período que NÃO entram nas categorias do orçamento semanal, e por quê.
     * Responde "por que o Mês mostra um valor que a Semana/Dia não mostra".
     *
     * @return array{itens:array,totais:array}
     */
    public static function foraDoOrcamento(array $obras, array $custos, array $lancamentos, string $ini, string $fim, bool $bruto = false): array
    {
        $porObra = [];
        foreach ($obras as $o) $porObra[(int) $o['id']] = $o;
        $idx = [];
        foreach ($porObra as $id => $_) $idx[$id] = self::indiceCustos($custos[$id] ?? []);

        $acc = [];
        foreach ($lancamentos as $l) {
            if (($l['type'] ?? '') !== 'payable') continue;
            $oid = $l['obra_id'] ?? null;
            if ($oid !== null && !isset($porObra[(int) $oid])) continue;      // obra de outra empresa / oculta: fora do escopo

            $eventos = array_filter(self::eventosLancamento($l)['ev'], fn($e) => $e['data'] >= $ini && $e['data'] <= $fim);
            if (!$eventos) continue;

            $obra = $oid !== null ? $porObra[(int) $oid] : null;
            $oi = (string) ($obra['data_inicio'] ?? '');
            $of = (string) ($obra['data_prev_fim'] ?? '');
            $temJanela = $oi !== '' && $of !== '' && $of >= $oi;
            $cat = $obra ? self::resolverCategoria($l, ...$idx[(int) $oid]) : null;
            $nome = (string) (($l['categoria_obra'] ?? '') ?: ($l['categoria_nibo_nome'] ?? 'Sem categoria'));

            foreach ($eventos as $e) {
                if ($e['tipo'] === 'prog') continue;   // o valor integral não é exibido: Previsto = só o não pago
                if (!$obra) $motivo = 'sem_obra';
                elseif (!$temJanela) $motivo = 'sem_datas';
                elseif ($cat === null) $motivo = 'sem_custo';
                elseif ($e['data'] < $oi || $e['data'] > $of) $motivo = 'fora_janela';
                else continue; // alocado normalmente no orçamento semanal
                $k = $motivo . '|' . ($oid ?? 0) . '|' . $nome;
                $acc[$k] ??= ['motivo' => $motivo, 'obra_id' => $oid, 'obra_codigo' => $obra['codigo'] ?? null, 'obra_nome' => $obra['nome'] ?? null,
                              'categoria' => $nome, 'previsto' => 0, 'pago' => 0,
                              'det' => ['previsto' => [], 'pago' => []]];
                $campo = ['aberto' => 'previsto', 'pago' => 'pago'][$e['tipo']];
                $acc[$k][$campo] += $e['c'];
                $acc[$k]['det'][$campo][] = ['obra' => (string) ($obra['codigo'] ?? ''), 'desc' => $e['r']['d'], 'forn' => $e['r']['f'], 'data' => $e['data'], 'st' => $e['r']['st'], 'valor' => round($e['c'] / 100, 2)];
            }
        }

        $itens = [];
        $totais = [];
        foreach ($acc as $it) {
            foreach (['previsto', 'pago'] as $f) $it[$f] = round($it[$f] / 100, 2);
            if ($it['previsto'] == 0 && $it['pago'] == 0) continue;
            if (!$bruto) foreach (['previsto', 'pago'] as $campo) $it['det'][$campo] = self::capLista($it['det'][$campo], 10);
            $itens[] = $it;
            $t = &$totais[$it['motivo']];
            $t ??= ['previsto' => 0.0, 'pago' => 0.0];
            foreach (['previsto', 'pago'] as $f) $t[$f] = round($t[$f] + $it[$f], 2);
            unset($t);
        }
        $ordemTot = [];
        foreach (array_keys(self::MOTIVOS_FORA) as $m) if (isset($totais[$m])) $ordemTot[$m] = $totais[$m];
        $totais = $ordemTot;
        $ordem = array_flip(array_keys(self::MOTIVOS_FORA));
        usort($itens, fn($a, $b) => [$ordem[$a['motivo']], -($a['pago'] + $a['previsto'])] <=> [$ordem[$b['motivo']], -($b['pago'] + $b['previsto'])]);
        return ['itens' => $itens, 'totais' => $totais, 'rotulos' => self::MOTIVOS_FORA];
    }

    /** Orçamento acumulado (centavos) de uma lista semanal até o fim de $data; dentro da semana, pro rata diário. */
    private static function orcadoAte(array $orc, string $ini, string $fim, string $data): float
    {
        if ($data < $ini) return 0.0;
        $total = count($orc);
        $d = min($data, $fim);
        $n = min($total, intdiv(self::diasEntre($ini, $d), 7) + 1);
        $acum = 0.0;
        for ($i = 0; $i < $n - 1; $i++) $acum += $orc[$i];
        [$si, $sf] = self::periodoSemana($ini, $fim, $n);
        $len = self::diasEntre($si, $sf) + 1;
        $dias = self::diasEntre($si, $d) + 1;
        return $acum + $orc[$n - 1] * min($dias, $len) / $len;
    }

    /**
     * Consolidado por categoria de um período qualquer (semana civil ou dia), somando as obras.
     * Orçado = custo previsto ÷ nº de semanas da obra (distribuição igual, a partir da data de início).
     *   - $porSemana (visão Semana): cada semana da obra conta INTEIRA no período em que ela COMEÇA
     *     (assim 70.000 em 5 semanas = 14.000 por semana, sem cortar uma semana da obra em duas).
     *   - Dia: parcela diária (valor da semana ÷ dias da semana).
     * Previsto / Pago / A pagar = eventos do período (mesma regra da visão semanal).
     * Saldo = orçado acumulado - pago acumulado até o fim do período (ou até $hoje, se o período é atual).
     *
     * @param array $obras   linhas de painel_obras (id, data_inicio, data_prev_fim)
     * @param array $custos  obra_id => [ [categoria_nibo_id, categoria_nome, valor_previsto], ... ]
     * @return array<string,array<string,float|int|null>> nome da categoria => valores em reais
     */
    public static function consolidadoPeriodo(array $obras, array $custos, array $lancamentos, string $ini, string $fim, string $hoje, bool $porSemana = false): array
    {
        $out = [];
        $refSaldo = min($fim, $hoje);
        $nova = fn() => ['orcado' => 0.0, 'previsto' => 0.0, 'pago' => 0.0, 'saldo' => 0.0, 'orc_acum' => 0.0, 'pago_acum' => 0.0, 'saldo_ok' => 0, 'obras' => 0,
                         'proj' => 0.0, 'proj_orc' => 0.0, 'proj_pago' => 0.0, 'proj_aberto' => 0.0, 'proj_ok' => 0,
                         'det' => ['orcado' => [], 'previsto' => [], 'pago' => [], 'saldo' => [], 'proj' => []]];
        foreach ($obras as $o) {
            $oi = (string) ($o['data_inicio'] ?? '');
            $of = (string) ($o['data_prev_fim'] ?? '');
            if ($oi === '' || $of === '' || $of < $oi) continue;
            $lista = $custos[(int) $o['id']] ?? [];
            if (!$lista) continue;
            $total = self::totalSemanas($oi, $of);
            $ev = self::eventos($lancamentos, (int) $o['id'], $oi, $of, $total, $lista)['ev'];

            foreach ($lista as $c) {
                $valorC = (int) round(((float) $c['valor_previsto']) * 100);
                if ($valorC <= 0) continue;
                $catId = (string) $c['categoria_nibo_id'];
                $orc = self::distribuir($valorC, $total);

                $semN = [];
                if ($porSemana) {
                    $orcado = 0;
                    for ($n = 1; $n <= $total; $n++) {
                        $inicioSem = gmdate('Y-m-d', strtotime($oi . ' UTC') + ($n - 1) * 7 * 86400);
                        if ($inicioSem >= $ini && $inicioSem <= $fim) { $orcado += $orc[$n - 1]; $semN[] = $n; }
                    }
                } else {
                    $orcado = self::orcadoAte($orc, $oi, $of, $fim) - self::orcadoAte($orc, $oi, $of, gmdate('Y-m-d', strtotime($ini . ' UTC') - 86400));
                }
                $prev = $pago = $pagoAcum = $pagoFim = $abertoFim = 0;
                $k = $c['categoria_nome'];
                $linhas = ['previsto' => [], 'pago' => []];
                $mk = fn($e) => ['obra' => (string) $o['codigo'], 'desc' => $e['r']['d'], 'forn' => $e['r']['f'], 'data' => $e['data'], 'st' => $e['r']['st'], 'valor' => round($e['c'] / 100, 2)];
                foreach ($ev as $e) {
                    if ($e['cat'] !== $catId) continue;
                    $dentro = $e['data'] >= $ini && $e['data'] <= $fim;
                    // PREVISTO = parte ainda NÃO paga (evento 'aberto'); o valor integral ('prog') não é exibido
                    if ($e['data'] <= $fim) { if ($e['tipo'] === 'pago') $pagoFim += $e['c']; elseif ($e['tipo'] === 'aberto') $abertoFim += $e['c']; }
                    if ($e['tipo'] === 'aberto' && $dentro) { $prev += $e['c']; $linhas['previsto'][] = $mk($e); }
                    elseif ($e['tipo'] === 'pago') {
                        if ($dentro) { $pago += $e['c']; $linhas['pago'][] = $mk($e); }
                        if ($e['data'] <= $refSaldo) $pagoAcum += $e['c'];
                    }
                }
                if ($orcado <= 0 && $prev === 0 && $pago === 0) {
                    // categoria sem atividade no período e fora da janela da obra: não polui o quadro
                    $janela = $fim >= $oi && $ini <= $of;
                    if (!$janela) continue;
                }
                $out[$k] ??= $nova();
                $out[$k]['orcado'] += $orcado / 100;
                if ($orcado > 0) $out[$k]['det']['orcado'][] = ['obra' => (string) $o['codigo'], 'nome' => (string) $o['nome'], 'custo' => round($valorC / 100, 2), 'semanas' => $total, 'por_semana' => round($valorC / $total / 100, 2), 'sem_n' => $semN, 'valor' => round($orcado / 100, 2)];
                foreach ($linhas as $campo => $lista) foreach ($lista as $it) $out[$k]['det'][$campo][] = $it;
                $out[$k]['previsto'] += $prev / 100;
                $out[$k]['pago'] += $pago / 100;
                if ($ini <= $hoje && $refSaldo >= $oi) {
                    // Mesma regra da previsão semanal: o orçamento da semana inteira (da obra) já está disponível;
                    // abate só o que foi efetivamente pago até a data de referência.
                    $nSem = min($total, intdiv(self::diasEntre($oi, min($refSaldo, $of)), 7) + 1);
                    $orcDisp = array_sum(array_slice($orc, 0, $nSem));
                    $out[$k]['saldo'] += ($orcDisp - $pagoAcum) / 100;
                    $out[$k]['orc_acum'] += $orcDisp / 100;      // para explicar a conta do saldo na interface
                    $out[$k]['pago_acum'] += $pagoAcum / 100;
                    $out[$k]['det']['saldo'][] = ['obra' => (string) $o['codigo'], 'nome' => (string) $o['nome'], 'orc_acum' => round($orcDisp / 100, 2), 'pago_acum' => round($pagoAcum / 100, 2), 'valor' => round(($orcDisp - $pagoAcum) / 100, 2)];
                    $out[$k]['saldo_ok']++;
                }
                elseif ($fim >= $oi) {
                    // Período futuro: saldo PROJETADO = orçado acumulado − pago − previsto em aberto, até o fim do período
                    $nSemP = min($total, intdiv(self::diasEntre($oi, min($fim, $of)), 7) + 1);
                    $orcP = array_sum(array_slice($orc, 0, $nSemP));
                    $out[$k]['proj'] += ($orcP - $pagoFim - $abertoFim) / 100;
                    $out[$k]['proj_orc'] += $orcP / 100;
                    $out[$k]['proj_pago'] += $pagoFim / 100;
                    $out[$k]['proj_aberto'] += $abertoFim / 100;
                    $out[$k]['det']['proj'][] = ['obra' => (string) $o['codigo'], 'nome' => (string) $o['nome'], 'orc_acum' => round($orcP / 100, 2), 'pago_acum' => round($pagoFim / 100, 2), 'aberto_acum' => round($abertoFim / 100, 2), 'valor' => round(($orcP - $pagoFim - $abertoFim) / 100, 2)];
                    $out[$k]['proj_ok']++;
                }
                $out[$k]['obras']++;
            }
        }
        // Lançamentos de obras sem datas / sem custo previsto da categoria / fora do período da obra:
        // contam em Previsto (não pago) e Pago da categoria (igual ao quadro Mês), mas não geram Orçado nem Saldo.
        // Lançamentos sem obra vinculada ficam de fora, como no Mês (aparecem só no aviso informativo).
        $canon = [];
        foreach ($custos as $lista) foreach ($lista as $c) {
            $nm = self::normNome($c['categoria_nome'] ?? '');
            if ($nm !== '' && !isset($canon[$nm])) $canon[$nm] = $c['categoria_nome'];
        }
        foreach (self::foraDoOrcamento($obras, $custos, $lancamentos, $ini, $fim, true)['itens'] as $it) {
            if ($it['motivo'] === 'sem_obra') continue;
            $k = $canon[self::normNome($it['categoria'])] ?? $it['categoria'];
            $out[$k] ??= $nova();
            foreach (['previsto', 'pago'] as $campo) {
                $out[$k][$campo] += $it[$campo];
                foreach ($it['det'][$campo] as $d) { $d['sem'] = $it['motivo']; $out[$k]['det'][$campo][] = $d; }
            }
        }

        foreach ($out as &$v) {
            foreach (['orcado', 'previsto', 'pago', 'saldo', 'orc_acum', 'pago_acum', 'proj', 'proj_orc', 'proj_pago', 'proj_aberto'] as $f) $v[$f] = round($v[$f], 2);
            $v['saldo'] = $v['saldo_ok'] > 0 ? $v['saldo'] : null; // período futuro: sem saldo realizado
            $v['saldo_proj'] = ($v['saldo'] === null && $v['proj_ok'] > 0) ? $v['proj'] : null; // projeção para períodos futuros
            unset($v['saldo_ok'], $v['proj_ok'], $v['proj']);
            // detalhe: quais obras / lançamentos formam cada número (limitado; o excedente fica em "mais")
            foreach (['previsto', 'pago'] as $campo) $v['det'][$campo] = self::capLista($v['det'][$campo]);
            usort($v['det']['orcado'], fn($a, $b) => $b['valor'] <=> $a['valor']);
            usort($v['det']['saldo'], fn($a, $b) => $b['valor'] <=> $a['valor']);
            usort($v['det']['proj'], fn($a, $b) => $b['valor'] <=> $a['valor']);
        }
        unset($v);
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    private static function periodoSemana(string $ini, string $fim, int $n): array
    {
        $si = gmdate('Y-m-d', strtotime($ini . ' UTC') + ($n - 1) * 7 * 86400);
        $sf = gmdate('Y-m-d', strtotime($ini . ' UTC') + ($n * 7 - 1) * 86400);
        return [$si, min($sf, $fim)];
    }

    private static function diasEntre(string $a, string $b): int
    {
        return intdiv(strtotime(substr($b, 0, 10) . ' UTC') - strtotime(substr($a, 0, 10) . ' UTC'), 86400);
    }

    private static function substituirLinhas(int $obraId, array $linhas): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            Database::query("DELETE FROM painel_obras_previsao_semanal WHERE obra_id = ?", [$obraId]);
            $cols = implode(', ', self::COLUNAS) . ', calculado_em';
            $um = '(' . implode(',', array_fill(0, count(self::COLUNAS) + 1, '?')) . ')';
            foreach (array_chunk($linhas, 150) as $chunk) {
                $ph = implode(',', array_fill(0, count($chunk), $um));
                Database::query("INSERT INTO painel_obras_previsao_semanal ($cols) VALUES $ph", array_merge(...$chunk));
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Um registro por obra + categoria (sem duplicidade). Abre/atualiza quando há alerta
     * (realizado ou projetado) e resolve quando o desvio some ou a categoria deixa de ter previsão.
     * Afeta apenas a visão atual; snapshots congelados guardam os próprios alertas.
     */
    private static function sincronizarAlertas(int $obraId, array $alertas): void
    {
        $agora = date('Y-m-d H:i:s');
        $existentes = [];
        foreach (Database::fetchAll("SELECT * FROM painel_obras_alertas_orcamento WHERE obra_id = ?", [$obraId]) as $a) {
            $existentes[$a['categoria_nibo_id']] = $a;
        }

        foreach ($alertas as $catId => $a) {
            $ex = $existentes[$catId] ?? null;
            $dados = $a + ['status' => 'ativo', 'atualizado_em' => $agora, 'resolvido_em' => null];
            if (!$ex) {
                Database::insert('painel_obras_alertas_orcamento', $dados + [
                    'obra_id' => $obraId, 'categoria_nibo_id' => (string) $catId, 'detectado_em' => $agora,
                ]);
            } else {
                if ($ex['status'] === 'resolvido') $dados['detectado_em'] = $agora; // desvio reaberto
                Database::update('painel_obras_alertas_orcamento', $dados, 'id = ?', [$ex['id']]);
            }
        }

        foreach ($existentes as $catId => $ex) {
            if (!isset($alertas[$catId]) && $ex['status'] === 'ativo') {
                Database::update('painel_obras_alertas_orcamento',
                    ['status' => 'resolvido', 'resolvido_em' => $agora, 'atualizado_em' => $agora], 'id = ?', [$ex['id']]);
            }
        }
    }
}
