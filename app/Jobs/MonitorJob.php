<?php

namespace App\Jobs;

use App\Dtos\AcaoDTO;
use App\Dtos\MensagemChatDTO;
use App\Events\AcaoExecutadaEvent;
use App\Events\MensagemChatEnviada;
use App\Models\Lances;
use App\Models\Parametros;
use App\Models\Processo;
use App\Models\ProcessoFaseEmpate;
use App\Models\ProcessoItemIniciado;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use PDO;
use Sh3\Bibliotecas\Util\Util;
 use Sh3\Bibliotecas\Util\Common;
 use Sh3\Bibliotecas\Monitor\Monitor;

class MonitorJob implements ShouldQueue
{
    use Queueable;
    public $timeout = 0;
    
    public $commonSlim;
    public $utilSlim;
    public $monitorSlim;
    /**
     * Create a new job instance.
     */
    public function __construct(public array $args)
    {

      
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $data        = \json_decode($this->args['data']);
        $intFaseItem = (int) $data->INT_FASE_ITEM;
        $verificar   = true;

        $this->carregarBibliotecaSh3($data);

        $this->monitorSlim->monitorarFase($intFaseItem);

        return;

    }

    public function handle2(): void
    {
        
        $data        = \json_decode($this->args['data']);
        $intFaseItem = (int) $data->INT_FASE_ITEM;
        $verificar   = true;

        $this->carregarBibliotecaSh3();

        while ($verificar) {

            $result = $this->getProcessoItemIniciadoCollection($intFaseItem);

            if ($result->isEmpty() || $result->first()->DH_FIM != null) {
                $verificar = false;
                continue;
            } 

            $lgRand = 'N';
            // $suspensaoGeral = false;
            
            if ($result->first()->LG_SUSP == 'S' || !$this->isPregoeiroAtivo($result->first())) {
                
                if ($result->first()->DH_SUSP === '') {

                    //TODO: atualizar campo DH_SUSP de Processo Item Iniciado com ID $intFaseItem

                    $lgAuto = 'N';

                    //TODO: existe uma if aqui que aparentemente nunca irá dar true

                    $trigger = array(
                        'INT_PROC' => $result->first()->INT_PROC,
                        'INT_FASE_ITEM' => $intFaseItem,
                        'CNPJ_PREF' => $result->first()->CNPJ_PREF,
                        'LG_AUTO' => $lgAuto
                    );

                    //TODO: criar Model "PROCESSO_SUSPENSO_TRIGGER" e dar save() com os parâmetros acima
                    //TODO: criar Model  "TIMER_TRIGGER" e chamar função delete() com o $intFaseItem

                } else {
                    $info['data'] =
                        array(
                            'time' => 'Sessão suspensa',
                            'INT_FASE_ITEM' => $intFaseItem,
                            'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                            'LG_PISC' => 'N',
                            'LG_DISA' => 'S'
                        );

                    $info['tipo'] = 'suspenso';

                    // TODO: informarAcaoPeloServidor('acao', $result->first()->INT_PROC, $info);
                    $verificar = false;
                }
            
            } elseif ($result->first()->LG_SUSP !== 'S') {

                $mnLicitante = 0;
                
                if ($result->first()->DH_SUSP !== null) {
                    
                    $diferenca = '00:00:00';
                    
                    try {
                        $dataSuspensao = new DateTime($result->first()->DH_SUSP);
                        $dataCalculoFim = new DateTime($result->first()->DH_CALC_FIM);

                        $intervalo = $dataSuspensao->diff($dataCalculoFim);
                        $diferenca = $intervalo->format('%H:%I:%S');

                    } catch (Exception $e) {
                        //TODO: Lançar exceção
                    }

                    $data = explode(':', $diferenca);
                    $formatador = 'PT';

                    $formatador .= ($data[0] != '00') ? $data[0] . 'H' : '';
                    $formatador .= ($data[1] != '00') ? $data[1] . 'M' : '';
                    $formatador .= ($data[2] != '00') ? $data[2] . 'S' : '';

                    $date = new DateTime('now');

                    try {
                        if ($formatador != 'PT') {
                            $date->add(new DateInterval($formatador));
                        }

                        $dataCalculoFim = $date->format('Y-m-d H:i:s');

                        //TODO: atualizar campos abaixo de Processo Item Iniciado com ID $intFaseItem
                        $updateProcessoItenIniciado = ProcessoItemIniciado::find($result->first()->INT_FASE_ITEM);
                        $updateProcessoItenIniciado->INT_FASE_ITEM = $intFaseItem;
                        $updateProcessoItenIniciado->DH_CALC_FIM = $date->format('Y-m-d H:i:s');
                        $updateProcessoItenIniciado->DH_SUSP = '';

                        $updateProcessoItenIniciado->update();
                        
                    } finally {
                        unset($date);
                    }
                }else{
                    $dataCalculoFim = $result->first()->DH_CALC_FIM;
                }

                $intervalo = new DateInterval('PT0S');
                try {
                    $nowDateTime = new DateTime('now');
                    $dataCalculoFim = new DateTime($dataCalculoFim);
                    $intervalo = $dataCalculoFim->diff($nowDateTime);
                } finally{
                    unset($nowDateTime);
                    unset($dataCalculoFim);
                }

                if ($intervalo->invert < 1) {
                   
                    $info['data'] = array(
                        'id'                 => $result->first()->INT_PROC,
                        'time'               => 'Analisando',
                        'INT_FASE_ITEM'      => $intFaseItem,
                        'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                        'LG_DISA'            => 'S'
                    );
                    
                    $info['tipo'] = 'login';

                    $acaoDTO = new AcaoDTO([
                        'id' => $this->args['id'],
                        'acao' => '',
                        'tipo' => 'timer',
                        'data' => json_encode($info)
                    ]);

                    AcaoExecutadaEvent::dispatch($acaoDTO);

                    //TODO: informarAcaoPeloServidor('acao', $result->first()->INT_PROC, $info);
                    sleep(2);

                    $this->verificarCancelamentoEmAberto(
                        $result->first()->INT_PROC, $intFaseItem, $result->first()->STR_LOTE_ITEM_LICI
                    );

                    $newResult = $this->getProcessoItemIniciadoCollection($intFaseItem);

                    if ($newResult->first()->DH_CALC_FIM == $result->first()->DH_CALC_FIM) {
                        
                        if ($this->loopFase($newResult->first()->INT_FASE_TP, $result->first()->INT_PROC, $result->first()->STR_LOTE_ITEM_LICI, $intFaseItem)) {
                            $this->commonSlim->registrarItemRecusado($intFaseItem, $newResult->first()->INT_FASE_TP, $result->first()->INT_PROC, $result->first()->STR_LOTE_ITEM_LICI);
                        }

                        if ($newResult->first()->H_DUR_PREG == '00:00:00') {
                            $lgDisa = 'N';
                        } else {
                            $lgDisa = 'S';
                        }

                        if ($newResult->first()->INT_FASE_TP == '7' || $newResult->first()->INT_FASE_TP == '4' || $newResult->first()->INT_FASE_TP == '6') {
                            $lgCloseSocket = 'N';
                        } else {
                            $lgCloseSocket = 'S';
                        }

                        $this->registrarFimItemFase($intFaseItem, $result->first()->INT_PROC, $result->first()->STR_LOTE_ITEM_LICI, $newResult->first()->INT_FASE_TP, $lgDisa, $lgCloseSocket);
                        $verificar = false;
                    }
                
                } else {
                    $time = base64_encode($intervalo->format('%H:%I:%S'));

                    if (($result->first()->INT_FASE_TP != '11') && ($intervalo->format('%i') < 2)) {                                                                //controle de pisca
                        $piscar = ($intervalo->format('%s') % 3 != 0) ? $result->first()->INT_FASE_TP : 'N';
                    } else {
                        $piscar = 'N';
                        if ($result->first()->INT_FASE_TP == 11) {
                            $lgRand = 'S';
                        }
                    }

                    $info['data'] = array(
                        'id'  => $result->first()->INT_PROC,
                        'time' => $time,
                        'INT_FASE_ITEM' => $intFaseItem,
                        'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                        'MN_LC' => $mnLicitante,
                        'LG_PISC' => $piscar,
                        'LG_DISA' => 'N',
                        'LG_RAND' => $lgRand
                    );
                    $info['tipo'] = 'timer';

                    $acaoDTO = new AcaoDTO([
                        'id' => $this->args['id'],
                        'acao' => '',
                        'tipo' => 'timer',
                        'data' => json_encode($info)
                    ]);

                    AcaoExecutadaEvent::dispatch($acaoDTO);
                    //TODO: informarAcaoPeloServidor('acao', $result->first->INT_PROC, $info);
                }
                sleep(1);
            }
        }

        return;

    }

    public function carregarBibliotecaSh3($data): void{

        $config = [
            
            'settings' => [
                'displayErrorDetails' => true,        
                'db' => [
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'user'   => env('DB_USERNAME', 'root'),
                    'pass'   => env('DB_PASSWORD', ''),
                    'dbname' => env('DB_DATABASE', 'laravel')
                ]
            ],
            
        ];

        $appSlim = new \Slim\App($config);
        $container = $appSlim->getContainer();


        $container['db'] = function ($c){
        
                $db = $c['settings']['db'];
            
                $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'].';charset=utf8',$db['user'], $db['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                return $pdo;

        };
        
        $this->utilSlim    = new Util($container);
        $this->commonSlim  = new Common($this->utilSlim);
        $this->monitorSlim = new Monitor($this->commonSlim);

        $this->commonSlim->setSocketPort(env('SOCKET_PORT', '80'));
        $this->commonSlim->setSocketURL(env('SOCKET_URL','http://localhost:8000/graphql'));
        $this->commonSlim->setCONTA($data->INT_CTA);
        $this->commonSlim->setUSUARIO($data->INT_USU);
    }

    /**
     * @param int $intFaseItem
     * @return Collection
     */
    protected function getProcessoItemIniciadoCollection(int $intFaseItem): Collection
    {
        return ProcessoItemIniciado::query()
            ->select([
                'PROCESSO_ITEM_INICIADO.DH_CALC_FIM',
                'PROCESSO_ITEM_INICIADO.DH_FIM',
                'PROCESSO_ITEM_INICIADO.DH_SUSP',
                'PROCESSO_ITEM_INICIADO.CNPJ_PREF',
                'PROCESSO_ITEM_INICIADO.INT_PROC',
                'PROCESSO_ITEM_INICIADO.STR_LOTE_ITEM_LICI',
                'PROCESSO_FASE.INT_FASE_TP',
                'PROCESSO_FASE.H_DUR_PREG',
                'PROCESSO.LG_SUSP',
                'PROCESSO.DH_PREG_ATV',
                'FASE_TIPO.STR_CP',
                'FASE_TIPO.INT_FASE_TP',
            ])
            ->join('PROCESSO_FASE', function ($join) {
                $join->on('PROCESSO_FASE.INT_FASE', '=', 'PROCESSO_ITEM_INICIADO.INT_FASE')
                    ->whereColumn('PROCESSO_FASE.INT_PROC', 'PROCESSO_ITEM_INICIADO.INT_PROC')
                    ->whereColumn('PROCESSO_FASE.CNPJ_PREF', 'PROCESSO_ITEM_INICIADO.CNPJ_PREF');
            })
            ->join('FASE_TIPO', 'FASE_TIPO.INT_FASE_TP', '=', 'PROCESSO_FASE.INT_FASE_TP')
            ->join('PROCESSO', 'PROCESSO.INT_PROC', '=', 'PROCESSO_FASE.INT_PROC')
            ->where('PROCESSO_ITEM_INICIADO.INT_FASE_ITEM', $intFaseItem)
            ->get();
    }

    protected function isPregoeiroAtivo(ProcessoItemIniciado $processoItemIniciado): bool
    {   
       
        $parametros = Parametros::where('CNPF_PREF','=',$processoItemIniciado->CNPJ_PREF)->first();

        $tempoSuspensao = $parametros->INT_TMP_SUSP;
        
        $formatador = 'PT'.$tempoSuspensao.'M';               
        
        $DataAtividade =  ($processoItemIniciado->DH_PREG_ATV != '') ? $processoItemIniciado->DH_PREG_ATV : $processoItemIniciado->D_INI_DPT_PRG_E.' '.$processoItemIniciado->H_INI_DPT_PRG_E;
        
        $date = new \DateTime($DataAtividade);
        $date->add(new \DateInterval($formatador));

        $DATA = new \DateTime('now');

        $diff = $date->getTimestamp() - $DATA->getTimestamp();
        
        if ($diff <= 0){

            $infoChat['LG_COMENT'] = 'N';
            $infoChat['INT_PROC'] = $processoItemIniciado->INT_PROC;  
            $this->bloquearChat($processoItemIniciado->INT_PROC, $infoChat);

            $dados['INT_PROC']  = $processoItemIniciado->INT_PROC;
            $dados['LG_SUSP']   = 'S';
            
            $processo = Processo::find($processoItemIniciado->INT_PROC);
            $processo->LG_SUSP = 'S';

            $processo->update();

            if($MSG = $this->commonSlim->mensagens->renderizarMensagemSuspensao($processoItemIniciado->INT_PROC)){
                   //$this->enviarMensagemPeloServidor('comentarios',$dados['INT_PROC'],$MSG);


                   $mensagemChatDTO = new MensagemChatDTO([
                    "idComentario"  => $processoItemIniciado->INT_PROC,
                    "msg" => $MSG,
                    "filtro" => 0,
                    "idTipoConta" => false, 
                    "tipo"  => "mensagem",
                    "id" => \uniqid()
                   ]);

                    MensagemChatEnviada::dispatch($mensagemChatDTO);
            }

            return false;

        }else{
            
            return true;
        }
    }

    protected function bloquearChat(Int $intProc, array $infoChat): bool
    {
        $processo = Processo::find($intProc);
        $processo->LG_COMENT = 'S';

        if($processo->update()){

            // $MSG = $this->mensagens->renderizarMensagemBatePapo($INT_COMENT, $info['LG_COMENT']);
            // $this->enviarMensagemPeloServidor('comentarios', $INT_COMENT, $MSG);
             $saida['sucesso'] = true;

        }else{
            $saida['sucesso'] = false;
        }

        return  $saida['sucesso'];
    }

    protected function verificarCancelamentoEmAberto($intProc, $IntFaseItem, $strLoteItemLici): void
    {   
        $this->commonSlim->verificarCancelamentoEmAberto($intProc, $IntFaseItem, $strLoteItemLici);
        // TODO: Implementar essa função
    }

    protected function loopFase($intFaseTipo, $intProc, $strLoteItemLici, $intFaseItem = ''): bool
    {
        $this->commonSlim->carregarDadosProcesso($intProc);

        $validarLei = $this->commonSlim->validarLei();
       
        $return = $intFaseTipo == '3' || ($intFaseTipo == '12' && $validarLei && !$this->commonSlim->existeLanceNaFase($intFaseTipo, $intProc, $strLoteItemLici));
        
        if($intFaseItem != '' && !$return && $intFaseTipo == '12' && $validarLei ){ //INT_TP_PRG_E = '2' && INT_LEI != '3'
            $this->registrarItemClassificadoQualificado($intFaseItem,$intFaseTipo,$intProc,$strLoteItemLici);
        };

        return $return;
    }

    protected function registrarItemClassificadoQualificado($IntFaseItem,$intFaseTipo,$intProc,$strLoteItemLici){
            
        try{
            
            $this->commonSlim->setProcesso($intProc);
            $this->commonSlim->setCNPJPREF($this->commonSlim->getCNPJPREF());
            $this->commonSlim->carregarDadosProcesso($intProc);     

            switch ($intFaseTipo) {
                
                case '12':

                    $arrayQualificados = $this->commonSlim->obterArrayQualificados($strLoteItemLici, $intFaseTipo);
                    $arrayIntCta       = $arrayQualificados;
                    $intFaseTipo       = '';

                break;

                default:

                    $intFaseTipo = '';

                break;
            }

        }finally{
           
        }

        foreach($arrayIntCta as $intCta){

            if(($this->commonSlim->existeLanceNaFase('12', $intProc, $strLoteItemLici, $intCta)) || $intFaseTipo == 3 || $this->commonSlim->getTipoPregao() == 6){
                $this->commonSlim->registrarItemClassificado($IntFaseItem, $intFaseTipo, $intProc, $strLoteItemLici, $intCta);
            }

        }
     
    }

    protected function registrarFimItemFase($intFaseItem,$intProc,$strLoteItemLici,$intFaseTipo, $lgDisa = 'S', $lgCloseSocket = 'S', $lgConfirm = 'S',$LG_CALLBACK = true){
            
        $processoItemIniciado = ProcessoItemIniciado::find($intFaseItem)->first();

        if($processoItemIniciado->DH_FIM == ''){

            $saida = array();

            //$DisputasAction = new DisputasAction($this);
            
            try{
                
                $this->commonSlim->setProcesso($intProc);
                //$this->common->setCONTA($this->CONTA);
                //$DisputasAction->setUSUARIO($this->USUARIO);
                //$DisputasAction->setCNPJPREF($this->common->getCNPJPREF());
                $this->commonSlim->carregarDadosProcesso($intProc);

                if($intFaseTipo == 3){
                    $intTipo = '4';
                }else{
                    $intTipo = $intFaseTipo;
                }

                $dadosGanhador = $this->commonSlim->obterDadosContaGanhadora($strLoteItemLici, $intTipo);
        
            }finally{

                unset($DisputasAction);
            }                                
            
            
            $processoItemIniciado->DH_FIM = date('Y-m-d H:i:s');

            if(isset($dadosGanhador->INT_CTA)){
                $processoItemIniciado->INT_CTA = $dadosGanhador->INT_CTA ; // $info['INT_CTA']       = $dadosGanhador->INT_CTA;                
            }
            
            $processoItemIniciado->LG_CONFIR = $lgConfirm;

            if( $processoItemIniciado->update() ){

                $this->commonSlim->deletarTimerTrigger($intFaseItem);                    
                
                $callback = '';
                if($intFaseItem == 8){

                    $info['DH_FIM']        = $processoItemIniciado->DH_FIM;
                    $info['INT_FASE_ITEM'] = $intFaseItem;
                    $info['CNPJ_PREF']     = $this->commonSlim->getCNPJPREF(); // necessario pq pelo shell_exec nao carrega automatico
                    $info['INT_FASE_ITEM'] = $intFaseItem;
                    
                    $this->commonSlim->forcarAceiteAcoesPregoeiro($intProc,$info);
                    
                    if( $LG_CALLBACK )
                     $callback = 'fimFaseRecurso';
                }

                $this->informarFim($intFaseItem,$intProc,$strLoteItemLici, $lgDisa, $lgCloseSocket,$callback);

                if( $this->commonSlim->getEmpate() == 'S' || $intFaseTipo == 3 ){

                    $teveLance = $this->verificaSeTeveLance($processoItemIniciado->DH_INI,$strLoteItemLici);

                    if( $teveLance  ){
                        //lance n da empate logo esta resolvido
                        $this->commonSlim->setItensEmpateResolvido($strLoteItemLici);

                        $verificaEmpateAberto = $this->verificaEmpateEmAberto($intProc, $strLoteItemLici);

                        if( isset($verificaEmpateAberto) ){

                            $now = date("Y-m-d H:i:s");

                            foreach( $verificaEmpateAberto as $empates ){

                                $processoFaseEmpate = ProcessoFaseEmpate::find($empates['INT_PROC_FASE_EMP']);
                                $processoFaseEmpate->DH_FIM = $now;
                                $processoFaseEmpate->LG_REIN = "S";

                                $processoFaseEmpate->update();
                            }

                        }

                    }else{
                        
                        //verifico se o empate ainda existe
                        if( ($dadosGanhador = $this->commonSlim->obterDadosContaGanhadora($strLoteItemLici, $intFaseTipo)) && $intFaseTipo != 15 ){
                            
                           $R = $this->commonSlim->sqlEmpateLance($strLoteItemLici, $dadosGanhador->VR_LC,$dadosGanhador->INT_CTA, $dadosGanhador->INT_PORT, $intFaseTipo,true);
        
                            try{

                                if($R->rowCount() == 0 && $intFaseTipo != 3 ){
                                    //se nao tem empate, algo aconteceu no meio do caminho para resolver (exclusao de proposta ou lance por exemplo)
                                    $this->commonSlim->setItensEmpateResolvido($strLoteItemLici);
                                }

                            }finally{
                                unset($R);
                            }   
                        }
                    }

                }

                if($dadosProximaFase = $this->verificarProximaFase($INT_FASE_ITEM, $INT_FASE_TP, $INT_PROC, $STR_LOTE_ITEM_LICI) ){
                    $this->registrarInicioProximaFase($dadosProximaFase);
                
                }else if ($INT_FASE_TP == 4 && $this->iniciarAutomaticamente($INT_PROC) ) {
                   
                  
                    $tokenSemaphore = sem_get($INT_PROC);
                   
                    $liberado = true;

                    while($liberado) {

                       if(  sem_acquire($tokenSemaphore) ){
                            
                           $liberado = false; 
                            
                            if( $L = $this->verificarProximoItem($INT_PROC) ){
                                $this->iniciarProximoItemAutomatico($L);
                            }

                            sem_release($tokenSemaphore);

                        }else{
                            usleep(300);
                        }

                    }

                  
                }         

            }
        }               
        
    }

    protected function informarFim($INT_FASE_ITEM,$INT_PROC,$STR_LOTE_ITEM_LICI, $LG_DISA, $LG_CLOSE_SOCKET, $callback = ''){

        $info['data'] = array('time' => 'Finalizado', 'INT_FASE_ITEM' => $INT_FASE_ITEM, 'STR_LOTE_ITEM_LICI' =>  $STR_LOTE_ITEM_LICI, 'LG_CLOSE_SOCKET' => $LG_CLOSE_SOCKET, 'LG_DISA' => $LG_DISA, 'callback' => $callback);
        $info['tipo'] = 'fim';          

        $acaoDTO = new AcaoDTO([
            'id' => $INT_PROC,
            'acao' => '',
            'tipo' => 'fim',
            'data' => json_encode($info)
        ]);

        AcaoExecutadaEvent::dispatch($acaoDTO);
        
        //$this->common->informarAcaoPeloServidor('acao',$INT_PROC, $info );                        
    }

    protected function verificaSeTeveLance($DH_INI,$STR_LOTE_ITEM_LICI){
        

        $lance = Lances::where(function ($query) use ($DH_INI, $STR_LOTE_ITEM_LICI) {
        $query->where('DH_EMIS', '>=', $DH_INI)
            ->whereNull('DH_CANC')
            ->whereNull('DH_CANC_LICI')
            ->where('LG_CANC_LICI', 'N')
            ->where('LG_INXQ', 'N')
            ->where('STR_LOTE_ITEM_LICI', $STR_LOTE_ITEM_LICI)
            ->where('INT_LC_TP', 1);
        })->get();
        
        

        return $lance;
    }

    function verificaEmpateEmAberto($INT_PROC, $STR_LOTE_ITEM_LICI,$INT_FASE_TP = ''){

        $verificaEmpateAberto = ProcessoFaseEmpate::where('INT_PROC', $INT_PROC)
                                ->where('STR_LOTE_ITEM_LICI', $STR_LOTE_ITEM_LICI)
                                ->whereNull('DH_FIM')
                                ->when(!empty($INT_FASE_TP), function ($query) use ($INT_FASE_TP) {
                                    $query->where('INT_FASE_TP', $INT_FASE_TP);
                                })
                                ->value('INT_PROC_FASE_EMP'); // ou ->first(), ->get(), etc.
       

        return $verificaEmpateAberto;
    }


}
