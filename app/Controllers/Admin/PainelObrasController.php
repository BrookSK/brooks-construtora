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
    private const CATEGORIAS_OBRA = [
        'Material Básico', 'Mão de Obra', 'Equipamento/Locação',
        'Material de Acabamento', 'Instalações',
        'Administração/Indiretos', 'Projetos e Taxas', 'Imprevistos',
    ];

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

        $this->view('admin.painel_obras.index', [
            'pageTitle'      => 'Painel de Obras',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'company'        => $company,
            'companies'      => NiboService::companies(),
            'competencia'    => $competencia,
            'categoriasObra' => self::CATEGORIAS_OBRA,
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
            foreach ($obras as &$o) { $o['completude'] = $this->completude($o); }
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
            'created_by'      => $user['id'],
        ]);
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
            'updated_by'      => $user['id'],
        ]);
        $this->json(['ok'=>true]);
    }

    public function obraDelete(): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false],400); return; }
        try { PainelObra::deleteById((int)$this->input('id',0)); } catch (\Throwable $e) {}
        $this->json(['ok'=>true]);
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
        try { $obras = PainelObra::allWithFilter(); } catch (\Throwable $e) { $obras = []; }
        $this->view('admin.painel_obras.orcamento',[
            'pageTitle'      => 'Orçamento por Obra',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'obras'          => $obras,
            'categoriasObra' => self::CATEGORIAS_OBRA,
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
        foreach (self::CATEGORIAS_OBRA as $cat) {
            Database::query(
                "INSERT INTO painel_obras_orcamento (obra_id,categoria_obra,valor_orcado,versao,data_vigencia,motivo_revisao,created_by) VALUES (?,?,?,?,?,?,?)",
                [$obraId,$cat,$this->parseMoeda($valores[$cat]??'0'),$v,$vigencia,$motivo?:null,$user['id']]
            );
        }
        $this->json(['ok'=>true,'versao'=>$v]);
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

        $this->view('admin.painel_obras.depara',[
            'pageTitle'      => 'De-para de Categorias',
            'currentPage'    => 'painel_obras',
            'user'           => Auth::user(),
            'flash'          => $this->getFlash(),
            'catMap'         => $catMap,
            'deParaMap'      => $deParaMap,
            'naoMapeadas'    => $naoMapeadas,
            'semEntrada'     => $semEntrada,
            'categoriasObra' => self::CATEGORIAS_OBRA,
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
