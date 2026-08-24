<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\ClientProject;
use App\Models\Briefing;
use App\Models\ContractTemplate;
use App\Models\ContractObject;
use App\Models\ContractorCompany;
use App\Services\OpenAIService;

class BriefingController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
    }

    // =================================================================
    // AUTO-MIGRATE
    // =================================================================

    private function ensureTables(): void
    {
        $files = [
            ROOT_PATH . '/database/migrations/033_create_briefing_contracts.sql',
            ROOT_PATH . '/database/migrations/034_briefing_contractor_and_fields.sql',
        ];
        $pdo = Database::getConnection();
        foreach ($files as $f) {
            if (!file_exists($f)) continue;
            $raw = file_get_contents($f);
            $raw = preg_replace('/--[^\n]*/', '', $raw);
            $raw = preg_replace('/\/\*.*?\*\//s', '', $raw);
            foreach ($this->splitSql($raw) as $stmt) {
                if (trim($stmt) === '') continue;
                try { $pdo->exec($stmt); }
                catch (\PDOException $e) {
                    $m = $e->getMessage();
                    if (stripos($m,'already exists')===false && stripos($m,'Duplicate column')===false && stripos($m,'Duplicate entry')===false)
                        error_log('[Briefing] ' . $m);
                }
            }
        }
    }

    private function splitSql(string $raw): array
    {
        $stmts = []; $cur = ''; $inStr = false; $sc = '';
        for ($i=0,$l=strlen($raw); $i<$l; $i++) {
            $ch = $raw[$i];
            if (!$inStr && ($ch==="'" || $ch==='"')) { $inStr=true; $sc=$ch; $cur.=$ch; continue; }
            if ($inStr) { if ($ch==='\\' && $i+1<$l) { $cur.=$ch.$raw[++$i]; continue; } if ($ch===$sc) $inStr=false; $cur.=$ch; continue; }
            if ($ch===';') { $s=trim($cur); if ($s!=='') $stmts[]=$s; $cur=''; continue; }
            $cur.=$ch;
        }
        if (trim($cur)!=='') $stmts[]=trim($cur);
        return $stmts;
    }

    // =================================================================
    // INDEX (mesclado: suporta statusFilter do remoto + ensureTables do local)
    // =================================================================

    public function index(): void
    {
        $statusFilter = trim($this->input('status', ''));

        try {
            $projects = ClientProject::allWithBriefing(100, $statusFilter);
        } catch (\PDOException $e) {
            if (stripos($e->getMessage(), "doesn't exist") !== false
                || stripos($e->getMessage(), 'exist') !== false) {
                $this->ensureTables();
                $projects = ClientProject::allWithBriefing(100, $statusFilter);
            } else {
                throw $e;
            }
        }

        $this->view('admin.briefing.index', [
            'user'         => Auth::user(),
            'flash'        => $this->getFlash(),
            'projects'     => $projects,
            'mode'         => 'list',
            'statusFilter' => $statusFilter,
        ]);
    }

    // =================================================================
    // CREATE
    // =================================================================

    public function create(): void
    {
        try {
            $contractors = ContractorCompany::allActive();
        } catch (\PDOException $e) {
            if (stripos($e->getMessage(), "doesn't exist") !== false || stripos($e->getMessage(), 'exist') !== false) {
                $this->ensureTables();
                $contractors = ContractorCompany::allActive();
            } else {
                $contractors = [];
            }
        }

        $this->view('admin.briefing.index', [
            'user'=>Auth::user(), 'flash'=>$this->getFlash(), 'mode'=>'create',
            'templates'=>ContractTemplate::all('is_default DESC, id ASC'),
            'defaultTemplate'=>ContractTemplate::getDefault(),
            'contractors'=>$contractors,
            'projects'=>ClientProject::allWithBriefing(100),
        ]);
    }

    // =================================================================
    // STORE
    // =================================================================

    public function store(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/briefing'); return; }
        $userId = (int)Auth::id();
        $email = trim($this->input('client_email',''));
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error','E-mail inválido.'); $this->redirect('/admin/briefing/create'); return;
        }
        $pid = ClientProject::create($this->collectProject($email, $userId));
        Briefing::create($this->collectBriefing($pid, $userId));
        $this->setFlash('success','Briefing salvo com sucesso!');
        $this->redirect('/admin/briefing/edit/' . $pid);
    }

    // =================================================================
    // EDIT
    // =================================================================

    public function edit(string $id = ''): void
    {
        $pid = (int)($id ?: $this->input('id'));
        $project = ClientProject::find($pid);
        if (!$project) { $this->setFlash('error','Projeto não encontrado.'); $this->redirect('/admin/briefing'); return; }

        $briefing = Briefing::findByProject($pid);

        try {
            $contractor = (!empty($briefing['contractor_company_id'])) ? ContractorCompany::find((int)$briefing['contractor_company_id']) : null;
            $contractors = ContractorCompany::allActive();
        } catch (\PDOException $e) {
            if (stripos($e->getMessage(), "doesn't exist") !== false || stripos($e->getMessage(), 'exist') !== false) {
                $this->ensureTables();
                $contractor = (!empty($briefing['contractor_company_id'])) ? ContractorCompany::find((int)$briefing['contractor_company_id']) : null;
                $contractors = ContractorCompany::allActive();
            } else {
                $contractor = null;
                $contractors = [];
            }
        }

        $contractObject = $briefing ? ContractObject::latestByBriefing((int)$briefing['id']) : null;

        $this->view('admin.briefing.index', [
            'user'=>Auth::user(), 'flash'=>$this->getFlash(), 'mode'=>'edit',
            'project'=>$project, 'briefing'=>$briefing,
            'templates'=>ContractTemplate::all('is_default DESC, id ASC'),
            'defaultTemplate'=>ContractTemplate::getDefault(),
            'contractors'=>$contractors,
            'selectedContractor'=>$contractor,
            'contractObject'=>$contractObject,
            'projects'=>ClientProject::allWithBriefing(100),
        ]);
    }

    // =================================================================
    // UPDATE
    // =================================================================

    public function update(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/briefing'); return; }
        $pid = (int)$this->input('project_id');
        $bid = (int)$this->input('briefing_id');
        if (!ClientProject::find($pid)) { $this->setFlash('error','Projeto não encontrado.'); $this->redirect('/admin/briefing'); return; }

        $email = trim($this->input('client_email',''));
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error','E-mail inválido.'); $this->redirect('/admin/briefing/edit/'.$pid); return;
        }

        $pd = $this->collectProject($email, null); unset($pd['created_by'],$pd['created_at']); $pd['updated_at']=date('Y-m-d H:i:s');
        ClientProject::updateById($pid, $pd);

        $bd = $this->collectBriefing($pid, (int)Auth::id()); unset($bd['client_project_id'],$bd['created_by'],$bd['created_at']); $bd['updated_at']=date('Y-m-d H:i:s');
        if ($bid > 0) Briefing::updateById($bid, $bd);
        else { $bd['client_project_id']=$pid; $bd['created_by']=(int)Auth::id(); $bd['created_at']=date('Y-m-d H:i:s'); Briefing::create($bd); }

        $this->setFlash('success','Briefing atualizado com sucesso!');
        $this->redirect('/admin/briefing/edit/'.$pid);
    }

    // =================================================================
    // SAVE AJAX
    // =================================================================

    public function saveAjax(): void
    {
        if (!$this->isPost()) { $this->json(['error'=>'Método inválido.'],400); return; }
        $userId=(int)Auth::id(); $pid=(int)$this->input('project_id',0); $bid=(int)$this->input('briefing_id',0);
        $email=trim($this->input('client_email',''));
        if (!empty($email)&&!filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->json(['error'=>'E-mail inválido.'],422); return; }
        if (empty(trim($this->input('client_name','')))) { $this->json(['error'=>'Nome do cliente obrigatório.'],422); return; }

        $pd=$this->collectProject($email,$userId); $bd=$this->collectBriefing(0,$userId);

        if ($pid>0 && ClientProject::find($pid)) {
            unset($pd['created_by'],$pd['created_at']); $pd['updated_at']=date('Y-m-d H:i:s');
            ClientProject::updateById($pid,$pd);
            unset($bd['client_project_id'],$bd['created_by'],$bd['created_at']); $bd['updated_at']=date('Y-m-d H:i:s');
            if ($bid>0) Briefing::updateById($bid,$bd);
            else { $bd['client_project_id']=$pid; $bd['created_by']=$userId; $bd['created_at']=date('Y-m-d H:i:s'); $bid=Briefing::create($bd); }
        } else {
            $pid=ClientProject::create($pd); $bd['client_project_id']=$pid; $bid=Briefing::create($bd);
        }
        $this->json(['success'=>true,'project_id'=>$pid,'briefing_id'=>$bid,'edit_url'=>'/admin/briefing/edit/'.$pid]);
    }

    // =================================================================
    // DELETE
    // =================================================================

    public function delete(): void
    {
        if (!$this->isPost()) { $this->redirect('/admin/briefing'); return; }
        ClientProject::deleteById((int)$this->input('id'));
        $this->setFlash('success','Projeto excluído com sucesso.'); $this->redirect('/admin/briefing');
    }

    // =================================================================
    // TRANSCRIÇÃO ÁUDIO (Whisper)
    // =================================================================

    public function transcribeAudio(): void
    {
        if (!$this->isPost()) { $this->json(['error'=>'Método inválido.'],400); return; }
        if (!isset($_FILES['audio'])||$_FILES['audio']['error']!==UPLOAD_ERR_OK) { $this->json(['error'=>'Áudio inválido.'],400); return; }
        $file=$_FILES['audio']; $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if ($file['size']>25*1024*1024) { $this->json(['error'=>'Máximo 25MB.'],400); return; }
        $dir=ROOT_PATH.'/public/uploads/audio_tmp/'; if(!is_dir($dir))mkdir($dir,0755,true);
        if(empty($ext)||$ext==='blob')$ext='webm';
        $tmp=$dir.'w_'.Auth::id().'_'.time().'.'.$ext;
        if(!move_uploaded_file($file['tmp_name'],$tmp)){$this->json(['error'=>'Erro ao salvar.'],500);return;}
        try{$ai=new OpenAIService();$t=$ai->transcribeAudio($tmp,'pt');if(file_exists($tmp))unlink($tmp);$this->json(['success'=>true,'text'=>$t]);}
        catch(\Exception $e){if(file_exists($tmp))unlink($tmp);$this->json(['error'=>$e->getMessage()],500);}
    }

    // =================================================================
    // POLIMENTO DE TEXTO (do remoto)
    // =================================================================

    public function polishText(): void
    {
        if (!$this->isPost()) { $this->json(['error'=>'Método inválido.'],400); return; }
        $text = trim($this->input('text', ''));
        if (empty($text)) { $this->json(['error'=>'Texto vazio.'],400); return; }

        if (str_word_count($text) < 3) {
            $text = preg_replace('/\s{2,}/', ' ', $text);
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
            $this->json(['success'=>true,'text'=>$text]);
            return;
        }

        try {
            $ai = new OpenAIService();
            $polished = $ai->polishText($text);
            $this->json(['success'=>true,'text'=>$polished]);
        } catch (\Exception $e) {
            $this->json(['success'=>true,'text'=>$text]);
        }
    }

    // =================================================================
    // IMPORTAÇÃO PDF (Demanda #61)
    // =================================================================

    public function importPdf(): void
    {
        if (!$this->isPost()) { $this->json(['error'=>'Método inválido.'],400); return; }
        if (!isset($_FILES['pdf'])||$_FILES['pdf']['error']!==UPLOAD_ERR_OK) { $this->json(['error'=>'PDF inválido.'],400); return; }
        $file=$_FILES['pdf']; $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if ($file['type']!=='application/pdf'&&$ext!=='pdf') { $this->json(['error'=>'Envie um PDF.'],400); return; }
        if ($file['size']>15*1024*1024) { $this->json(['error'=>'Máximo 15MB.'],400); return; }

        $dir=ROOT_PATH.'/public/uploads/briefing_tmp/'; if(!is_dir($dir))mkdir($dir,0755,true);
        $tmp=$dir.'bf_'.Auth::id().'_'.time().'.pdf';
        if(!move_uploaded_file($file['tmp_name'],$tmp)){$this->json(['error'=>'Erro ao salvar.'],500);return;}

        try {
            $ai=new OpenAIService();
            $fields=$ai->extractBriefingFromPdf($tmp,$file['name']);
            if(file_exists($tmp))unlink($tmp);
            $this->json(['success'=>true,'fields'=>$fields]);
        } catch(\Exception $e) {
            if(file_exists($tmp))unlink($tmp);
            $this->json(['error'=>$e->getMessage()],500);
        }
    }

    // =================================================================
    // GERAÇÃO DO CONTRATO (Demanda #61 — variáveis corrigidas)
    // =================================================================

    public function generateObject(): void
    {
        if (!$this->isPost()) { $this->json(['error'=>'Método inválido.'],400); return; }
        $bid=(int)$this->input('briefing_id'); $tid=(int)$this->input('template_id'); $cp=trim($this->input('custom_prompt',''));
        if ($bid<=0) { $this->json(['error'=>'Salve o briefing primeiro.'],400); return; }

        $briefing=Briefing::find($bid);
        if(!$briefing){$this->json(['error'=>'Briefing não encontrado.'],404);return;}
        $project=ClientProject::find((int)$briefing['client_project_id']);
        if(!$project){$this->json(['error'=>'Projeto não encontrado.'],404);return;}
        $contractor=!empty($briefing['contractor_company_id'])?ContractorCompany::find((int)$briefing['contractor_company_id']):null;

        $template=$tid>0?ContractTemplate::find($tid):ContractTemplate::getDefault();
        $promptTpl=$cp?:($template['prompt_template']??'');
        if(empty($promptTpl)){$this->json(['error'=>'Nenhum modelo disponível.'],400);return;}

        $vars=$this->buildVariablesMap($project,$briefing,$contractor);

        try {
            $ai=new OpenAIService();
            $result=$ai->generateContractObject($promptTpl,$vars);
            $oid=ContractObject::create(['briefing_id'=>$bid,'contract_template_id'=>$template['id']??null,'generated_text'=>$result['text'],'prompt_used'=>$result['prompt_used'],'status'=>'generated','created_by'=>(int)Auth::id(),'created_at'=>date('Y-m-d H:i:s')]);
            $this->json(['success'=>true,'object_id'=>$oid,'text'=>$result['text']]);
        } catch(\Exception $e) { $this->json(['error'=>$e->getMessage()],500); }
    }

    // =================================================================
    // APROVAR
    // =================================================================

    public function approveObject(): void
    {
        if(!$this->isPost()){$this->json(['error'=>'Método inválido.'],400);return;}
        $oid=(int)$this->input('object_id');
        if(!ContractObject::find($oid)){$this->json(['error'=>'Não encontrado.'],404);return;}
        ContractObject::approve($oid,(int)Auth::id());
        $this->json(['success'=>true]);
    }

    // =================================================================
    // TEMPLATES
    // =================================================================

    public function storeTemplate(): void
    {
        if(!$this->isPost()){$this->redirect('/admin/briefing');return;}
        $n=trim($this->input('template_name','')); $p=trim($this->input('prompt_template',''));
        if(empty($n)||empty($p)){$this->json(['error'=>'Nome e template obrigatórios.'],400);return;}
        $id=ContractTemplate::create(['name'=>$n,'description'=>trim($this->input('template_description','')),'prompt_template'=>$p,'is_default'=>0,'created_by'=>(int)Auth::id(),'created_at'=>date('Y-m-d H:i:s')]);
        $this->json(['success'=>true,'id'=>$id,'name'=>$n]);
    }

    public function updateTemplate(): void
    {
        if(!$this->isPost()){$this->json(['error'=>'Método inválido.'],400);return;}
        $id=(int)$this->input('template_id'); $n=trim($this->input('template_name','')); $p=trim($this->input('prompt_template',''));
        if(!$id||empty($n)||empty($p)){$this->json(['error'=>'Dados inválidos.'],400);return;}
        ContractTemplate::updateById($id,['name'=>$n,'description'=>trim($this->input('template_description','')),'prompt_template'=>$p,'updated_at'=>date('Y-m-d H:i:s')]);
        $this->json(['success'=>true]);
    }

    // =================================================================
    // CADASTRO EMPRESA CONTRATADA
    // =================================================================

    public function storeContractor(): void
    {
        if(!$this->isPost()){$this->json(['error'=>'Método inválido.'],400);return;}
        $name=trim($this->input('company_name',''));
        if(empty($name)){$this->json(['error'=>'Razão social obrigatória.'],422);return;}
        $id=ContractorCompany::create([
            'company_name'=>$name,'trade_name'=>trim($this->input('trade_name','')),
            'cnpj'=>preg_replace('/\D/','',$this->input('contractor_cnpj','')),
            'address'=>trim($this->input('contractor_address','')),
            'address_number'=>trim($this->input('contractor_address_number','')),
            'complement'=>trim($this->input('contractor_complement','')),
            'neighborhood'=>trim($this->input('contractor_neighborhood','')),
            'city'=>trim($this->input('contractor_city','')),
            'state'=>trim($this->input('contractor_state','')),
            'cep'=>preg_replace('/\D/','',$this->input('contractor_cep','')),
            'phone'=>preg_replace('/\D/','',$this->input('contractor_phone','')),
            'email'=>trim($this->input('contractor_email','')),
            'representative_name'=>trim($this->input('contractor_representative_name','')),
            'representative_role'=>trim($this->input('contractor_representative_role','')),
            'active'=>1,'created_at'=>date('Y-m-d H:i:s'),
        ]);
        $this->json(['success'=>true,'id'=>$id,'company'=>ContractorCompany::find($id)]);
    }

    // =================================================================
    // HELPERS
    // =================================================================

    private function collectProject(string $email, ?int $userId): array
    {
        $d=[
            'client_name'=>trim($this->input('client_name','')),
            'client_document'=>preg_replace('/\D/','',$this->input('client_document','')),
            'client_phone'=>preg_replace('/\D/','',$this->input('client_phone','')),
            'client_email'=>$email,
            'project_type'=>trim($this->input('project_type','')),
            'project_address'=>trim($this->input('project_address','')),
            'project_address_number'=>trim($this->input('project_address_number','')),
            'project_complement'=>trim($this->input('project_complement','')),
            'project_neighborhood'=>trim($this->input('project_neighborhood','')),
            'project_cep'=>preg_replace('/\D/','',$this->input('project_cep','')),
            'project_city'=>trim($this->input('project_city','')),
            'project_state'=>trim($this->input('project_state','')),
            'project_goal'=>trim($this->input('project_goal','')),
            'project_area'=>$this->input('project_area')?:null,
        ];
        if($userId!==null){$d['created_by']=$userId;$d['created_at']=date('Y-m-d H:i:s');}
        return $d;
    }

    private function collectBriefing(int $pid, int $userId): array
    {
        return [
            'client_project_id'=>$pid,
            'contractor_company_id'=>$this->input('contractor_company_id')?:null,
            'preferences'=>trim($this->input('preferences','')),
            'priorities'=>trim($this->input('priorities','')),
            'needs'=>trim($this->input('needs','')),
            'restrictions'=>trim($this->input('restrictions','')),
            'briefing_summary'=>trim($this->input('briefing_summary','')),
            'negotiation_details'=>trim($this->input('negotiation_details','')),
            'contract_value'=>$this->input('contract_value')?:null,
            'down_payment'=>$this->input('down_payment')?:null,
            'discount_value'=>$this->input('discount_value')?:null,
            'discount_percent'=>$this->input('discount_percent')?:null,
            'payment_installments'=>$this->input('payment_installments')?:null,
            'payment_details'=>trim($this->input('payment_details','')),
            'payment_method'=>trim($this->input('payment_method','')),
            'project_number'=>trim($this->input('project_number','')),
            'start_date'=>$this->input('start_date')?:null,
            'end_date'=>$this->input('end_date')?:null,
            'deadline_days'=>$this->input('deadline_days')?:null,
            'clauses'=>trim($this->input('clauses','')),
            'responsible_name'=>trim($this->input('responsible_name','')),
            'responsible_role'=>trim($this->input('responsible_role','')),
            'created_by'=>$userId,'created_at'=>date('Y-m-d H:i:s'),
        ];
    }

    private function buildVariablesMap(array $project, array $briefing, ?array $contractor): array
    {
        $fDoc=function(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===11)return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/','$1.$2.$3-$4',$d);if(strlen($d)===14)return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/','$1.$2.$3/$4-$5',$d);return $d;};
        $fPhone=function(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===11)return preg_replace('/(\d{2})(\d{5})(\d{4})/','($1) $2-$3',$d);if(strlen($d)===10)return preg_replace('/(\d{2})(\d{4})(\d{4})/','($1) $2-$3',$d);return $d;};
        $fCep=function(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===8)return preg_replace('/(\d{5})(\d{3})/','$1-$2',$d);return $d;};
        $fDate=function(?string $v):string{if(empty($v))return '';$dt=\DateTime::createFromFormat('Y-m-d',$v);return $dt?$dt->format('d/m/Y'):$v;};
        $fMoney=function($v):string{if(empty($v)||(float)$v==0)return '';return 'R$ '.number_format((float)$v,2,',','.');};
        $fPct=function($v):string{if(empty($v)||(float)$v==0)return '';return number_format((float)$v,2,',','.').'%';};

        $v=[
            'cliente_nome'=>$project['client_name']??'',
            'cliente_documento'=>$fDoc($project['client_document']??''),
            'cliente_cpf'=>$fDoc($project['client_document']??''),
            'cliente_cnpj'=>$fDoc($project['client_document']??''),
            'cliente_telefone'=>$fPhone($project['client_phone']??''),
            'cliente_email'=>$project['client_email']??'',
            'tipo_obra'=>$project['project_type']??'',
            'endereco_obra'=>$project['project_address']??'',
            'numero_obra'=>$project['project_address_number']??'',
            'complemento_obra'=>$project['project_complement']??'',
            'bairro_obra'=>$project['project_neighborhood']??'',
            'cidade_obra'=>$project['project_city']??'',
            'estado_obra'=>$project['project_state']??'',
            'cep_obra'=>$fCep($project['project_cep']??''),
            'objetivo'=>$project['project_goal']??'',
            'area_m2'=>$project['project_area']??'',
            'endereco'=>$project['project_address']??'',
            'cidade'=>$project['project_city']??'',
            'preferencias'=>$briefing['preferences']??'',
            'prioridades'=>$briefing['priorities']??'',
            'necessidades'=>$briefing['needs']??'',
            'restricoes'=>$briefing['restrictions']??'',
            'resumo_briefing'=>$briefing['briefing_summary']??'',
            'detalhes_negociacao'=>$briefing['negotiation_details']??'',
            'briefing'=>implode("\n\n",array_filter([
                !empty($briefing['preferences'])?"Preferências: ".$briefing['preferences']:'',
                !empty($briefing['priorities'])?"Prioridades: ".$briefing['priorities']:'',
                !empty($briefing['needs'])?"Necessidades: ".$briefing['needs']:'',
                !empty($briefing['restrictions'])?"Restrições: ".$briefing['restrictions']:'',
                !empty($briefing['briefing_summary'])?"Resumo: ".$briefing['briefing_summary']:'',
                !empty($briefing['negotiation_details'])?"Negociação: ".$briefing['negotiation_details']:'',
            ])),
            'valor_contrato'=>$fMoney($briefing['contract_value']??null),
            'entrada'=>$fMoney($briefing['down_payment']??null),
            'desconto_valor'=>$fMoney($briefing['discount_value']??null),
            'desconto_percentual'=>$fPct($briefing['discount_percent']??null),
            'forma_pagamento'=>$briefing['payment_method']??'',
            'parcelas'=>$briefing['payment_installments']??'',
            'detalhes_parcelamento'=>$briefing['payment_details']??'',
            'parcelamento'=>!empty($briefing['payment_installments'])?$briefing['payment_installments'].'x - '.($briefing['payment_details']??''):($briefing['payment_details']??''),
            'numero_projeto'=>$briefing['project_number']??'',
            'data_inicio'=>$fDate($briefing['start_date']??''),
            'data_conclusao'=>$fDate($briefing['end_date']??''),
            'prazo_dias'=>$briefing['deadline_days']??'',
            'clausulas'=>$briefing['clauses']??'',
            'responsavel_nome'=>$briefing['responsible_name']??'',
            'responsavel_cargo'=>$briefing['responsible_role']??'',
            'contratada_razao_social'=>$contractor['company_name']??'',
            'contratada_nome_fantasia'=>$contractor['trade_name']??'',
            'contratada_cnpj'=>$fDoc($contractor['cnpj']??''),
            'contratada_endereco'=>$contractor['address']??'',
            'contratada_numero'=>$contractor['address_number']??'',
            'contratada_complemento'=>$contractor['complement']??'',
            'contratada_bairro'=>$contractor['neighborhood']??'',
            'contratada_cidade'=>$contractor['city']??'',
            'contratada_estado'=>$contractor['state']??'',
            'contratada_cep'=>$fCep($contractor['cep']??''),
            'contratada_telefone'=>$fPhone($contractor['phone']??''),
            'contratada_email'=>$contractor['email']??'',
            'contratada_representante'=>$contractor['representative_name']??'',
            'contratada_representante_cargo'=>$contractor['representative_role']??'',
        ];
        return $v;
    }
}
