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
        error_log('[PainelObras/index] ===== INÍCIO =====');
        
        try {
            $company     = (string) $this->input('company', 'brooks');
            if (!NiboService::isValidCompany($company)) $company = 'brooks';
            $competencia = (string) $this->input('periodo', date('Y-m'));
            
            error_log('[PainelObras/index] Company: ' . $company . ', Competência: ' . $competencia);

            // Busca categorias que TÊM gastos > 0 para passar ao frontend
            $categoriasObra = [];
            try {
                error_log('[PainelObras/index] Chamando getDashboardData...');
                $dashboardData = PainelObrasService::getDashboardData($competencia, $company);
                error_log('[PainelObras/index] getDashboardData executado com sucesso');
                
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
                error_log('[PainelObras/index] Categorias processadas: ' . count($categoriasObra));
            } catch (\Throwable $e) {
                error_log('[PainelObras/index] ERRO em getDashboardData: ' . $e->getMessage());
                error_log('[PainelObras/index] Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
                error_log('[PainelObras/index] Trace: ' . $e->getTraceAsString());
                // Se falhar, usa array vazio (visão limpa)
                $categoriasObra = [];
            }

            error_log('[PainelObras/index] Renderizando view...');
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
            error_log('[PainelObras/index] ===== FIM (sucesso) =====');
        } catch (\Throwable $e) {
            error_log('[PainelObras/index] ===== ERRO FATAL =====');
            error_log('[PainelObras/index] Tipo: ' . get_class($e));
            error_log('[PainelObras/index] Mensagem: ' . $e->getMessage());
            error_log('[PainelObras/index] Arquivo: ' . $e->getFile());
            error_log('[PainelObras/index] Linha: ' . $e->getLine());
            error_log('[PainelObras/index] Stack Trace:');
            error_log($e->getTraceAsString());
            throw $e; // Re-lança para gerar o 500
        }
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
                        "SELECT categoria_id, valor_previsto 
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
            foreach ($custos as $catId => $valor) {
                if ($valor > 0) {
                    Database::insert('painel_obras_custos_previstos', [
                        'obra_id' => $id,
                        'categoria_id' => (int)$catId,
                        'valor_previsto' => (float)$valor,
                    ]);
                }
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
            // Remove custos antigos
            Database::query("DELETE FROM painel_obras_custos_previstos WHERE obra_id = ?", [$id]);
            
            // Insere novos
            foreach ($custos as $catId => $valor) {
                if ($valor > 0) {
                    Database::insert('painel_obras_custos_previstos', [
                        'obra_id' => $id,
                        'categoria_id' => (int)$catId,
                        'valor_previsto' => (float)$valor,
                    ]);
                }
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

    public function obrasCategorias(): void
    {
        $company = (string) $this->input('company', 'brooks');
        $periodo = (string) $this->input('periodo', date('Y-m'));
        
        try {
            // Busca dados do dashboard para pegar apenas categorias com gastos REAIS
            $dashboardData = PainelObrasService::getDashboardData($periodo, $company);
            
            // Pega categorias que têm gastos ou a pagar (valores > 0)
            $categoriasComGasto = [];
            foreach (($dashboardData['gasto_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0) {
                    $categoriasComGasto[] = $cat;
                }
            }
            
            $categoriasComAPagar = [];
            foreach (($dashboardData['a_pagar_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0) {
                    $categoriasComAPagar[] = $cat;
                }
            }
            
            // Combina e remove duplicados
            $categorias = array_unique(array_merge($categoriasComGasto, $categoriasComAPagar));
            
            // Remove "Sem categoria / Não mapeado" da lista
            $categorias = array_filter($categorias, fn($cat) => $cat !== 'Sem categoria / Não mapeado');
            
            // Ordena alfabeticamente
            sort($categorias);
            
            error_log('[PainelObras] Categorias COM gastos > 0 retornadas: ' . count($categorias) . ' - ' . implode(', ', array_slice($categorias, 0, 5)));
            
            // Retorna no formato esperado pelo frontend (separado por tipo)
            $this->json([
                'Construção' => $categorias,
                'Reforma' => $categorias, // Mesmas categorias para ambos os tipos
            ]);
        } catch (\Throwable $e) {
            error_log('[PainelObrasController] Erro ao buscar categorias: ' . $e->getMessage());
            // Fallback vazio se houver erro
            $this->json(['Construção' => [], 'Reforma' => []]);
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

    // ─── Orçamento ────────────────────────────────────────────────────

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

    public function orcamentoData(): void
    {
        $obraId = (int)$this->input('obra_id',0);
        try {
            $v = PainelObra::versaoVigente($obraId);
            $linhas = $v ? Database::fetchAll(
                "SELECT * FROM painel_obras_orcamento WHERE obra_id = ? AND versao = ? ORDER BY categoria_obra",[$obraId,$v]
            ) : [];
            $historico = PainelObra::historicoOrcamento($obraId);
        } catch (\Throwable $e) { $v=null; $linhas=[]; $historico=[]; }
        $this->json(['ok'=>true,'versao'=>$v,'linhas'=>$linhas,'historico'=>$historico]);
    }

    public function orcamentoSalvar(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        $obraId = (int)$this->input('obra_id',0);
        try { $obra = PainelObra::find($obraId); } catch (\Throwable $e) { $this->json(['ok'=>false,'error'=>'Migration não executada.'],500); return; }
        if (!$obra) { $this->json(['ok'=>false,'error'=>'Obra não encontrada.'],404); return; }
        $user    = Auth::user();
        $vigencia= (string)$this->input('data_vigencia',date('Y-m-d'));
        $motivo  = trim((string)$this->input('motivo_revisao',''));
        $valores = $this->input('valores',[]);
        $v = (PainelObra::versaoVigente($obraId) ?? 0) + 1;
        if ($v >= 2 && !$motivo) { $this->json(['ok'=>false,'error'=>'Motivo da revisão obrigatório a partir da v2.'],422); return; }
        
        // Busca apenas categorias com gastos > 0 (visão limpa)
        $categorias = [];
        try {
            $competencia = date('Y-m');
            $dashboardData = PainelObrasService::getDashboardData($competencia, 'brooks');
            
            foreach (($dashboardData['gasto_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado') {
                    $categorias[] = $cat;
                }
            }
            
            foreach (($dashboardData['a_pagar_por_cat'] ?? []) as $cat => $valor) {
                if ($valor > 0 && $cat !== 'Sem categoria / Não mapeado' && !in_array($cat, $categorias)) {
                    $categorias[] = $cat;
                }
            }
        } catch (\Throwable $e) {
            $categorias = [];
        }
        
        // Verifica se há algum valor preenchido
        $totalOrcado = 0;
        foreach ($valores as $cat => $val) {
            $valorParsed = is_numeric($val) ? (float)$val : $this->parseMoeda($val);
            if ($valorParsed > 0) {
                $totalOrcado += $valorParsed;
                // Adiciona categoria se não estiver na lista
                if (!in_array($cat, $categorias)) {
                    $categorias[] = $cat;
                }
            }
        }
        
        // Valida se há pelo menos um valor
        if ($totalOrcado <= 0) {
            $this->json(['ok'=>false,'error'=>'Informe pelo menos um valor orçado.'],422);
            return;
        }
        
        // Salva apenas categorias que foram preenchidas OU que têm gastos
        $categoriasInseridas = 0;
        foreach ($categorias as $cat) {
            $valorOrcado = 0;
            
            // Verifica se foi informado valor para esta categoria
            if (isset($valores[$cat])) {
                $valorOrcado = is_numeric($valores[$cat]) ? (float)$valores[$cat] : $this->parseMoeda($valores[$cat]);
            }
            
            // Insere apenas se houver valor > 0
            if ($valorOrcado > 0) {
                try {
                    Database::query(
                        "INSERT INTO painel_obras_orcamento (obra_id,categoria_obra,valor_orcado,versao,data_vigencia,motivo_revisao,created_by) VALUES (?,?,?,?,?,?,?)",
                        [$obraId,$cat,$valorOrcado,$v,$vigencia,$motivo?:null,$user['id']]
                    );
                    $categoriasInseridas++;
                } catch (\Throwable $e) {
                    error_log('[PainelObrasController] Erro ao inserir orçamento: ' . $e->getMessage());
                }
            }
        }
        
        if ($categoriasInseridas === 0) {
            $this->json(['ok'=>false,'error'=>'Nenhuma categoria foi salva. Verifique os valores informados.'],422);
            return;
        }
        
        $this->json(['ok'=>true,'versao'=>$v,'inseridas'=>$categoriasInseridas]);
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
