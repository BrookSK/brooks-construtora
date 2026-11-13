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

        error_log('[PainelObras] ===== INICIANDO BUSCA DE DADOS =====');
        error_log('[PainelObras] Company: ' . $company . ', Competência: ' . $competencia);
        error_log('[PainelObras] Período: ' . $dataIni . ' a ' . $dataFim);

        // Carrega snapshot
        $snapshot = NiboSyncSnapshot::latest($company);
        $payload  = $snapshot ? NiboSyncSnapshot::decodePayload($snapshot) : [];
        $payables    = $payload['payables']    ?? [];
        $receivables = $payload['receivables'] ?? [];
        $todos = array_merge($payables, $receivables);

        error_log('[PainelObras] Total de lançamentos no snapshot: ' . count($todos));
        error_log('[PainelObras] Payables: ' . count($payables) . ', Receivables: ' . count($receivables));

        // Mapas auxiliares
        $catMap    = NiboCategoriesService::mapa($company);
        $deParaMap = PainelCategoriasDepara::mapCompleto();
        $obras     = PainelObra::allAtivas();
        $obrasMap  = self::indexarObras($obras);

        error_log('[PainelObras] Categorias Nibo: ' . count($catMap));
        error_log('[PainelObras] Mapeamentos de-para: ' . count($deParaMap));
        error_log('[PainelObras] Obras ativas: ' . count($obras));

        $lancamentos = [];
        $semObra     = [];
        $semCat      = [];
        $filtrados   = 0;

        foreach ($todos as $item) {
            // Filtra pelo período
            $dataRef = $item['forecast_date'] ?? $item['due_date'] ?? '';
            if ($dataRef < $dataIni || $dataRef > $dataFim) {
                $filtrados++;
                continue;
            }

            $catId   = $item['category_id'] ?? null;
            $catInfo = $catId ? ($catMap[$catId] ?? null) : null;
            $catNome = $catInfo['nome'] ?? ($item['category'] !== '—' ? $item['category'] : null);

            // NOVA LÓGICA: Usa categoria do Nibo diretamente como categoria de obra
            // Se houver mapeamento de-para, usa o mapeado, senão usa a categoria Nibo original
            $deParaEntry  = $catId ? ($deParaMap[$catId] ?? null) : null;
            
            // Determina categoria de obra: prioridade para mapeamento, depois catNome
            if ($deParaEntry && !empty($deParaEntry['categoria_obra'])) {
                $catObra = $deParaEntry['categoria_obra'];
            } elseif ($catNome) {
                $catObra = $catNome; // Usa nome da categoria Nibo diretamente
            } else {
                $catObra = 'Sem categoria / Não mapeado';
            }
            
            $ignorar = $deParaEntry ? (bool) $deParaEntry['ignorar_no_painel'] : false;
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

        error_log('[PainelObras] Lançamentos filtrados pelo período: ' . $filtrados);
        error_log('[PainelObras] Lançamentos processados: ' . count($lancamentos));
        error_log('[PainelObras] Sem obra vinculada: ' . count($semObra));
        error_log('[PainelObras] Sem categoria: ' . count($semCat));
        error_log('[PainelObras] ===== FIM DA BUSCA DE DADOS =====');

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
     * Detecta obras a partir dos centros de custo do Nibo e cria registros automáticos
     * se não existirem. Retorna lista de obras com seus centros de custo vinculados.
     */
    public static function sincronizarObrasDoNibo(string $company = 'brooks'): array
    {
        // Busca centros de custo do último snapshot
        $snapshot = NiboSyncSnapshot::latest($company);
        if (!$snapshot) {
            return ['criadas' => 0, 'atualizadas' => 0, 'obras' => []];
        }
        
        $payload = NiboSyncSnapshot::decodePayload($snapshot);
        $centrosCusto = $payload['filters']['costcenters'] ?? [];
        
        $criadas = 0;
        $atualizadas = 0;
        $obras = [];
        
        foreach ($centrosCusto as $cc) {
            $ccId = $cc['id'] ?? null;
            $ccNome = $cc['name'] ?? '';
            
            if (!$ccId || !$ccNome || $ccNome === '—') continue;
            
            // Verifica se já existe obra com este centro de custo
            $obraExistente = Database::fetch(
                "SELECT * FROM painel_obras WHERE JSON_CONTAINS(centros_custo, ?)",
                [json_encode((string)$ccId)]
            );
            
            if ($obraExistente) {
                $obras[] = $obraExistente;
                continue;
            }
            
            // Extrai código da obra do nome do centro de custo
            // Exemplos: "P007", "P031", "P004.2"
            $codigo = self::extrairCodigo($ccNome);
            if (!$codigo) {
                // Se não conseguir extrair, usa primeiras letras + ID
                $codigo = 'CC-' . substr(preg_replace('/[^A-Z0-9]/i', '', $ccNome), 0, 6);
            }
            
            // Verifica se já existe obra com este código
            $porCodigo = Database::fetch(
                "SELECT * FROM painel_obras WHERE codigo = ?",
                [$codigo]
            );
            
            if ($porCodigo) {
                // Adiciona o centro de custo à obra existente
                $ccsExistentes = json_decode($porCodigo['centros_custo'] ?? '[]', true) ?: [];
                if (!in_array($ccId, $ccsExistentes)) {
                    $ccsExistentes[] = (string)$ccId;
                    Database::query(
                        "UPDATE painel_obras SET centros_custo = ? WHERE id = ?",
                        [json_encode($ccsExistentes), $porCodigo['id']]
                    );
                    $atualizadas++;
                }
                $obras[] = $porCodigo;
                continue;
            }
            
            // Cria nova obra
            $tipo = self::detectarTipo($ccNome);
            $cliente = self::extrairCliente($ccNome);
            
            $obraId = Database::insert('painel_obras', [
                'codigo' => $codigo,
                'nome' => $ccNome,
                'tipo' => $tipo,
                'cliente' => $cliente,
                'centros_custo' => json_encode([(string)$ccId]),
                'valor_contratado' => 0, // Será preenchido manualmente depois
                'data_contrato' => date('Y-m-d'),
                'situacao' => 'Ativa',
                'observacoes' => 'Criada automaticamente a partir do centro de custo do Nibo',
                'created_by' => null,
            ]);
            
            $criadas++;
            
            $novaObra = Database::fetch("SELECT * FROM painel_obras WHERE id = ?", [$obraId]);
            if ($novaObra) {
                $obras[] = $novaObra;
            }
        }
        
        return [
            'criadas' => $criadas,
            'atualizadas' => $atualizadas,
            'obras' => $obras,
        ];
    }
    
    /**
     * Detecta o tipo de obra pelo nome
     */
    private static function detectarTipo(string $nome): string
    {
        $nome = strtolower($nome);
        
        if (strpos($nome, 'reforma') !== false || strpos($nome, 'ref') !== false) {
            return 'Reforma';
        }
        
        if (strpos($nome, 'construção') !== false || 
            strpos($nome, 'construcao') !== false ||
            strpos($nome, 'const') !== false ||
            strpos($nome, 'obra') !== false) {
            return 'Construção';
        }
        
        return 'Reforma'; // Default
    }
    
    /**
     * Extrai nome do cliente do nome do centro de custo
     * Exemplo: "Joia Bergamo - João Doria - P031" → "João Doria"
     */
    private static function extrairCliente(string $nome): ?string
    {
        // Padrão: "Empreendimento - Cliente - Código"
        // Remove o código do final
        $semCodigo = preg_replace('/\s*-\s*[A-Z0-9\-]{2,10}$/i', '', $nome);
        
        // Pega a parte do meio (entre traços)
        $partes = explode(' - ', $semCodigo);
        
        if (count($partes) >= 2) {
            // Retorna a segunda parte (cliente)
            return trim($partes[1]);
        }
        
        if (count($partes) === 1) {
            // Se só tem uma parte, é o nome do cliente/empreendimento
            return trim($partes[0]);
        }
        
        return null;
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
        $linha = array_merge($base, [
            'categoria_nibo_id'   => $catId,
            'categoria_nibo_nome' => $catNome ?? 'Sem categoria',
            'categoria_excluida'  => $catInfo['excluida'] ?? false,
            'grupo_nome'          => $catInfo['grupo_nome'] ?? null,
            'subgrupo_nome'       => $catInfo['subgrupo_nome'] ?? null,
            'tipo_categoria'      => $catInfo['tipo'] ?? null,
            'categoria_obra'      => $catObra,
            'valor_rateado'       => $valor,
        ]);
        
        // Log para debug
        if (empty($catObra)) {
            error_log('[PainelObras] ALERTA: Lançamento SEM categoria_obra - catNome: ' . ($catNome ?? 'NULL') . ', catId: ' . ($catId ?? 'NULL') . ', description: ' . ($item['description'] ?? 'N/A'));
        }
        
        return [$linha];
    }

    /**
     * Obtém dados consolidados do dashboard - 100% BASEADO NO NIBO
     * TODOS os dados vêm da API do Nibo, exceto:
     * - valor_contratado (campo do cadastro)
     * - numero_parcelas (campo do cadastro, quando não houver no Nibo)
     */
    public static function getDashboardData(string $periodo, string $company = 'completo'): array
    {
        // Busca dados enriquecidos DO NIBO
        $data = self::dados($company, $periodo);
        $lancamentos = $data['lancamentos']; // TODOS os lançamentos do Nibo filtrados
        $obrasMap = $data['obras_map'];
        $catMap = $data['cat_map']; // Categorias DO NIBO
        $deParaMap = $data['depara_map']; // Mapeamento
        
        // Filtra apenas obras únicas
        $obras = [];
        $obrasVistas = [];
        foreach ($obrasMap as $key => $obra) {
            $id = $obra['id'];
            if (!in_array($id, $obrasVistas)) {
                $obras[] = $obra;
                $obrasVistas[] = $id;
            }
        }
        
        // Se não houver obras cadastradas, retorna estrutura vazia
        if (empty($obras)) {
            return [
                'kpis' => [
                    'obras_total' => 0,
                    'contratado' => 0,
                    'orcado' => 0,
                    'gasto' => 0,
                    'a_pagar' => 0,
                    'desvio' => 0,
                    'obras_estouro' => 0,
                    'obras_atencao' => 0,
                ],
                'obras' => [],
                'obras_alerta' => [],
                'gasto_por_cat' => [],
                'orcado_por_cat' => [], // 🆕 Adicionado
                'a_pagar_por_cat' => [],
                'categorias_nibo' => [],
                'fornecedores' => [],
                'fluxo_mensal' => [],
                'lancamentos_por_obra' => [],
                'nao_mapeadas' => 0,
                'sem_obra_count' => count($data['sem_obra'] ?? []),
                'sem_obra_total' => 0,
                'sem_obra_lancamentos' => [],
                'synced_at' => $data['synced_at'],
                'periodo' => $data['periodo'],
            ];
        }
        
        // Processa cada obra COM DADOS 100% DO NIBO
        $obrasProcessadas = [];
        $totalContratado = $totalOrcado = $totalGasto = $totalAPagar = 0;
        $obrasComEstouro = $obrasComAtencao = 0;
        $gastoPorCat = [];
        $aPagarPorCat = [];
        $obrasAlerta = [];
        $conciliacaoLista = [];
        $fornecedoresPorObra = [];
        $lancamentosPorObra = [];
        
        foreach ($obras as $obra) {
            $obraId = $obra['id'];
            
            // ÚNICO CAMPO DO CADASTRO: valor_contratado
            $contratado = (float)($obra['valor_contratado'] ?? 0);
            
            // TUDO ABAIXO VEM DO NIBO ↓↓↓
            
            // Filtra lançamentos desta obra
            $lancsObra = array_filter($lancamentos, fn($l) => $l['obra_id'] === $obraId);
            
            // Gasto REAL do Nibo (somente status='paid' E type='payable' - DESPESAS)
            $gasto = 0;
            $gastoDetalhado = []; // Por categoria de obra
            $gastoDetalhado_nibo = []; // Por categoria Nibo (original)
            $fornecedores = [];
            $lancsGastos = [];
            
            foreach ($lancsObra as $l) {
                // IMPORTANTE: Só conta como GASTO se for DESPESA (payable) e estiver PAGO
                if ($l['status'] === 'paid' && $l['type'] === 'payable') {
                    $valor = (float)$l['valor_rateado'];
                    $gasto += $valor;
                    
                    // Agrupa por categoria DE OBRA
                    $catObra = $l['categoria_obra'] ?? 'Sem categoria / Não mapeado';
                    if (!isset($gastoDetalhado[$catObra])) {
                        $gastoDetalhado[$catObra] = 0;
                    }
                    $gastoDetalhado[$catObra] += $valor;
                    
                    // Agrupa por categoria NIBO (original)
                    $catNibo = $l['categoria_nibo_nome'] ?? 'Sem categoria';
                    if (!isset($gastoDetalhado_nibo[$catNibo])) {
                        $gastoDetalhado_nibo[$catNibo] = 0;
                    }
                    $gastoDetalhado_nibo[$catNibo] += $valor;
                    
                    // Fornecedores
                    $fornecedor = $l['contact_name'] ?? 'Não informado';
                    if (!isset($fornecedores[$fornecedor])) {
                        $fornecedores[$fornecedor] = 0;
                    }
                    $fornecedores[$fornecedor] += $valor;
                    
                    // Guarda lançamento completo
                    $lancsGastos[] = $l;
                }
            }
            
            // RECEITAS RECEBIDAS (receivable + paid)
            $receitasRecebidas = 0;
            $lancsReceitasRecebidas = [];
            
            foreach ($lancsObra as $l) {
                if ($l['type'] === 'receivable' && $l['status'] === 'paid') {
                    $valor = (float)$l['valor_rateado'];
                    $receitasRecebidas += $valor;
                    $lancsReceitasRecebidas[] = $l;
                }
            }
            
            // A PAGAR do Nibo (status='open' ou 'overdue' E type='payable' - DESPESAS)
            $aPagar = 0;
            $aPagarDetalhado = [];
            $lancsAPagar = [];
            
            foreach ($lancsObra as $l) {
                // Só conta como A PAGAR se for DESPESA (payable)
                if ($l['type'] === 'payable' && in_array($l['status'], ['open', 'overdue'])) {
                    $valor = (float)$l['valor_rateado'];
                    $aPagar += $valor;
                    
                    $catObra = $l['categoria_obra'] ?? 'Sem categoria / Não mapeado';
                    if (!isset($aPagarDetalhado[$catObra])) {
                        $aPagarDetalhado[$catObra] = 0;
                    }
                    $aPagarDetalhado[$catObra] += $valor;
                    
                    $lancsAPagar[] = $l;
                }
            }
            
            // A RECEBER do Nibo (receivables - RECEITAS)
            $aReceber = 0;
            $lancsAReceber = [];
            
            foreach ($lancsObra as $l) {
                if ($l['type'] === 'receivable' && in_array($l['status'], ['open', 'overdue'])) {
                    $valor = (float)$l['valor_rateado'];
                    $aReceber += $valor;
                    $lancsAReceber[] = $l;
                }
            }
            
            // ORÇADO = Soma dos custos previstos cadastrados na aba "Custos Previstos"
            $orcado = 0;
            $orcadoDetalhado = []; // Por categoria
            
            try {
                // Verifica se a tabela existe antes de fazer a query
                $tabelaExiste = Database::fetch("SHOW TABLES LIKE 'painel_obras_custos_previstos'");
                
                if ($tabelaExiste) {
                    $custosPrevistos = Database::fetchAll(
                        "SELECT categoria_nome, valor_previsto
                         FROM painel_obras_custos_previstos 
                         WHERE obra_id = ?",
                        [$obraId]
                    );
                    
                    foreach ($custosPrevistos as $custo) {
                        $valorPrevisto = (float)$custo['valor_previsto'];
                        $orcado += $valorPrevisto;
                        $orcadoDetalhado[$custo['categoria_nome']] = $valorPrevisto;
                    }
                    
                    error_log('[PainelObras] Obra ' . $obraId . ' - Orçado da tabela custos_previstos: R$ ' . number_format($orcado, 2, ',', '.'));
                } else {
                    error_log('[PainelObras] AVISO: Tabela painel_obras_custos_previstos não existe. Execute o SQL de migração.');
                }
            } catch (\Throwable $e) {
                error_log('[PainelObras] Erro ao buscar custos previstos da obra ' . $obraId . ': ' . $e->getMessage());
                $orcado = 0;
                $orcadoDetalhado = [];
            }
            
            // Se não houver custos previstos cadastrados, usa estimativa baseada no comprometido
            if ($orcado <= 0) {
                // Estimativa: gasto + a pagar + margem 10%
                $orcado = ($gasto + $aPagar) * 1.1;
                error_log('[PainelObras] Obra ' . $obraId . ' - Usando estimativa: R$ ' . number_format($orcado, 2, ',', '.'));
            }
            
            $desvio = $gasto - $orcado;
            $desvioPct = $orcado > 0 ? ($desvio / $orcado) * 100 : 0;
            
            // Situação
            $situacao = 'ok';
            if ($desvioPct > 5) {
                $situacao = 'danger';
                $obrasComEstouro++;
            } elseif ($desvioPct > 0) {
                $situacao = 'warn';
                $obrasComAtencao++;
            }
            
            // Top 3 categorias
            arsort($gastoDetalhado);
            $topCats = array_slice(array_keys($gastoDetalhado), 0, 3);
            
            // Top 3 fornecedores
            arsort($fornecedores);
            $topFornecedores = array_slice($fornecedores, 0, 3, true);
            
            $obraProcessada = [
                'id' => $obraId,
                'codigo' => $obra['codigo'] ?? 'OBRA-' . $obraId,
                'nome' => $obra['nome'] ?? 'Obra ' . $obraId,
                'tipo' => $obra['tipo'] ?? 'Reforma',
                'cliente' => $obra['cliente'] ?? null,
                'contratado' => $contratado, // ÚNICO campo do cadastro
                'orcado' => $orcado, // Soma dos custos previstos
                'orcado_cat' => $orcadoDetalhado, // Custos previstos por categoria
                'gasto' => $gasto, // DO NIBO
                'a_pagar' => $aPagar, // DO NIBO
                'a_receber' => $aReceber, // DO NIBO
                'desvio' => $desvio,
                'desvio_pct' => $desvioPct,
                'situacao' => $situacao,
                'gasto_cat' => $gastoDetalhado, // Categoria de obra
                'gasto_cat_nibo' => $gastoDetalhado_nibo, // Categoria Nibo original
                'a_pagar_cat' => $aPagarDetalhado,
                'top_cats' => $topCats,
                'top_fornecedores' => $topFornecedores, // DO NIBO
                'qtd_lancamentos_pagos' => count($lancsGastos), // DO NIBO
                'qtd_lancamentos_abertos' => count($lancsAPagar), // DO NIBO
                'qtd_lancamentos_receber' => count($lancsAReceber), // DO NIBO
            ];
            
            $obrasProcessadas[] = $obraProcessada;
            
            // Guarda lançamentos por obra para detalhamento
            $lancamentosPorObra[$obraId] = [
                'pagos' => $lancsGastos,
                'a_pagar' => $lancsAPagar,
                'a_receber' => $lancsAReceber,
            ];
            
            $totalContratado += $contratado;
            $totalOrcado += $orcado;
            $totalGasto += $gasto;
            $totalAPagar += $aPagar;
            
            // Obras em alerta
            if ($desvioPct > 0) {
                $obrasAlerta[] = $obraProcessada;
            }
            
            // Gasto por categoria (consolidado de todas as obras)
            foreach ($gastoDetalhado as $cat => $valorGasto) {
                if (!isset($gastoPorCat[$cat])) {
                    $gastoPorCat[$cat] = 0;
                }
                $gastoPorCat[$cat] += $valorGasto;
            }
            
            // A pagar por categoria (consolidado)
            foreach ($aPagarDetalhado as $cat => $valorAPagar) {
                if (!isset($aPagarPorCat[$cat])) {
                    $aPagarPorCat[$cat] = 0;
                }
                $aPagarPorCat[$cat] += $valorAPagar;
            }
        }
        
        // Ordena obras em alerta (piores primeiro)
        usort($obrasAlerta, fn($a, $b) => $b['desvio_pct'] <=> $a['desvio_pct']);
        $obrasAlerta = array_slice($obrasAlerta, 0, 3);
        
        // Ordena obras por código
        usort($obrasProcessadas, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));
        
        // Fluxo mensal (12 meses) - DO NIBO
        $fluxoMensal = self::getFluxoMensal($lancamentos);
        
        // Totais por fornecedor (todas as obras) - DO NIBO
        $fornecedorGlobal = [];
        foreach ($lancamentos as $l) {
            if ($l['status'] === 'paid') {
                $fornecedor = $l['contact_name'] ?? 'Não informado';
                if (!isset($fornecedorGlobal[$fornecedor])) {
                    $fornecedorGlobal[$fornecedor] = 0;
                }
                $fornecedorGlobal[$fornecedor] += (float)$l['valor_rateado'];
            }
        }
        arsort($fornecedorGlobal);
        $topFornecedoresGlobal = array_slice($fornecedorGlobal, 0, 10, true);
        
        // Totais por categoria Nibo (original) - DO NIBO
        $categoriaNiboGlobal = [];
        foreach ($lancamentos as $l) {
            if ($l['status'] === 'paid') {
                $catNibo = $l['categoria_nibo_nome'] ?? 'Sem categoria';
                if (!isset($categoriaNiboGlobal[$catNibo])) {
                    $categoriaNiboGlobal[$catNibo] = 0;
                }
                $categoriaNiboGlobal[$catNibo] += (float)$l['valor_rateado'];
            }
        }
        arsort($categoriaNiboGlobal);
        
        // ORÇADO POR CATEGORIA - busca da tabela de custos previstos
        $orcadoPorCat = [];
        try {
            // Verifica se a tabela existe
            $tabelaExiste = Database::fetch("SHOW TABLES LIKE 'painel_obras_custos_previstos'");
            
            if ($tabelaExiste) {
                $custosPrevistos = Database::fetchAll(
                    "SELECT 
                        cp.categoria_nome,
                        SUM(cp.valor_previsto) as total_previsto
                    FROM painel_obras_custos_previstos cp
                    INNER JOIN painel_obras o ON o.id = cp.obra_id
                    WHERE o.situacao = 'Ativa'
                    GROUP BY cp.categoria_nome"
                );
                
                foreach ($custosPrevistos as $custo) {
                    $orcadoPorCat[$custo['categoria_nome']] = (float)$custo['total_previsto'];
                }
                
                error_log('[PainelObras] Orçado por categoria (custos previstos): ' . count($orcadoPorCat) . ' categorias');
            } else {
                error_log('[PainelObras] AVISO: Tabela painel_obras_custos_previstos não existe. Execute o SQL de migração.');
            }
        } catch (\Throwable $e) {
            error_log('[PainelObras] Erro ao buscar custos previstos: ' . $e->getMessage());
            $orcadoPorCat = [];
        }
        
        // Categorias não mapeadas (DO NIBO)
        $naoMapeadas = count(array_unique(array_column(
            array_filter($data['sem_categoria'], fn($l) => $l['status'] === 'paid'),
            'categoria_nibo_id'
        )));
        
        // Lançamentos sem obra (DO NIBO)
        $semObra = $data['sem_obra'];
        $semObraTotal = array_sum(array_map(fn($l) => 
            $l['status'] === 'paid' ? (float)$l['valor_rateado'] : 0, 
            $semObra
        ));
        
        // Calcula total recebido de todas as obras
        $result = Database::fetch(
            "SELECT COALESCE(SUM(valor), 0) as total
             FROM painel_obras_recebimentos"
        );
        $totalRecebido = $result ? (float)$result['total'] : 0;
        
        $resultado = [
            'kpis' => [
                'obras_total' => count($obras),
                'contratado' => $totalContratado,
                'orcado' => $totalOrcado,
                'gasto' => $totalGasto, // DO NIBO
                'a_pagar' => $totalAPagar, // DO NIBO
                'desvio' => $totalGasto - $totalOrcado,
                'saldo' => $totalContratado - $totalGasto, // NOVO: Saldo disponível
                'recebido' => $totalRecebido, // NOVO: Total recebido de clientes
                'obras_estouro' => $obrasComEstouro,
                'obras_atencao' => $obrasComAtencao,
            ],
            'obras' => $obrasProcessadas,
            'obras_alerta' => $obrasAlerta,
            'gasto_por_cat' => $gastoPorCat, // Gastos reais do Nibo por categoria
            'orcado_por_cat' => $orcadoPorCat, // Orçado vem dos custos previstos cadastrados
            'a_pagar_por_cat' => $aPagarPorCat,
            'categorias_nibo' => $categoriaNiboGlobal, // Categorias Nibo originais
            'fornecedores' => $topFornecedoresGlobal, // DO NIBO
            'fluxo_mensal' => $fluxoMensal, // DO NIBO
            'lancamentos_por_obra' => $lancamentosPorObra, // Detalhamento completo DO NIBO
            'nao_mapeadas' => $naoMapeadas,
            'sem_obra_count' => count($semObra),
            'sem_obra_total' => $semObraTotal,
            'sem_obra_lancamentos' => array_slice($semObra, 0, 50), // Primeiros 50
            'synced_at' => $data['synced_at'], // Timestamp da sincronização
            'periodo' => $data['periodo'], // Período filtrado
        ];
        
        return $resultado;
    }
    
    /**
     * Sincroniza parcelas contratuais com lançamentos do Nibo
     */
    private static function syncParcelasWithNibo(array $lancamentos, array $obras): void
    {
        foreach ($obras as $obra) {
            $parcelas = \App\Models\PainelObraParcela::getByObra($obra['id']);
            
            foreach ($parcelas as $parcela) {
                // Se parcela já tem vínculo com Nibo, atualiza status
                if (!empty($parcela['nibo_schedule_id'])) {
                    // Busca lançamento correspondente
                    $lancamento = array_filter($lancamentos, fn($l) => 
                        (string)($l['schedule_id'] ?? '') === $parcela['nibo_schedule_id']
                    );
                    
                    if (!empty($lancamento)) {
                        $lanc = reset($lancamento);
                        $valorRecebido = ($lanc['status'] === 'paid') ? (float)$lanc['valor_rateado'] : 0;
                        $dataRecebimento = ($lanc['status'] === 'paid') ? ($lanc['due_date'] ?? null) : null;
                        
                        \App\Models\PainelObraParcela::syncWithNibo(
                            $parcela['id'],
                            $valorRecebido,
                            $dataRecebimento
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Valor a pagar da obra (lançamentos em aberto)
     */
    private static function aPagarObra(array $lancamentos, int $obraId): float
    {
        $total = 0.0;
        foreach ($lancamentos as $l) {
            if ($l['obra_id'] === $obraId && 
                $l['type'] === 'payable' && 
                in_array($l['status'], ['open', 'overdue'])) {
                $total += (float) $l['valor_rateado'];
            }
        }
        return $total;
    }
    
    /**
     * Busca total da planilha de controle para um período
     */
    private static function getPlanilhaTotal(int $obraId, string $periodo): ?float
    {
        // Converte período para competência (YYYY-MM)
        $competencia = substr($periodo, 0, 7);
        
        $row = Database::fetch(
            "SELECT total_planilha FROM painel_obras_planilha 
             WHERE obra_id = ? AND competencia = ?",
            [$obraId, $competencia]
        );
        
        return $row ? (float)$row['total_planilha'] : null;
    }
    
    /**
     * Calcula fluxo mensal (12 meses)
     */
    private static function getFluxoMensal(array $lancamentos): array
    {
        $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $anoAtual = (int)date('Y');
        
        $fluxo = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $mesIni = sprintf('%04d-%02d-01', $anoAtual, $i);
            $mesFim = date('Y-m-t', strtotime($mesIni));
            
            $pago = 0;
            $aPagar = 0;
            $previsto = 0;
            
            foreach ($lancamentos as $l) {
                $dataRef = $l['due_date'] ?? $l['forecast_date'] ?? '';
                if ($dataRef < $mesIni || $dataRef > $mesFim) {
                    continue;
                }
                
                $valor = (float)($l['valor_rateado'] ?? 0);
                
                if ($l['status'] === 'paid') {
                    $pago += $valor;
                } elseif ($l['status'] === 'open') {
                    $aPagar += $valor;
                } else {
                    $previsto += $valor;
                }
            }
            
            $fluxo[] = [
                'mes' => $i,
                'label' => $meses[$i - 1],
                'pago' => $pago,
                'a_pagar' => $aPagar,
                'previsto' => $previsto,
            ];
        }
        
        return $fluxo;
    }
}

