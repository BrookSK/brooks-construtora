<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\PainelObra;
use App\Models\PainelCategoriasDepara;
use App\Services\NiboCategoriesService;
use App\Services\NiboService;
use App\Services\PainelObrasService;
use App\Models\NiboSyncSnapshot;

/**
 * Painel Financeiro de Obras.
 * Regra principal: NUNCA altera tabelas ou serviços existentes.
 * Todas as tabelas acessadas aqui são da migration 053_painel_obras.sql.
 */
class PainelObrasController extends Controller
{
    // Removido CATEGORIAS_OBRA hardcoded
    // Agora as categorias vêm do Nibo através do de-para

    public function __construct()
    {
        if (!Auth::check()) { $this->redirect('/admin/login'); exit; }
        if (!Auth::hasPermission('orders.payment')) { $this->redirect('/admin/dashboard'); exit; }
    }

    // ─── Dashboard ────────────────────────────────────────────────────

    public function index(): void
    {
        $company     = (string) $this->input('company', 'brooks');
        if (!NiboService::isValidCompany($company)) $company = 'brooks';
        $competencia = (string) $this->input('periodo', date('Y-m'));
        
        // Busca categorias que TÊM ORÇAMENTO cadastrado (não todas as categorias com gastos)
        $categoriasObra = [];
        
        try {
            $dashboardData = PainelObrasService::getDashboardData($competencia, $company);
            
            // Apenas categorias que têm orçamento cadastrado
            foreach (($dashboardData['orcado_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado') {
                    $categoriasObra[] = $cat;
                }
            }
            
            // Se houver gastos em categorias que não têm orçamento, adiciona também (para mostrar desvio)
            foreach (($dashboardData['gasto_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado' && !in_array($cat, $categoriasObra)) {
                    $categoriasObra[] = $cat;
                }
            }
            
            sort($categoriasObra);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] ERRO FATAL ao carregar index: ' . $e->getMessage());
            error_log('[PainelObrasController] Stack trace: ' . $e->getTraceAsString());
            // Define vazio para não quebrar a view
            $categoriasObra = [];
        }

        $this->view('admin.painel_obras.index', [
            'pageTitle'      => 'Painel de Obras',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'company'        => $company,
            'companies'      => NiboService::companies(),
            'competencia'    => $competencia,
            'categoriasObra' => $categoriasObra,
        ]);
    }

    public function dashboardData(): void
    {
        $company     = (string) $this->input('company', 'brooks');
        $competencia = (string) $this->input('periodo', date('Y-m'));

        try {
            // Sincroniza obras a partir dos centros de custo do Nibo
            $sync = PainelObrasService::sincronizarObrasDoNibo($company);
            
            $dashboardData = PainelObrasService::getDashboardData($competencia, $company);
            
            // Busca snapshot para pegar synced_at
            $snapshot = NiboSyncSnapshot::latest($company === 'completo' ? 'brooks' : $company);
            $syncedAt = $snapshot ? $snapshot['created_at'] : null;
            
            // Aplica filtros do usuário
            $user = Auth::user();
            $config = Database::fetch(
                "SELECT centros_custo_visiveis, obras_visiveis 
                 FROM painel_obras_config 
                 WHERE user_id = ?",
                [$user['id']]
            );
            
            if ($config) {
                $ccVisiveis = json_decode($config['centros_custo_visiveis'] ?? '[]', true) ?: [];
                $obrasVisiveis = json_decode($config['obras_visiveis'] ?? '[]', true) ?: [];
                
                error_log('[PainelObras] Config carregada - CCs: ' . count($ccVisiveis) . ', Obras: ' . count($obrasVisiveis));
                error_log('[PainelObras] Obras visíveis IDs: ' . json_encode($obrasVisiveis));
                
                // Filtra obras se houver filtro ativo
                if (!empty($obrasVisiveis)) {
                    // Converte IDs para inteiros para comparação
                    $obrasVisiveis = array_map('intval', $obrasVisiveis);
                    
                    error_log('[PainelObras] Total de obras ANTES do filtro: ' . count($dashboardData['obras']));
                    
                    // Log das primeiras 3 obras para debug
                    if (!empty($dashboardData['obras'])) {
                        $sample = array_slice($dashboardData['obras'], 0, 3);
                        foreach ($sample as $s) {
                            error_log('[PainelObras] Obra sample - ID: ' . $s['id'] . ' (type: ' . gettype($s['id']) . '), Nome: ' . $s['nome']);
                        }
                    }
                    
                    $dashboardData['obras'] = array_filter(
                        $dashboardData['obras'],
                        function($o) use ($obrasVisiveis) {
                            $obraId = (int)$o['id'];
                            $match = in_array($obraId, $obrasVisiveis);
                            if ($match) {
                                error_log('[PainelObras] Obra INCLUÍDA: ID=' . $obraId . ', Nome=' . $o['nome']);
                            }
                            return $match;
                        }
                    );
                    $dashboardData['obras'] = array_values($dashboardData['obras']); // Reindex
                    
                    error_log('[PainelObras] Total de obras DEPOIS do filtro: ' . count($dashboardData['obras']));
                }
                
                // Recalcula KPIs com obras filtradas
                if (!empty($obrasVisiveis)) {
                    $totalContratado = $totalOrcado = $totalGasto = $totalAPagar = 0;
                    $obrasEstouro = $obrasAtencao = 0;
                    
                    foreach ($dashboardData['obras'] as $obra) {
                        $totalContratado += $obra['contratado'];
                        $totalOrcado += $obra['orcado'];
                        $totalGasto += $obra['gasto'];
                        $totalAPagar += $obra['a_pagar'];
                        
                        if ($obra['situacao'] === 'danger') $obrasEstouro++;
                        if ($obra['situacao'] === 'warn') $obrasAtencao++;
                    }
                    
                    $dashboardData['kpis'] = [
                        'obras_total' => count($dashboardData['obras']),
                        'contratado' => $totalContratado,
                        'orcado' => $totalOrcado,
                        'gasto' => $totalGasto,
                        'a_pagar' => $totalAPagar,
                        'desvio' => $totalGasto - $totalOrcado,
                        'saldo' => $totalContratado - $totalGasto, // NOVO: Saldo disponível
                        'obras_estouro' => $obrasEstouro,
                        'obras_atencao' => $obrasAtencao,
                    ];
                }
            }
            
            $this->json([
                'ok' => true,
                'data' => $dashboardData,
                'synced_at' => $syncedAt,
                'sync_info' => $sync, // Info sobre obras criadas/atualizadas
            ]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao buscar dados: ' . $e->getMessage());
            $this->json([
                'ok' => false,
                'error' => 'Erro ao carregar dados: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── Cadastro de Obras ────────────────────────────────────────────

    public function obras(): void
    {
        try {
            $obras = PainelObra::allWithFilter(
                (string) $this->input('situacao', ''),
                (string) $this->input('q', '')
            );
            
            // Carrega custos previstos para cada obra
            foreach ($obras as &$o) {
                $o['completude'] = $this->completude($o);
                try {
                    $o['custos_previstos'] = Database::fetchAll(
                        "SELECT categoria_nibo_id, categoria_nome, valor_previsto 
                         FROM painel_obras_custos_previstos 
                         WHERE obra_id = ?",
                        [$o['id']]
                    );
                } catch (\Throwable $e) {
                    $o['custos_previstos'] = [];
                }
            }
            unset($o);
        } catch (\Throwable $e) { $obras = []; }

        try { $costCenters = $this->listarCentrosCusto(); } catch (\Throwable $e) { $costCenters = []; }
        $pendentes = count(array_filter($obras, fn($o) => !($o['completude']['completa'] ?? false)));

        $this->view('admin.painel_obras.obras', [
            'pageTitle'   => 'Obras — Painel Financeiro',
            'currentPage' => 'painel_obras',
            'user'        => Auth::user(),
            'flash'       => $this->getFlash(),
            'obras'       => $obras,
            'costCenters' => $costCenters,
            'pendentes'   => $pendentes,
        ]);
    }

    public function obraStore(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $user   = Auth::user();
        $codigo = strtoupper(trim((string)$this->input('codigo','')));
        if (!$codigo) { $this->json(['ok'=>false,'error'=>'Código obrigatório.'],422); return; }

        try {
            $exists = Database::fetch("SELECT id FROM painel_obras WHERE codigo = ?", [$codigo]);
        } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>'Execute a migration 053_painel_obras.sql primeiro.'],500); return; }

        if ($exists) { $this->json(['ok'=>false,'error'=>'Código já cadastrado.'],409); return; }

        $dataContrato = (string)$this->input('data_contrato','');
        if (!$dataContrato) { $this->json(['ok'=>false,'error'=>'Data do contrato obrigatória.'],422); return; }

        $dataInicio  = (string)$this->input('data_inicio','');
        $dataPrevFim = (string)$this->input('data_prev_fim','');
        if ($dataInicio && $dataPrevFim && $dataPrevFim < $dataInicio) {
            $this->json(['ok'=>false,'error'=>'Data de fim deve ser maior que data de início.'],422); return;
        }

        $ccs  = json_decode((string)$this->input('centros_custo','[]'),true) ?: [];
        $alts = array_values(array_filter(array_map('trim', explode(',', (string)$this->input('codigos_alt','')))));

        $id = PainelObra::create([
            'codigo'          => $codigo,
            'nome'            => trim((string)$this->input('nome','')),
            'tipo'            => $this->input('tipo','Reforma'),
            'cliente'         => trim((string)$this->input('cliente','')) ?: null,
            'centros_custo'   => json_encode(array_values($ccs)),
            'codigos_alt'     => json_encode($alts),
            'valor_contratado'=> $this->parseMoeda($this->input('valor_contratado','0')),
            'data_contrato'   => $dataContrato,
            'data_inicio'     => $dataInicio ?: null,
            'data_prev_fim'   => $dataPrevFim ?: null,
            'situacao'        => $this->input('situacao','Ativa'),
            'responsavel'     => trim((string)$this->input('responsavel','')) ?: null,
            'observacoes'     => trim((string)$this->input('observacoes','')) ?: null,
            'valor_entrada'   => $this->parseMoeda($this->input('valor_entrada','0')),
            'numero_parcelas' => (int)$this->input('numero_parcelas',0) ?: null,
            'frequencia_parcelas' => trim((string)$this->input('frequencia_parcelas','')) ?: null,
            'carencia_meses'  => (int)$this->input('carencia_meses',0),
            'created_by'      => $user['id'],
        ]);
        
        // Salva custos previstos
        $custosJson = (string)$this->input('custos_previstos','{}');
        $custos = json_decode($custosJson, true) ?: [];
        
        if (!empty($custos)) {
            // Busca categorias do Nibo para pegar os nomes
            try {
                $company = 'brooks'; // Nova obra usa brooks por padrão
                $snapshot = \App\Models\NiboSyncSnapshot::latest($company);
                $payload = \App\Models\NiboSyncSnapshot::decodePayload($snapshot);
                $categoriasNibo = $payload['filters']['categories'] ?? [];
                
                // Cria mapa id => nome
                $categoriaMap = [];
                foreach ($categoriasNibo as $cat) {
                    $categoriaMap[$cat['id']] = $cat['name'];
                }
                
                foreach ($custos as $catNiboId => $valor) {
                    if ($valor > 0 && isset($categoriaMap[$catNiboId])) {
                        Database::insert('painel_obras_custos_previstos', [
                            'obra_id' => $id,
                            'categoria_nibo_id' => (string)$catNiboId,
                            'categoria_nome' => $categoriaMap[$catNiboId],
                            'valor_previsto' => (float)$valor,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                error_log('[OBRA STORE] Erro ao salvar custos previstos: ' . $e->getMessage());
                // Não falha a criação da obra, apenas loga o erro
            }
        }
        
        $this->json(['ok'=>true,'id'=>$id]);
    }

    public function obraUpdate(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $id   = (int)$this->input('id',0);
        try { $obra = PainelObra::find($id); } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>'Migration não executada.'],500); return; }
        if (!$obra) { $this->json(['ok'=>false,'error'=>'Obra não encontrada.'],404); return; }
        $user   = Auth::user();
        $codigo = strtoupper(trim((string)$this->input('codigo','')));
        $exists = Database::fetch("SELECT id FROM painel_obras WHERE codigo = ? AND id != ?",[$codigo,$id]);
        if ($exists) { $this->json(['ok'=>false,'error'=>'Código já cadastrado.'],409); return; }
        $dataInicio  = (string)$this->input('data_inicio','');
        $dataPrevFim = (string)$this->input('data_prev_fim','');
        if ($dataInicio && $dataPrevFim && $dataPrevFim < $dataInicio) {
            $this->json(['ok'=>false,'error'=>'Data de fim deve ser maior que data de início.'],422); return;
        }
        $ccs  = json_decode((string)$this->input('centros_custo','[]'),true) ?: [];
        $alts = array_values(array_filter(array_map('trim', explode(',', (string)$this->input('codigos_alt','')))));
        
        PainelObra::updateById($id, [
            'codigo'          => $codigo,
            'nome'            => trim((string)$this->input('nome','')),
            'tipo'            => $this->input('tipo','Reforma'),
            'cliente'         => trim((string)$this->input('cliente','')) ?: null,
            'centros_custo'   => json_encode(array_values($ccs)),
            'codigos_alt'     => json_encode($alts),
            'valor_contratado'=> $this->parseMoeda($this->input('valor_contratado','0')),
            'data_contrato'   => (string)$this->input('data_contrato',''),
            'data_inicio'     => $dataInicio ?: null,
            'data_prev_fim'   => $dataPrevFim ?: null,
            'situacao'        => $this->input('situacao','Ativa'),
            'responsavel'     => trim((string)$this->input('responsavel','')) ?: null,
            'observacoes'     => trim((string)$this->input('observacoes','')) ?: null,
            'valor_entrada'   => $this->parseMoeda($this->input('valor_entrada','0')),
            'numero_parcelas' => (int)$this->input('numero_parcelas',0) ?: null,
            'frequencia_parcelas' => trim((string)$this->input('frequencia_parcelas','')) ?: null,
            'carencia_meses'  => (int)$this->input('carencia_meses',0),
            'updated_by'      => $user['id'],
        ]);
        
        // Salva custos previstos
        $custosJson = (string)$this->input('custos_previstos','{}');
        $custos = json_decode($custosJson, true) ?: [];
        
        if (!empty($custos)) {
            // Busca categorias do Nibo para pegar os nomes
            try {
                $company = $obra['company'] ?? 'brooks';
                $snapshot = \App\Models\NiboSyncSnapshot::latest($company);
                $payload = \App\Models\NiboSyncSnapshot::decodePayload($snapshot);
                $categoriasNibo = $payload['filters']['categories'] ?? [];
                
                // Cria mapa id => nome
                $categoriaMap = [];
                foreach ($categoriasNibo as $cat) {
                    $categoriaMap[$cat['id']] = $cat['name'];
                }
                
                // UPSERT: atualiza ou insere cada categoria
                foreach ($custos as $catNiboId => $valor) {
                    $valorFloat = (float)$valor;
                    
                    if (!isset($categoriaMap[$catNiboId])) {
                        continue; // Ignora categoria inválida
                    }
                    
                    // Verifica se já existe registro para esta obra + categoria
                    $existe = Database::fetch(
                        "SELECT id FROM painel_obras_custos_previstos 
                         WHERE obra_id = ? AND categoria_nibo_id = ?",
                        [$id, $catNiboId]
                    );
                    
                    if ($valorFloat > 0) {
                        // Se valor > 0, insere ou atualiza
                        if ($existe) {
                            Database::query(
                                "UPDATE painel_obras_custos_previstos 
                                 SET valor_previsto = ?, categoria_nome = ?, updated_at = NOW()
                                 WHERE id = ?",
                                [$valorFloat, $categoriaMap[$catNiboId], $existe['id']]
                            );
                        } else {
                            Database::insert('painel_obras_custos_previstos', [
                                'obra_id' => $id,
                                'categoria_nibo_id' => (string)$catNiboId,
                                'categoria_nome' => $categoriaMap[$catNiboId],
                                'valor_previsto' => $valorFloat,
                            ]);
                        }
                    } else {
                        // Se valor = 0, remove o registro (se existir)
                        if ($existe) {
                            Database::query(
                                "DELETE FROM painel_obras_custos_previstos WHERE id = ?",
                                [$existe['id']]
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('[OBRA UPDATE] Erro ao salvar custos previstos: ' . $e->getMessage());
                $this->json(['ok'=>false,'error'=>'Erro ao salvar custos previstos.'],500);
                return;
            }
        }
        
        $this->json(['ok'=>true]);
    }

    public function obraDelete(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { PainelObra::deleteById((int)$this->input('id',0)); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
    }

    public function obrasData(): void
    {
        $obraId = (int)$this->input('id', 0);
        
        if (!$obraId) {
            $this->json(['ok' => false, 'error' => 'ID da obra obrigatório'], 422);
            return;
        }
        
        try {
            $obra = Database::fetch("SELECT * FROM painel_obras WHERE id = ?", [$obraId]);
            
            if (!$obra) {
                $this->json(['ok' => false, 'error' => 'Obra não encontrada'], 404);
                return;
            }
            
            // Decodifica JSON fields
            $obra['centros_custo'] = json_decode($obra['centros_custo'] ?? '[]', true);
            $obra['codigos_alt'] = json_decode($obra['codigos_alt'] ?? '[]', true);
            
            // Busca recebimentos registrados
            $recebimentos = Database::fetchAll(
                "SELECT * FROM painel_obras_recebimentos WHERE obra_id = ? ORDER BY data_recebimento DESC",
                [$obraId]
            );
            
            $obra['recebimentos'] = $recebimentos;
            
            $this->json(['ok' => true, 'obra' => $obra]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao buscar dados da obra: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao buscar dados da obra'], 500);
        }
    }

    public function recebimentoRegistrar(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false], 400);
            return;
        }
        
        $user = Auth::user();
        $obraId = (int)$this->input('obra_id', 0);
        $tipo = (string)$this->input('tipo', '');
        $numeroParcela = $this->input('numero_parcela', null);
        $valor = $this->parseMoeda($this->input('valor', '0'));
        $dataVencimento = (string)$this->input('data_vencimento', '');
        $dataRecebimento = (string)$this->input('data_recebimento', date('Y-m-d'));
        $observacoes = trim((string)$this->input('observacoes', ''));
        
        if (!$obraId || !$tipo || !$valor || !$dataVencimento) {
            $this->json(['ok' => false, 'error' => 'Dados incompletos'], 422);
            return;
        }
        
        try {
            // Verifica se já foi registrado
            $whereClause = "obra_id = ? AND tipo = ?";
            $params = [$obraId, $tipo];
            
            if ($tipo === 'parcela') {
                $whereClause .= " AND numero_parcela = ?";
                $params[] = (int)$numeroParcela;
            }
            
            $existe = Database::fetch(
                "SELECT id FROM painel_obras_recebimentos WHERE $whereClause",
                $params
            );
            
            if ($existe) {
                $this->json(['ok' => false, 'error' => 'Este recebimento já foi registrado'], 409);
                return;
            }
            
            // Registra o recebimento
            $id = Database::insert('painel_obras_recebimentos', [
                'obra_id' => $obraId,
                'tipo' => $tipo,
                'numero_parcela' => $tipo === 'parcela' ? (int)$numeroParcela : null,
                'valor' => $valor,
                'data_vencimento' => $dataVencimento,
                'data_recebimento' => $dataRecebimento,
                'observacoes' => $observacoes ?: null,
                'created_by' => $user['id'],
            ]);
            
            // Busca total já recebido
            $totalRecebido = Database::fetch(
                "SELECT SUM(valor) as total FROM painel_obras_recebimentos WHERE obra_id = ?",
                [$obraId]
            );
            
            $this->json([
                'ok' => true,
                'id' => $id,
                'total_recebido' => (float)($totalRecebido['total'] ?? 0)
            ]);
            
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao registrar recebimento: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao registrar recebimento'], 500);
        }
    }

    public function obrasCategorias(): void
    {
        $company = (string) $this->input('company', 'brooks');
        
        try {
            error_log('[PainelObras] Buscando categorias para company: ' . $company);
            
            // Busca categorias do último snapshot do Nibo
            $snapshot = \App\Models\NiboSyncSnapshot::latest($company);
            
            if (!$snapshot) {
                error_log('[PainelObras] Nenhum snapshot encontrado para company: ' . $company);
                $this->json([
                    'Construção' => [],
                    'Reforma' => [],
                    'erro' => 'Nenhuma sincronização encontrada. Execute a sincronização do Nibo primeiro.'
                ]);
                return;
            }
            
            error_log('[PainelObras] Snapshot encontrado - ID: ' . $snapshot['id'] . ', created_at: ' . $snapshot['created_at']);
            
            $payload = \App\Models\NiboSyncSnapshot::decodePayload($snapshot);
            
            // As categorias estão em filters.categories
            if (!isset($payload['filters']['categories'])) {
                error_log('[PainelObras] ERRO: payload não tem chave filters.categories');
                $this->json([
                    'Construção' => [],
                    'Reforma' => [],
                    'erro' => 'Nenhuma categoria encontrada no snapshot'
                ]);
                return;
            }
            
            $categories = $payload['filters']['categories'];
            error_log('[PainelObras] Total de categorias do Nibo no snapshot: ' . count($categories));
            
            if (empty($categories)) {
                error_log('[PainelObras] AVISO: Array de categorias está vazio');
                $this->json([
                    'Construção' => [],
                    'Reforma' => [],
                    'erro' => 'Nenhuma categoria encontrada no Nibo'
                ]);
                return;
            }
            
            // Filtra apenas categorias que TÊM LANÇAMENTOS no período
            // Para isso, pega todos os payables e receivables e extrai as category_ids únicas
            $payables = $payload['payables'] ?? [];
            $receivables = $payload['receivables'] ?? [];
            
            $categoriesComLancamentos = [];
            foreach ($payables as $item) {
                if (!empty($item['category_id'])) {
                    $categoriesComLancamentos[$item['category_id']] = true;
                }
            }
            foreach ($receivables as $item) {
                if (!empty($item['category_id'])) {
                    $categoriesComLancamentos[$item['category_id']] = true;
                }
            }
            
            error_log('[PainelObras] Categorias com lançamentos: ' . count($categoriesComLancamentos));
            
            // Filtra apenas as categorias que têm lançamentos
            $categoriasComDados = array_filter($categories, function($cat) use ($categoriesComLancamentos) {
                return isset($categoriesComLancamentos[$cat['id']]);
            });
            
            // Formata para o frontend
            $categoriasFormatadas = array_map(function($cat) {
                return [
                    'id' => $cat['id'] ?? '',
                    'nome' => $cat['name'] ?? 'Sem nome',
                ];
            }, $categoriasComDados);
            
            // Ordena por nome
            usort($categoriasFormatadas, fn($a, $b) => strcmp($a['nome'], $b['nome']));
            
            error_log('[PainelObras] Categorias formatadas (com lançamentos): ' . count($categoriasFormatadas));
            
            // Retorna as mesmas para ambos os tipos
            $this->json([
                'Construção' => array_values($categoriasFormatadas),
                'Reforma' => array_values($categoriasFormatadas),
            ]);
            
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] EXCEÇÃO ao buscar categorias: ' . $e->getMessage());
            error_log('[PainelObrasController] Stack trace: ' . $e->getTraceAsString());
            $this->json([
                'Construção' => [],
                'Reforma' => [],
                'erro' => 'Erro ao buscar categorias: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─── Configurações de Visualização ────────────────────────────────

    public function configGet(): void
    {
        $user = Auth::user();
        try {
            $config = Database::fetch(
                "SELECT centros_custo_visiveis, obras_visiveis, filtros_ativos 
                 FROM painel_obras_config 
                 WHERE user_id = ?",
                [$user['id']]
            );
            
            if ($config) {
                $this->json([
                    'centros_custo_visiveis' => json_decode($config['centros_custo_visiveis'] ?? '[]', true) ?: [],
                    'obras_visiveis' => json_decode($config['obras_visiveis'] ?? '[]', true) ?: [],
                    'filtros_ativos' => json_decode($config['filtros_ativos'] ?? '{}', true) ?: [],
                ]);
            } else {
                $this->json([
                    'centros_custo_visiveis' => [],
                    'obras_visiveis' => [],
                    'filtros_ativos' => [],
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao buscar config: ' . $e->getMessage());
            $this->json(['centros_custo_visiveis' => [], 'obras_visiveis' => [], 'filtros_ativos' => []]);
        }
    }

    public function configSave(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        
        $user = Auth::user();
        $body = file_get_contents('php://input');
        $data = json_decode($body, true) ?: [];
        
        $centrosCusto = $data['centros_custo_visiveis'] ?? [];
        $obras = $data['obras_visiveis'] ?? [];
        $filtros = $data['filtros_ativos'] ?? [];
        
        try {
            $exists = Database::fetch("SELECT id FROM painel_obras_config WHERE user_id = ?", [$user['id']]);
            
            if ($exists) {
                Database::query(
                    "UPDATE painel_obras_config 
                     SET centros_custo_visiveis = ?, obras_visiveis = ?, filtros_ativos = ?, updated_at = NOW()
                     WHERE user_id = ?",
                    [json_encode($centrosCusto), json_encode($obras), json_encode($filtros), $user['id']]
                );
            } else {
                Database::insert('painel_obras_config', [
                    'user_id' => $user['id'],
                    'centros_custo_visiveis' => json_encode($centrosCusto),
                    'obras_visiveis' => json_encode($obras),
                    'filtros_ativos' => json_encode($filtros),
                ]);
            }
            
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao salvar config: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao salvar configurações.'], 500);
        }
    }

    // ─── Aditivos ─────────────────────────────────────────────────────

    public function aditivoStore(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $obraId = (int)$this->input('obra_id',0);
        try { $obra = PainelObra::find($obraId); } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>'Migration não executada.'],500); return; }
        if (!$obra) { $this->json(['ok'=>false,'error'=>'Obra não encontrada.'],404); return; }
        $user = Auth::user();
        $id = Database::insert('painel_obras_aditivos',[
            'obra_id'   => $obraId,
            'data'      => (string)$this->input('data',date('Y-m-d')),
            'valor'     => $this->parseMoeda($this->input('valor','0')),
            'descricao' => trim((string)$this->input('descricao','')),
            'created_by'=> $user['id'],
        ]);
        $this->json(['ok'=>true,'id'=>$id]);
    }

    public function aditivoDelete(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { Database::query("DELETE FROM painel_obras_aditivos WHERE id = ?",[(int)$this->input('id',0)]); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
    }

    public function aditivosList(): void
    {
        $obraId = (int)$this->input('obra_id',0);
        try {
            $rows = Database::fetchAll("SELECT * FROM painel_obras_aditivos WHERE obra_id = ? ORDER BY data DESC",[$obraId]);
        } catch (\Throwable $e) { $rows = []; }
        $this->json(['ok'=>true,'aditivos'=>$rows]);
    }

    // ─── Orçamento (DESABILITADO - usar Custos Previstos em Obras) ────

    /*
    public function orcamento(): void
    {
        // Busca configuração de filtro do usuário
        $user = Auth::user();
        $config = null;
        try {
            $config = Database::fetch(
                "SELECT obras_visiveis FROM painel_obras_config WHERE user_id = ?",
                [$user['id']]
            );
        } catch (\Throwable $e) {
            $config = null;
        }
        
        // Decodifica os filtros de obras
        $obrasVisiveisIds = [];
        if ($config && !empty($config['obras_visiveis'])) {
            $obrasVisiveisIds = json_decode($config['obras_visiveis'], true) ?: [];
        }
        
        // Busca obras aplicando o filtro se houver
        try {
            if (!empty($obrasVisiveisIds)) {
                // Aplica filtro: apenas obras selecionadas
                $placeholders = implode(',', array_fill(0, count($obrasVisiveisIds), '?'));
                $obras = Database::fetchAll(
                    "SELECT * FROM painel_obras WHERE id IN ($placeholders) ORDER BY codigo ASC",
                    $obrasVisiveisIds
                );
            } else {
                // Nenhum filtro = mostra todas
                $obras = Database::fetchAll("SELECT * FROM painel_obras ORDER BY codigo ASC");
            }
        } catch (\Throwable $e) {
            $obras = [];
        }
        
        // Busca apenas categorias com gastos > 0 (visão limpa)
        $categoriasObra = [];
        try {
            $competencia = date('Y-m');
            $dashboardData = PainelObrasService::getDashboardData($competencia, 'brooks');
            
            foreach (($dashboardData['gasto_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado') {
                    $categoriasObra[] = $cat;
                }
            }
            
            foreach (($dashboardData['a_pagar_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado' && !in_array($cat, $categoriasObra)) {
                    $categoriasObra[] = $cat;
                }
            }
            
            sort($categoriasObra);
        } catch (\Throwable $e) {
            $categoriasObra = [];
        }
        
        $this->view('admin.painel_obras.orcamento',[
            'pageTitle'      => 'Orçamento por Obra',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'obras'          => $obras,
            'categoriasObra' => $categoriasObra,
        ]);
    }
    */

    // ─── Custos Previstos (usado na aba de Obras) ────────────────────

    public function orcamentoData(): void
    {
        $obraId = (int)$this->input('obra_id',0);
        
        try {
            // Busca os valores orçados salvos (com ID do Nibo)
            $linhas = Database::fetchAll(
                "SELECT 
                    cp.id,
                    cp.obra_id,
                    cp.categoria_nibo_id,
                    cp.categoria_nome,
                    cp.valor_previsto,
                    cp.observacoes,
                    cp.updated_at
                FROM painel_obras_custos_previstos cp
                WHERE cp.obra_id = ?
                ORDER BY cp.categoria_nome",
                [$obraId]
            );
            
            // Formata para o frontend esperar (nome => valor)
            $valores = [];
            foreach ($linhas as $linha) {
                $valores[$linha['categoria_nome']] = $linha['valor_previsto'];
            }
            
            $this->json([
                'ok' => true,
                'valores' => $valores,
                'linhas' => $linhas
            ]);
            
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao buscar orçamento: ' . $e->getMessage());
            $this->json([
                'ok' => false,
                'valores' => [],
                'linhas' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function orcamentoSalvar(): void
    {
        if (!$this->isPost()) { 
            $this->json(['ok'=>false],400); 
            return; 
        }
        
        $obraId = (int)$this->input('obra_id',0);
        
        try { 
            $obra = PainelObra::find($obraId); 
        } catch (\Throwable $e) { 
            $this->json(['ok'=>false,'error'=>'Tabela não existe. Execute a migração.'],500); 
            return; 
        }
        
        if (!$obra) { 
            $this->json(['ok'=>false,'error'=>'Obra não encontrada.'],404); 
            return; 
        }
        
        $valores = $this->input('valores',[]);
        
        error_log('[ORCAMENTO] Valores recebidos: ' . print_r($valores, true));
        
        // Verifica se há algum valor preenchido
        $totalOrcado = 0;
        foreach ($valores as $catNome => $val) {
            $valorParsed = is_numeric($val) ? (float)$val : $this->parseMoeda($val);
            if ($valorParsed > 0) {
                $totalOrcado += $valorParsed;
            }
        }
        
        if ($totalOrcado <= 0) {
            $this->json(['ok'=>false,'error'=>'Informe pelo menos um valor orçado.'],422);
            return;
        }
        
        // Busca categorias do Nibo (do snapshot) para pegar o ID
        try {
            $company = $obra['company'] ?? 'brooks';
            $snapshot = \App\Models\NiboSyncSnapshot::latest($company);
            
            if (!$snapshot) {
                $this->json(['ok'=>false,'error'=>'Sincronização do Nibo não encontrada.'],500);
                return;
            }
            
            $payload = \App\Models\NiboSyncSnapshot::decodePayload($snapshot);
            $categoriasNibo = $payload['filters']['categories'] ?? [];
            
            // Cria mapa nome => id
            $categoriaMap = [];
            foreach ($categoriasNibo as $cat) {
                $categoriaMap[$cat['name']] = $cat['id'];
            }
            
            error_log('[ORCAMENTO] Categorias do Nibo mapeadas: ' . count($categoriaMap));
            
        } catch (\Throwable $e) {
            error_log('[ORCAMENTO] Erro ao buscar categorias do Nibo: ' . $e->getMessage());
            $this->json(['ok'=>false,'error'=>'Erro ao buscar categorias do Nibo.'],500);
            return;
        }
        
        // Salva os valores usando o ID da categoria do Nibo
        $categoriasInseridas = 0;
        $categoriasAtualizadas = 0;
        $erros = [];
        
        foreach ($valores as $catNome => $val) {
            $valorOrcado = is_numeric($val) ? (float)$val : $this->parseMoeda($val);
            
            if ($valorOrcado <= 0) {
                continue;
            }
            
            // Busca o ID da categoria no Nibo
            if (!isset($categoriaMap[$catNome])) {
                $erro = "Categoria do Nibo não encontrada: $catNome";
                error_log("[ORCAMENTO] $erro");
                $erros[] = $erro;
                continue;
            }
            
            $categoriaNiboId = $categoriaMap[$catNome];
            
            try {
                // Verifica se já existe
                $existe = Database::fetch(
                    "SELECT id FROM painel_obras_custos_previstos WHERE obra_id = ? AND categoria_nibo_id = ?",
                    [$obraId, $categoriaNiboId]
                );
                
                if ($existe) {
                    // Atualiza
                    Database::query(
                        "UPDATE painel_obras_custos_previstos 
                         SET valor_previsto = ?, categoria_nome = ?, updated_at = NOW() 
                         WHERE obra_id = ? AND categoria_nibo_id = ?",
                        [$valorOrcado, $catNome, $obraId, $categoriaNiboId]
                    );
                    $categoriasAtualizadas++;
                } else {
                    // Insere novo
                    Database::query(
                        "INSERT INTO painel_obras_custos_previstos 
                         (obra_id, categoria_nibo_id, categoria_nome, valor_previsto, created_at) 
                         VALUES (?, ?, ?, ?, NOW())",
                        [$obraId, $categoriaNiboId, $catNome, $valorOrcado]
                    );
                    $categoriasInseridas++;
                }
            } catch (\Throwable $e) {
                $erro = 'Erro ao salvar ' . $catNome . ': ' . $e->getMessage();
                error_log('[ORCAMENTO] ' . $erro);
                $erros[] = $erro;
            }
        }
        
        if ($categoriasInseridas === 0 && $categoriasAtualizadas === 0) {
            $msgErro = 'Nenhuma categoria foi salva.';
            if (!empty($erros)) {
                $msgErro .= ' Erros: ' . implode('; ', $erros);
            }
            $this->json(['ok'=>false,'error'=>$msgErro],422);
            return;
        }
        
        $this->json([
            'ok' => true, 
            'inseridas' => $categoriasInseridas,
            'atualizadas' => $categoriasAtualizadas,
            'total' => $categoriasInseridas + $categoriasAtualizadas,
            'erros' => $erros
        ]);
    }

    // ─── Planilha de Controle ─────────────────────────────────────────

    public function planilha(): void
    {
        $comp = (string)$this->input('competencia',date('Y-m'));
        try {
            $obras = PainelObra::allWithFilter();
            $rows  = Database::fetchAll(
                "SELECT p.*, o.codigo, o.nome FROM painel_obras_planilha p JOIN painel_obras o ON o.id = p.obra_id WHERE p.competencia = ? ORDER BY o.codigo",
                [$comp]
            );
        } catch (\Throwable $e) { $obras=[]; $rows=[]; }
        $this->view('admin.painel_obras.planilha',[
            'pageTitle'   => 'Planilha de Controle',
            'currentPage' => 'painel_obras',
            'user'        => Auth::user(),
            'flash'       => $this->getFlash(),
            'obras'       => $obras,
            'competencia' => $comp,
            'registros'   => $rows,
        ]);
    }

    public function planilhaSalvar(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $user   = Auth::user();
        $obraId = (int)$this->input('obra_id',0);
        $comp   = (string)$this->input('competencia','');
        $total  = $this->parseMoeda($this->input('total_planilha','0'));
        if (!$obraId || !$comp) { $this->json(['ok'=>false,'error'=>'Dados inválidos.'],422); return; }
        try {
            Database::query(
                "INSERT INTO painel_obras_planilha (obra_id,competencia,total_planilha,informado_por,observacao) VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE total_planilha=VALUES(total_planilha),informado_por=VALUES(informado_por),observacao=VALUES(observacao),data_informacao=NOW()",
                [$obraId,$comp,$total,$user['name'],trim((string)$this->input('observacao',''))?:null]
            );
        } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>$e->getMessage()],500); return; }
        $this->json(['ok'=>true]);
    }

    // ─── De-para de Categorias ────────────────────────────────────────

    public function depara(): void
    {
        $company = (string)$this->input('company','brooks');
        try {
            $catMap      = NiboCategoriesService::mapa($company);
            $deParaMap   = PainelCategoriasDepara::mapCompleto();
            $naoMapeadas = PainelCategoriasDepara::naoMapeadas();
        } catch (\Throwable $e) { $catMap=[]; $deParaMap=[]; $naoMapeadas=[]; }

        $semEntrada = [];
        foreach ($catMap as $id => $cat) {
            if (!isset($deParaMap[$id])) $semEntrada[] = ['id'=>$id,'nome'=>$cat['nome'],'caminho'=>$cat['caminho']??''];
        }

        // Busca categorias de obra únicas já mapeadas para sugestões
        $categoriasObraExistentes = Database::fetchAll(
            "SELECT DISTINCT categoria_obra FROM painel_categorias_depara 
             WHERE categoria_obra IS NOT NULL AND categoria_obra != '' 
             ORDER BY categoria_obra"
        );
        $categoriasObra = array_column($categoriasObraExistentes, 'categoria_obra');
        
        // Se não houver nenhuma, fornece sugestões padrão
        if (empty($categoriasObra)) {
            $categoriasObra = [
                'Material Básico', 'Mão de Obra', 'Equipamento/Locação',
                'Material de Acabamento', 'Instalações',
                'Administração/Indiretos', 'Projetos e Taxas', 'Imprevistos',
            ];
        }

        $this->view('admin.painel_obras.depara',[
            'pageTitle'      => 'De-para de Categorias',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'catMap'         => $catMap,
            'deParaMap'      => $deParaMap,
            'naoMapeadas'    => $naoMapeadas,
            'semEntrada'     => $semEntrada,
            'categoriasObra' => $categoriasObra,
            'company'        => $company,
            'companies'      => NiboService::companies(),
        ]);
    }

    public function deparaSalvar(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $user    = Auth::user();
        $niboId  = trim((string)$this->input('categoria_nibo_id',''));
        $niboNome= trim((string)$this->input('categoria_nibo_nome',''));
        $catObra = $this->input('categoria_obra',null);
        $ignorar = (bool)$this->input('ignorar_no_painel',false);
        if (!$niboId) { $this->json(['ok'=>false,'error'=>'ID da categoria Nibo obrigatório.'],422); return; }
        try { PainelCategoriasDepara::upsert($niboId,$niboNome,$catObra?:null,$ignorar,(int)$user['id']); } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>$e->getMessage()],500); return; }
        $this->json(['ok'=>true]);
    }

    public function deparaSalvarLote(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $user = Auth::user(); $salvos = 0;
        foreach ((array)$this->input('mapeamentos',[]) as $m) {
            $niboId  = trim($m['categoria_nibo_id']??'');
            if (!$niboId) continue;
            try {
                PainelCategoriasDepara::upsert($niboId,trim($m['categoria_nibo_nome']??''),$m['categoria_obra']??null,!empty($m['ignorar_no_painel']),(int)$user['id']);
                $salvos++;
            } catch (\Throwable $e) {}
        }
        $this->json(['ok'=>true,'salvos'=>$salvos]);
    }

    public function deparaDelete(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { Database::query("DELETE FROM painel_categorias_depara WHERE id = ?",[(int)$this->input('id',0)]); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
    }

    public function syncCategorias(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $company = (string)$this->input('company','brooks');
        try { NiboCategoriesService::mapa($company,true); $this->json(['ok'=>true]); }
        catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>$e->getMessage()],500); }
    }

    // ─── Pauta de Revisão ─────────────────────────────────────────────

    public function pauta(): void
    {
        try {
            $rows  = Database::fetchAll("SELECT p.*,o.codigo,o.nome FROM painel_pauta_revisao p JOIN painel_obras o ON o.id=p.obra_id ORDER BY p.data_reuniao DESC,p.status ASC");
            $obras = PainelObra::allAtivas();
        } catch (\Throwable $e) { $rows=[]; $obras=[]; }
        $this->view('admin.painel_obras.pauta',[
            'pageTitle'   => 'Pauta de Revisão',
            'currentPage' => 'painel_obras',
            'user'        => Auth::user(),
            'flash'       => $this->getFlash(),
            'pauta'       => $rows,
            'obras'       => $obras,
        ]);
    }

    public function pautaStore(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $user    = Auth::user();
        $obraIds = (array)$this->input('obra_ids',[]);
        $data    = (string)$this->input('data_reuniao',date('Y-m-d',strtotime('next friday')));
        $motivo  = trim((string)$this->input('motivo',''));
        $ids = [];
        foreach ($obraIds as $obraId) {
            try {
                Database::query("INSERT INTO painel_pauta_revisao (obra_id,data_reuniao,motivo,adicionado_por) VALUES (?,?,?,?)",[(int)$obraId,$data,$motivo?:null,$user['name']]);
                $last = Database::fetch("SELECT LAST_INSERT_ID() AS id");
                $ids[] = (int)($last['id']??0);
            } catch (\Throwable $e) {}
        }
        $this->json(['ok'=>true,'ids'=>$ids]);
    }

    public function pautaUpdate(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { Database::query("UPDATE painel_pauta_revisao SET status=? WHERE id=?",[$this->input('status','Pendente'),(int)$this->input('id',0)]); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
    }

    public function pautaDelete(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { Database::query("DELETE FROM painel_pauta_revisao WHERE id=?",[(int)$this->input('id',0)]); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
    }

    // ─── Sem obra vinculada ───────────────────────────────────────────

    public function semObra(): void
    {
        $company     = (string)$this->input('company','brooks');
        $competencia = (string)$this->input('periodo',date('Y-m'));
        try {
            $dados = PainelObrasService::dados($company,$competencia);
            $this->json(['ok'=>true,'lancamentos'=>$dados['sem_obra']]);
        } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>$e->getMessage()],500); }
    }

    public function vincularCc(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $obraId = (int)$this->input('obra_id',0);
        $ccId   = trim((string)$this->input('cost_center_id',''));
        try { $obra = PainelObra::find($obraId); } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>'Migration não executada.'],500); return; }
        if (!$obra || !$ccId) { $this->json(['ok'=>false,'error'=>'Dados inválidos.'],422); return; }
        $ccs = json_decode($obra['centros_custo']??'[]',true) ?: [];
        if (!in_array($ccId,$ccs)) {
            $ccs[] = $ccId;
            PainelObra::updateById($obraId,['centros_custo'=>json_encode($ccs),'updated_by'=>Auth::id()]);
        }
        $this->json(['ok'=>true]);
    }

    public function costCenters(): void
    {
        $company = (string)$this->input('company','brooks');
        $this->json(['ok'=>true,'costCenters'=>$this->listarCentrosCusto($company)]);
    }

    // ─── Helpers privados ─────────────────────────────────────────────

    private function listarCentrosCusto(string $company = 'brooks'): array
    {
        $snapshot = NiboSyncSnapshot::latest($company);
        if (!$snapshot) return [];
        $payload = NiboSyncSnapshot::decodePayload($snapshot);
        return $payload['filters']['costcenters'] ?? [];
    }

    private function completude(array $obra): array
    {
        $falta = [];
        if (!$obra['valor_contratado'] || (float)$obra['valor_contratado'] == 0) $falta[] = 'contratado';
        if (!$obra['centros_custo'] || $obra['centros_custo'] === '[]')           $falta[] = 'centro_custo';
        try { $orcado = PainelObra::orcadoTotal((int)$obra['id']); } catch (\Throwable $e) { $orcado = 0; }
        if ($orcado == 0) $falta[] = 'orcamento';
        try {
            $plan = Database::fetch("SELECT id FROM painel_obras_planilha WHERE obra_id = ? AND competencia = ?",[$obra['id'],date('Y-m')]);
        } catch (\Throwable $e) { $plan = null; }
        if (!$plan) $falta[] = 'planilha';
        return ['falta'=>$falta,'completa'=>empty($falta)];
    }

    private function calcularKpis(array $obras, array $dados, string $competencia, array $todosLanc): array
    {
        $lancamentos = $dados['lancamentos'];
        $totalContratado = $totalOrcado = $totalGasto = 0.0;
        $obrasAlerta = $detalheObras = [];
        $gastoPorCat = $orcadoPorCatGlobal = [];

        foreach ($obras as $o) {
            $id         = (int)$o['id'];
            $contratado = PainelObra::contratadoEfetivo($id);
            $orcado     = PainelObra::orcadoTotal($id);
            $gasto      = PainelObrasService::gastoObra($lancamentos,$id);
            $gastoCat   = PainelObrasService::gastosPorCategoria($lancamentos,$id);
            $desvio     = $gasto - $orcado;
            $pct        = $orcado > 0 ? ($desvio/$orcado)*100 : 0;
            $situacao   = $gasto <= $orcado ? 'ok' : ($pct <= 5 ? 'warn' : 'danger');
            $totalContratado += $contratado;
            $totalOrcado     += $orcado;
            $totalGasto      += $gasto;
            foreach ($gastoCat as $cat => $val) $gastoPorCat[$cat] = ($gastoPorCat[$cat]??0.0) + $val;
            $cats = PainelObra::orcadoPorCategoria($id);
            foreach ($cats as $c) $orcadoPorCatGlobal[$c['categoria_obra']] = ($orcadoPorCatGlobal[$c['categoria_obra']]??0.0) + (float)$c['valor_orcado'];
            [$planTotal] = $this->conciliacao($id,$competencia);
            $detalheObras[] = [
                'id'=>$id,'codigo'=>$o['codigo'],'nome'=>$o['nome'],'tipo'=>$o['tipo'],
                'contratado'=>round($contratado,2),'orcado'=>round($orcado,2),'gasto'=>round($gasto,2),
                'desvio'=>round($desvio,2),'desvio_pct'=>round($pct,1),'situacao'=>$situacao,
                'gasto_cat'=>$gastoCat,
                'planilha_total'=>$planTotal,
                'planilha_dif'=>$planTotal!==null?round($gasto-$planTotal,2):null,
            ];
            if ($situacao !== 'ok' && $orcado > 0) {
                $obrasAlerta[] = [
                    'id'=>$id,'codigo'=>$o['codigo'],'nome'=>$o['nome'],'tipo'=>$o['tipo'],
                    'orcado'=>round($orcado,2),'gasto'=>round($gasto,2),'desvio'=>round($desvio,2),
                    'desvio_pct'=>round($pct,1),'situacao'=>$situacao,
                    'top_cats'=>array_slice(array_keys($gastoCat),0,2),
                ];
            }
        }
        usort($obrasAlerta,fn($a,$b)=>$b['desvio_pct']<=>$a['desvio_pct']);
        $obrasAlerta = array_slice($obrasAlerta,0,3);

        // Fluxo mensal usa TODOS os lançamentos do snapshot (não só os de obras cadastradas)
        $fluxo = $this->fluxoMensal($todosLanc);

        $conciliacao = $this->kpisConciliacao($detalheObras);

        try { $naoMapeadas = count(PainelCategoriasDepara::naoMapeadas()); } catch (\Throwable $e) { $naoMapeadas = 0; }

        return [
            'kpis' => [
                'contratado'    => round($totalContratado,2),
                'orcado'        => round($totalOrcado,2),
                'gasto'         => round($totalGasto,2),
                'desvio'        => round($totalGasto-$totalOrcado,2),
                'obras_total'   => count($obras),
                'obras_estouro' => count(array_filter($detalheObras,fn($o)=>$o['situacao']==='danger')),
                'obras_atencao' => count(array_filter($detalheObras,fn($o)=>$o['situacao']==='warn')),
            ],
            'obras'          => $detalheObras,
            'obras_alerta'   => $obrasAlerta,
            'gasto_por_cat'  => $gastoPorCat,
            'orcado_por_cat' => $orcadoPorCatGlobal,
            'fluxo_mensal'   => $fluxo,
            'conciliacao'    => $conciliacao,
            'sem_obra_count' => count($dados['sem_obra']??[]),
            'sem_cat_count'  => count($dados['sem_categoria']??[]),
            'nao_mapeadas'   => $naoMapeadas,
        ];
    }

    private function conciliacao(int $obraId, string $competencia): array
    {
        try {
            $row = Database::fetch("SELECT total_planilha FROM painel_obras_planilha WHERE obra_id = ? AND competencia = ?",[$obraId,$competencia]);
        } catch (\Throwable $e) { $row = null; }
        return $row ? [(float)$row['total_planilha'],$competencia] : [null,$competencia];
    }

    private function kpisConciliacao(array $detalheObras): array
    {
        $conc=$divergPeq=$diverg=0; $lista=[];
        foreach ($detalheObras as $o) {
            if ($o['planilha_total']===null) $status='pendente';
            elseif (abs($o['planilha_dif']??0)===0.0) { $status='conciliada'; $conc++; }
            elseif (abs($o['planilha_dif']??0)<=2000)  { $status='divergencia_pequena'; $divergPeq++; }
            else                                        { $status='divergente'; $diverg++; }
            $lista[] = ['id'=>$o['id'],'codigo'=>$o['codigo'],'nome'=>$o['nome'],'nibo'=>$o['gasto'],'planilha'=>$o['planilha_total'],'dif'=>$o['planilha_dif'],'status'=>$status];
        }
        usort($lista,fn($a,$b)=>strcmp($a['status'],$b['status']));
        return ['conciliadas'=>$conc,'divergencia_pequena'=>$divergPeq,'divergentes'=>$diverg,'lista'=>$lista];
    }

    /**
     * Fluxo mensal — usa todos os lançamentos do snapshot do Nibo (pagos/a pagar/previstos).
     * Isso garante que o gráfico mostra dados mesmo sem obras cadastradas.
     */
    private function fluxoMensal(array $todosLanc): array
    {
        $fluxo = [];
        for ($m=1;$m<=12;$m++) $fluxo[$m]=['pago'=>0.0,'a_pagar'=>0.0,'previsto'=>0.0];
        $ano = (int)date('Y');
        foreach ($todosLanc as $l) {
            $data  = $l['forecast_date'] ?? $l['due_date'] ?? '';
            if (!$data || substr($data,0,4) != $ano) continue;
            $mes   = (int)substr($data,5,2);
            if ($mes < 1 || $mes > 12) continue;
            $valor = abs((float)($l['value'] ?? $l['open_value'] ?? 0));
            $status= $l['status'] ?? ($l['is_paid']??false ? 'paid' : 'open');
            if ($status === 'paid')    $fluxo[$mes]['pago']    += $valor;
            elseif ($status==='overdue') $fluxo[$mes]['a_pagar'] += $valor;
            else                       $fluxo[$mes]['previsto'] += $valor;
        }
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        $out=[];
        foreach ($fluxo as $m=>$v) {
            $out[] = ['mes'=>$m,'label'=>$meses[$m-1],'pago'=>round($v['pago'],2),'a_pagar'=>round($v['a_pagar'],2),'previsto'=>round($v['previsto'],2)];
        }
        return $out;
    }

    private function estruturaVazia(array $todosLanc = []): array
    {
        return [
            'kpis' => ['contratado'=>0,'orcado'=>0,'gasto'=>0,'desvio'=>0,'obras_total'=>0,'obras_estouro'=>0,'obras_atencao'=>0],
            'obras'=>[],'obras_alerta'=>[],
            'gasto_por_cat'=>[],'orcado_por_cat'=>[],
            'fluxo_mensal'=>$this->fluxoMensal($todosLanc),
            'conciliacao'=>['conciliadas'=>0,'divergencia_pequena'=>0,'divergentes'=>0,'lista'=>[]],
            'sem_obra_count'=>0,'sem_cat_count'=>0,'nao_mapeadas'=>0,
        ];
    }

    private function parseMoeda($valor): float
    {
        if ($valor===null||$valor==='') return 0.0;
        $v = str_replace(['R$',' ','.'],'',(string)$valor);
        $v = str_replace(',','.',$v);
        return (float)$v;
    }

    // ─── Lançamentos Financeiros ─────────────────────────────────────────────
    
    public function lancamentos(): void
    {
        // Busca configuração de filtro do usuário
        $user = Auth::user();
        $config = null;
        try {
            $config = Database::fetch(
                "SELECT obras_visiveis FROM painel_obras_config WHERE user_id = ?",
                [$user['id']]
            );
        } catch (\Throwable $e) {
            $config = null;
        }
        
        // Decodifica os filtros de obras
        $obrasVisiveisIds = [];
        if ($config && !empty($config['obras_visiveis'])) {
            $obrasVisiveisIds = json_decode($config['obras_visiveis'], true) ?: [];
        }
        
        // Busca obras aplicando o filtro se houver
        try {
            if (!empty($obrasVisiveisIds)) {
                $placeholders = implode(',', array_fill(0, count($obrasVisiveisIds), '?'));
                $obras = Database::fetchAll(
                    "SELECT * FROM painel_obras WHERE id IN ($placeholders) ORDER BY codigo ASC",
                    $obrasVisiveisIds
                );
            } else {
                $obras = Database::fetchAll("SELECT * FROM painel_obras ORDER BY codigo ASC");
            }
        } catch (\Throwable $e) {
            $obras = [];
        }
        
        $this->view('admin.painel_obras.lancamentos', [
            'pageTitle'   => 'Lançamentos Financeiros',
            'currentPage' => 'painel_obras',
            'user'        => Auth::user(),
            'flash'       => $this->getFlash(),
            'obras'       => $obras,
        ]);
    }
    
    public function lancamentosListar(): void
    {
        $obraId = (int)$this->input('obra_id', 0);
        $categoriaId = (int)$this->input('categoria_id', 0);
        $status = $this->input('status', '');
        $tipo = $this->input('tipo', '');
        
        $where = '1=1';
        $params = [];
        
        if ($obraId > 0) {
            $where .= ' AND l.obra_id = ?';
            $params[] = $obraId;
        }
        if ($categoriaId > 0) {
            $where .= ' AND l.categoria_id = ?';
            $params[] = $categoriaId;
        }
        if ($status) {
            $where .= ' AND l.status = ?';
            $params[] = $status;
        }
        if ($tipo) {
            $where .= ' AND l.tipo = ?';
            $params[] = $tipo;
        }
        
        try {
            $lancamentos = Database::fetchAll(
                "SELECT l.*, o.codigo AS obra_codigo, o.nome AS obra_nome, o.valor_contratado
                 FROM painel_obras_lancamentos l
                 LEFT JOIN painel_obras o ON o.id = l.obra_id
                 WHERE {$where}
                 ORDER BY l.data_lancamento DESC, l.id DESC",
                $params
            );
            
            // Calcular summary
            $contratado = 0;
            $realizadoManual = 0;
            $previsto = 0;
            $realizadoNibo = 0;
            
            $whereObra = $obraId > 0 ? 'WHERE id = ?' : '1=1';
            $paramsObra = $obraId > 0 ? [$obraId] : [];
            
            $obrasData = Database::fetchAll(
                "SELECT id, valor_contratado FROM painel_obras WHERE {$whereObra}",
                $paramsObra
            );
            
            $obrasIds = array_column($obrasData, 'id');
            
            foreach ($obrasData as $o) {
                $contratado += (float)($o['valor_contratado'] ?? 0);
            }
            
            // Lançamentos manuais realizados
            $whereSum = $obraId > 0 ? 'AND obra_id = ?' : '';
            $paramsSum = $obraId > 0 ? [$obraId] : [];
            
            $sumRealizado = Database::fetch(
                "SELECT COALESCE(SUM(valor), 0) AS total 
                 FROM painel_obras_lancamentos 
                 WHERE status = 'Realizado' AND tipo = 'Despesa' {$whereSum}",
                $paramsSum
            );
            $realizadoManual = (float)($sumRealizado['total'] ?? 0);
            
            $sumPrevisto = Database::fetch(
                "SELECT COALESCE(SUM(valor), 0) AS total 
                 FROM painel_obras_lancamentos 
                 WHERE status = 'Previsto' AND tipo = 'Despesa' {$whereSum}",
                $paramsSum
            );
            $previsto = (float)($sumPrevisto['total'] ?? 0);
            
            // Buscar gastos do Nibo para as obras filtradas
            try {
                $competencia = date('Y-m');
                $company = 'brooks';
                $dashboardData = PainelObrasService::getDashboardData($competencia, $company);
                
                // Se filtrou por obra específica, pega só dessa obra
                if ($obraId > 0) {
                    $obraData = array_values(array_filter(
                        $dashboardData['obras'], 
                        fn($o) => $o['id'] === $obraId
                    ));
                    $realizadoNibo = !empty($obraData) ? (float)($obraData[0]['gasto'] ?? 0) : 0;
                } else {
                    // Soma gastos de todas as obras
                    $realizadoNibo = (float)($dashboardData['kpis']['gasto'] ?? 0);
                }
            } catch (\Throwable $e) {
                error_log('[PainelObrasController] Erro ao buscar gastos Nibo: ' . $e->getMessage());
                $realizadoNibo = 0;
            }
            
            // Total realizado = manual + Nibo
            $realizado = $realizadoManual + $realizadoNibo;
            $saldo = $contratado - $realizado;
            
            $this->json([
                'lancamentos' => $lancamentos,
                'summary' => [
                    'contratado' => $contratado,
                    'realizado' => $realizado,
                    'realizado_manual' => $realizadoManual,
                    'realizado_nibo' => $realizadoNibo,
                    'previsto' => $previsto,
                    'saldo' => $saldo,
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao listar lançamentos: ' . $e->getMessage());
            $this->json(['lancamentos' => [], 'summary' => []]);
        }
    }
    
    public function lancamentosSalvar(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false], 400);
            return;
        }
        
        $id = (int)$this->input('id', 0);
        $obraId = (int)$this->input('obra_id', 0);
        $categoriaId = (int)$this->input('categoria_id', 0);
        $tipo = $this->input('tipo', 'Despesa');
        $status = $this->input('status', 'Previsto');
        $descricao = trim($this->input('descricao', ''));
        $valor = (float)$this->input('valor', 0);
        $dataLancamento = $this->input('data_lancamento', '');
        $dataVencimento = $this->input('data_vencimento', '');
        $observacoes = trim($this->input('observacoes', ''));
        
        if (!$obraId || !$categoriaId || !$descricao || $valor <= 0 || !$dataLancamento) {
            $this->json(['ok' => false, 'error' => 'Campos obrigatórios não preenchidos.'], 422);
            return;
        }
        
        $user = Auth::user();
        
        try {
            if ($id > 0) {
                // Atualizar
                Database::query(
                    "UPDATE painel_obras_lancamentos 
                     SET obra_id = ?, categoria_id = ?, tipo = ?, status = ?, descricao = ?, 
                         valor = ?, data_lancamento = ?, data_vencimento = ?, observacoes = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$obraId, $categoriaId, $tipo, $status, $descricao, $valor, $dataLancamento, $dataVencimento ?: null, $observacoes, $id]
                );
            } else {
                // Criar
                Database::insert('painel_obras_lancamentos', [
                    'obra_id' => $obraId,
                    'categoria_id' => $categoriaId,
                    'tipo' => $tipo,
                    'status' => $status,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'data_lancamento' => $dataLancamento,
                    'data_vencimento' => $dataVencimento ?: null,
                    'observacoes' => $observacoes,
                    'created_by' => $user['id'],
                ]);
            }
            
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao salvar lançamento: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }
    }
    
    public function lancamentosDeletar(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false], 400);
            return;
        }
        
        $id = (int)$this->input('id', 0);
        
        try {
            Database::query("DELETE FROM painel_obras_lancamentos WHERE id = ?", [$id]);
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao deletar lançamento: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao deletar.'], 500);
        }
    }
    
    // ─── Categorias ───────────────────────────────────────────────────────────
    
    public function categorias(): void
    {
        try {
            $categorias = Database::fetchAll(
                "SELECT * FROM painel_obras_categorias_servico WHERE ativo = 1 ORDER BY ordem ASC, nome ASC"
            );
            $this->json(['categorias' => $categorias]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao listar categorias: ' . $e->getMessage());
            $this->json(['categorias' => []]);
        }
    }
    
    public function categoriasSalvar(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false], 400);
            return;
        }
        
        $id = (int)$this->input('id', 0);
        $nome = trim($this->input('nome', ''));
        $codigo = trim($this->input('codigo', ''));
        $tipoObra = $this->input('tipo_obra', 'Ambos');
        
        if (!$nome || !$codigo) {
            $this->json(['ok' => false, 'error' => 'Nome e código são obrigatórios.'], 422);
            return;
        }
        
        try {
            if ($id > 0) {
                // Atualizar
                Database::query(
                    "UPDATE painel_obras_categorias_servico 
                     SET nome = ?, codigo = ?, tipo_obra = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$nome, $codigo, $tipoObra, $id]
                );
            } else {
                // Criar
                $ordem = Database::fetch("SELECT COALESCE(MAX(ordem), 0) + 1 AS prox FROM painel_obras_categorias_servico");
                Database::insert('painel_obras_categorias_servico', [
                    'nome' => $nome,
                    'codigo' => $codigo,
                    'tipo_obra' => $tipoObra,
                    'ordem' => $ordem['prox'] ?? 1,
                    'ativo' => 1,
                ]);
            }
            
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao salvar categoria: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }
    }
    
    public function categoriasDeletar(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false], 400);
            return;
        }
        
        $id = (int)$this->input('id', 0);
        
        try {
            // Verifica se há lançamentos usando esta categoria
            $count = Database::fetch(
                "SELECT COUNT(*) AS total FROM painel_obras_lancamentos WHERE categoria_id = ?",
                [$id]
            );
            
            if ($count && $count['total'] > 0) {
                $this->json(['ok' => false, 'error' => 'Esta categoria possui lançamentos vinculados e não pode ser excluída.'], 422);
                return;
            }
            
            Database::query("DELETE FROM painel_obras_categorias_servico WHERE id = ?", [$id]);
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao deletar categoria: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Erro ao deletar.'], 500);
        }
    }
}
