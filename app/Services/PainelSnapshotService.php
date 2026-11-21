<?php

namespace App\Services;

use App\Core\Database;
use App\Models\PainelCategoriasDepara;
use App\Models\PainelObra;

/**
 * Snapshots congelados do Painel de Obras (um por sincronização do Nibo).
 *
 * IMUTABILIDADE: esta classe só faz INSERT e SELECT em painel_obras_snapshots. Não existe
 * caminho de código que atualize, apague ou recalcule um snapshot existente. Os lançamentos
 * da época vêm de nibo_sync_snapshots (também insert-only); aqui ficam as demais entradas
 * (obras, custos previstos, de-para, categorias, recebimentos) e os resultados calculados
 * (semanal e alertas) exatamente como eram na data da sincronização.
 */
class PainelSnapshotService
{
    /** Congela o estado atual associado ao snapshot do Nibo recém-gravado. Idempotente: nunca sobrescreve. */
    public static function criar(int $niboSnapshotId, string $company): ?int
    {
        if (Database::fetch("SELECT id FROM painel_obras_snapshots WHERE nibo_snapshot_id = ?", [$niboSnapshotId])) {
            return null; // já existe: preservado como está
        }
        $nibo = Database::fetch("SELECT * FROM nibo_sync_snapshots WHERE id = ?", [$niboSnapshotId]);
        if (!$nibo) return null;

        $obras = PainelObra::allAtivas();
        $ids = array_map(fn($o) => (int) $o['id'], $obras);

        $custos = [];
        $recebido = [];
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            foreach (Database::fetchAll(
                "SELECT obra_id, categoria_nibo_id, categoria_nome, valor_previsto
                 FROM painel_obras_custos_previstos WHERE obra_id IN ($ph)", $ids) as $c) {
                $custos[(int) $c['obra_id']][] = [
                    'categoria_nibo_id' => $c['categoria_nibo_id'],
                    'categoria_nome' => $c['categoria_nome'],
                    'valor_previsto' => $c['valor_previsto'],
                ];
            }
            foreach (Database::fetchAll(
                "SELECT obra_id, COALESCE(SUM(valor),0) AS total FROM painel_obras_recebimentos
                 WHERE obra_id IN ($ph) GROUP BY obra_id", $ids) as $r) {
                $recebido[(int) $r['obra_id']] = (float) $r['total'];
            }
        }

        $ctx = [
            'snapshot' => $nibo,
            'obras' => $obras,
            'custos' => $custos,
            'depara' => PainelCategoriasDepara::mapCompleto(),
            'catmap' => NiboCategoriesService::mapa($company),
            'recebido' => $recebido,
        ];

        $hoje = substr((string) $nibo['created_at'], 0, 10);

        // Cálculo semanal (mesma função pura da visão atual) com os dados DESTA sincronização
        $semanal = [];
        $alertas = [];
        $pagosSemData = 0;
        $obrasDaEmpresa = array_values(array_filter(
            $obras,
            fn($o) => ($o['company'] ?: 'brooks') === $company && $o['data_inicio'] && $o['data_prev_fim']
        ));
        if ($obrasDaEmpresa) {
            PainelObrasService::comContexto($ctx, function () use ($obrasDaEmpresa, $company, $custos, $hoje, &$semanal, &$alertas, &$pagosSemData) {
                $lancs = PrevisaoSemanalService::carregarLancamentos($company, $obrasDaEmpresa);
                foreach ($obrasDaEmpresa as $o) {
                    $res = PrevisaoSemanalService::calcular($o, $custos[(int) $o['id']] ?? [], $lancs, $hoje);
                    $pagosSemData += $res['pagos_sem_data'];
                    foreach (PrevisaoSemanalService::linhasPlanas((int) $o['id'], $res) as $l) $semanal[] = $l;
                    foreach (PrevisaoSemanalService::alertasPlanos($res) as $catId => $a) {
                        $alertas[] = $a + [
                            'obra_id' => (int) $o['id'], 'obra_codigo' => $o['codigo'], 'obra_nome' => $o['nome'],
                            'categoria_id' => (string) $catId, 'categoria' => $a['categoria_nome'],
                            'semana' => $a['semana_num'], 'detectado_em' => $nibo['created_at'],
                        ];
                    }
                }
            });
        }

        $payload = [
            'v' => 1,
            'referencia' => $hoje,
            'nibo_snapshot_id' => $niboSnapshotId,
            'company' => $company,
            'snapshot_em' => $nibo['created_at'],
            'obras' => $obras,
            'custos' => $custos,
            'depara' => $ctx['depara'],
            'catmap' => $ctx['catmap'],
            'recebido' => $recebido,
            'semanal' => ['colunas' => PrevisaoSemanalService::COLUNAS, 'linhas' => $semanal],
            'alertas' => $alertas,
            'pagos_sem_data' => $pagosSemData,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $compressao = 'none';
        if (function_exists('gzcompress')) {
            $json = gzcompress($json, 6);
            $compressao = 'gz';
        }

        return Database::insert('painel_obras_snapshots', [
            'nibo_snapshot_id' => $niboSnapshotId,
            'company' => $company,
            'snapshot_em' => $nibo['created_at'],
            'compressao' => $compressao,
            'payload' => $json,
            'resumo_json' => json_encode([
                'obras' => count($obras), 'alertas' => count($alertas), 'pagos_sem_data' => $pagosSemData,
            ]),
        ]);
    }

    /** Lista de snapshots (mais recentes primeiro) para o filtro. */
    public static function listar(string $company, int $limite = 200): array
    {
        $limite = max(1, min(1000, $limite));
        return Database::fetchAll(
            "SELECT id, nibo_snapshot_id, company, snapshot_em, resumo_json
             FROM painel_obras_snapshots WHERE company = ? ORDER BY snapshot_em DESC, id DESC LIMIT {$limite}",
            [$company]
        );
    }

    /** @return array{meta:array,data:array}|null */
    public static function carregar(int $id): ?array
    {
        $row = Database::fetch("SELECT * FROM painel_obras_snapshots WHERE id = ?", [$id]);
        if (!$row) return null;
        $raw = $row['payload'];
        if ($row['compressao'] === 'gz') $raw = @gzuncompress($raw);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) return null;
        unset($row['payload']);
        return ['meta' => $row, 'data' => $data];
    }

    /** Contexto para PainelObrasService::comContexto(). */
    public static function contexto(array $snap): ?array
    {
        $nibo = Database::fetch("SELECT * FROM nibo_sync_snapshots WHERE id = ?", [(int) $snap['meta']['nibo_snapshot_id']]);
        if (!$nibo) return null;
        $d = $snap['data'];
        return [
            'snapshot' => $nibo,
            'obras' => $d['obras'] ?? [],
            'custos' => $d['custos'] ?? [],
            'depara' => $d['depara'] ?? [],
            'catmap' => $d['catmap'] ?? [],
            'recebido' => $d['recebido'] ?? [],
        ];
    }

    /** Linhas semanais do snapshot como arrays associativos. */
    public static function linhasSemanais(array $data, ?int $obraId = null): array
    {
        $cols = $data['semanal']['colunas'] ?? PrevisaoSemanalService::COLUNAS;
        $out = [];
        foreach ($data['semanal']['linhas'] ?? [] as $l) {
            $r = array_combine($cols, array_slice($l, 0, count($cols)));
            if ($obraId !== null && (int) $r['obra_id'] !== $obraId) continue;
            $out[] = $r;
        }
        return $out;
    }
}
