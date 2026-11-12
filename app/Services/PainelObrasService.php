<?php

namespace App\Services;

use App\Core\Database;
use App\Models\PainelObra;
use App\Models\PainelCategoriasDepara;
use App\Models\NiboSyncSnapshot;

/**
 * Enriquece os lançamentos do Nibo com categorias e vincula às obras.
 * NUNCA altera dados existentes — só lê snapshots e tabelas novas.
 */
class PainelObrasService
{
    // Regex para extrair código de obra da descrição ou centro de custo
    // Exemplos: P002, P003, CON-03, REF-07, ADM-105
    private const CODIGO_REGEX = '/\b((?:P|CON|REF|ADM|OBR|VET)-?\d{2,4})\b/i';

    /**
     * Lançamentos enriquecidos do período para o painel.
     * Fonte: último snapshot do Nibo + mapa de categorias + de-para + obras.
     *
     * @return array{ lancamentos: array, sem_obra: array, sem_categoria: array,
     *                obras_map: array, depara_map: array, cat_map: array }
     */
    public static function dados(string $company, string $competencia): array
    {
        // competencia = 'YYYY-MM' ou 'YYYY-Q1' etc.
        [$dataIni, $dataFim] = self::resolverPeriodo($competencia);

        // Carrega snapshot
        $snapshot = NiboSyncSnapshot::latest($company);
        $payload  = $snapshot ? NiboSyncSnapshot::decodePayload($snapshot) : [];
        $payables    = $payload['payables']    ?? [];
        $receivables = $payload['receivables'] ?? [];
        $todos = array_merge($payables, $receivables);

        // Mapas auxiliares
        $catMap    = NiboCategoriesService::mapa($company);
        $deParaMap = PainelCategoriasDepara::mapCompleto();
        $obras     = PainelObra::allAtivas();
        $obrasMap  = self::indexarObras($obras);

        $lancamentos = [];
        $semObra     = [];
        $semCat      = [];

        foreach ($todos as $item) {
            // Filtra pelo período
            $dataRef = $item['forecast_date'] ?? $item['due_date'] ?? '';
            if ($dataRef < $dataIni || $dataRef > $dataFim) continue;

            $catId   = $item['category_id'] ?? null;
            $catInfo = $catId ? ($catMap[$catId] ?? null) : null;
            $catNome = $catInfo['nome'] ?? ($item['category'] !== '—' ? $item['category'] : null);
            if (!$catNome) $catNome = null;

            // Categoria de obra via de-para
            $deParaEntry  = $catId ? ($deParaMap[$catId] ?? null) : null;
            $catObra      = $deParaEntry['categoria_obra'] ?? null;
            $ignorar      = $deParaEntry ? (bool) $deParaEntry['ignorar_no_painel'] : false;
            if ($ignorar) continue;

            if ($catId && !$catNome) $catNome = 'Sem categoria';

            // Vincula obra
            $obraId = self::resolverObra($item, $obrasMap);

            // Rateio: gera uma linha por categoria se houver split
            $linhas = self::expandirRateio($item, $catId, $catNome, $catInfo, $catObra, $deParaMap, $catMap);

            foreach ($linhas as $linha) {
                $linha['obra_id']    = $obraId;
                $lancamentos[] = $linha;
                if (!$obraId) $semObra[] = $linha;
                if (!$linha['categoria_nibo_id']) $semCat[] = $linha;
            }
        }

        return [
            'lancamentos'  => $lancamentos,
            'sem_obra'     => $semObra,
            'sem_categoria'=> $semCat,
            'obras_map'    => $obrasMap,
            'depara_map'   => $deParaMap,
            'cat_map'      => $catMap,
            'periodo'      => ['ini' => $dataIni, 'fim' => $dataFim],
            'synced_at'    => $snapshot['created_at'] ?? null,
        ];
    }

    /**
     * Totais gastos por categoria de obra para uma obra específica ou todas.
     */
    public static function gastosPorCategoria(array $lancamentos, ?int $obraId = null): array
    {
        $totais = [];
        foreach ($lancamentos as $l) {
            if ($obraId !== null && $l['obra_id'] !== $obraId) continue;
            if ($l['status'] !== 'paid') continue; // somente pagos
            $cat = $l['categoria_obra'] ?? 'Sem categoria / Não mapeado';
            $totais[$cat] = ($totais[$cat] ?? 0.0) + (float) $l['valor_rateado'];
        }
        arsort($totais);
        return $totais;
    }

    /**
     * Gasto total pago de uma obra.
     */
    public static function gastoObra(array $lancamentos, int $obraId): float
    {
        $total = 0.0;
        foreach ($lancamentos as $l) {
            if ($l['obra_id'] === $obraId && $l['status'] === 'paid') {
                $total += (float) $l['valor_rateado'];
            }
        }
        return $total;
    }

    /**
     * Extrai código de obra de um texto (descrição ou centro de custo).
     * Retorna o primeiro match ou null.
     */
    public static function extrairCodigo(string $texto): ?string
    {
        if (preg_match(self::CODIGO_REGEX, $texto, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    // ─── Privados ─────────────────────────────────────────────────────

    private static function resolverPeriodo(string $competencia): array
    {
        // Trimestre: 'YYYY-Q1' .. 'YYYY-Q4'
        if (preg_match('/^(\d{4})-Q([1-4])$/', $competencia, $m)) {
            $year = (int) $m[1];
            $q    = (int) $m[2];
            $ini  = sprintf('%04d-%02d-01', $year, ($q - 1) * 3 + 1);
            $fim  = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $q * 3)));
            return [$ini, $fim];
        }
        // Mês: 'YYYY-MM'
        if (preg_match('/^(\d{4})-(\d{2})$/', $competencia, $m)) {
            $ini = "{$m[1]}-{$m[2]}-01";
            $fim = date('Y-m-t', strtotime($ini));
            return [$ini, $fim];
        }
        // Fallback: mês atual
        $ini = date('Y-m-01');
        $fim = date('Y-m-t');
        return [$ini, $fim];
    }

    private static function indexarObras(array $obras): array
    {
        $map = [];
        foreach ($obras as $o) {
            // Por ID
            $map['id:' . $o['id']] = $o;
            // Por código
            $map['cod:' . strtoupper($o['codigo'])] = $o;
            // Por centros de custo
            $ccs = json_decode($o['centros_custo'] ?? '[]', true) ?: [];
            foreach ($ccs as $cc) {
                $map['cc:' . (string) $cc] = $o;
            }
            // Por códigos alternativos
            $alts = json_decode($o['codigos_alt'] ?? '[]', true) ?: [];
            foreach ($alts as $alt) {
                $map['cod:' . strtoupper($alt)] = $o;
            }
        }
        return $map;
    }

    private static function resolverObra(array $item, array $obrasMap): ?int
    {
        // 1. Tenta pelo ID do centro de custo
        $ccId = $item['cost_center_id'] ?? null;
        if ($ccId && isset($obrasMap['cc:' . $ccId])) {
            return (int) $obrasMap['cc:' . $ccId]['id'];
        }

        // 2. Tenta extrair código do centro de custo
        $ccNome = $item['cost_center'] ?? '';
        $cod = self::extrairCodigo($ccNome);
        if ($cod && isset($obrasMap['cod:' . $cod])) {
            return (int) $obrasMap['cod:' . $cod]['id'];
        }

        // 3. Tenta extrair código da descrição
        $desc = $item['description'] ?? '';
        $cod = self::extrairCodigo($desc);
        if ($cod && isset($obrasMap['cod:' . $cod])) {
            return (int) $obrasMap['cod:' . $cod]['id'];
        }

        return null;
    }

    private static function expandirRateio(
        array $item,
        ?string $catId,
        ?string $catNome,
        ?array $catInfo,
        ?string $catObra,
        array $deParaMap,
        array $catMap
    ): array {
        $valor     = (float) $item['value'];
        $status    = $item['status'];
        $isPaid    = $item['is_paid'];
        $base = [
            'schedule_id'      => $item['id'],
            'type'             => $item['type'],
            'due_date'         => $item['due_date'],
            'forecast_date'    => $item['forecast_date'],
            'contact_name'     => $item['contact_name'],
            'cost_center'      => $item['cost_center'],
            'cost_center_id'   => $item['cost_center_id'],
            'description'      => $item['description'],
            'status'           => $status,
            'is_paid'          => $isPaid,
        ];

        // Lançamento simples (sem rateio explícito)
        return [array_merge($base, [
            'categoria_nibo_id'   => $catId,
            'categoria_nibo_nome' => $catNome ?? 'Sem categoria',
            'categoria_excluida'  => $catInfo['excluida'] ?? false,
            'grupo_nome'          => $catInfo['grupo_nome'] ?? null,
            'subgrupo_nome'       => $catInfo['subgrupo_nome'] ?? null,
            'tipo_categoria'      => $catInfo['tipo'] ?? null,
            'categoria_obra'      => $catObra,
            'valor_rateado'       => $valor,
        ])];
    }
}
