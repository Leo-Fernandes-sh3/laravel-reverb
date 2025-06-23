<?php

namespace App\Jobs;

use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use App\Models\ProcessoItemIniciado;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class MonitorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $args)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = \json_decode($this->args['data']);
        $intFaseItem = (int) $data->INT_FASE_ITEM;
        $verificar = true;
        while ($verificar) {
            $result = $this->getProcessoItemIniciadoCollection($intFaseItem);

            if ($result->isEmpty()) {
                $verificar = false;
                continue;
            } else {
                if ( $result->first()->DH_FIM != null ) {
                    $verificar = false;
                    continue;
                }
            }

            $lgRand = 'N';
//            $suspensaoGeral = false;

            if ($result->first()->LG_SUSP === 'S' || $this->isPregoeiroAtivo($result->first())) {
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

                        //TODO: atualizar campos abaixo de Processo Item Iniciado com ID $intFaseItem

                        // $processoItemIniciado->INT_FASE_ITEM = $intFaseItem;
                        // $processoItemIniciado->DH_CALC_FIM = $date->format('Y-m-d H:i:s');
                        // $processoItemIniciado->DH_SUSP = '';

                    } catch (Exception $e) {
                        //TODO: Lançar exceção
                    }
                }

                $intervalo = new DateInterval('PT0S');
                try {
                    $nowDateTime = new DateTime('now');
                    $dataCalculoFim = new DateTime($result->first()->DH_CALC_FIM);
                    $intervalo = $dataCalculoFim->diff($nowDateTime);
                } catch (Exception $e) {
                    //TODO: Lançar exceção
                }

                if ($intervalo->invert < 1) {

                    $info['data'] = array(
                        'time' => 'Analisando',
                        'INT_FASE_ITEM' => $intFaseItem,
                        'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                        'LG_DISA' => 'S'
                    );
                    $info['tipo'] = 'login';

                    //TODO: informarAcaoPeloServidor('acao', $result->first()->INT_PROC, $info);
                    sleep(2);

                    $this->verificarCancelamentoEmAberto(
                        $result->first()->INT_PROC, $intFaseItem, $result->first()->STR_LOTE_ITEM_LICI
                    );

                    $newResult = $this->getProcessoItemIniciadoCollection($intFaseItem);

                    if ($newResult->first()->DH_CALC_FIM == $result->first()->DH_CALC_FIM) {
                        if ($this->loopFase($newResult->first()->INT_FASE_TP, $result->first()->INT_PROC, $result->first()->STR_LOTE_ITEM_LICI, $intFaseItem)) {
                            $this->registrarItemRecusado($intFaseItem, $newResult->first()->INT_FASE_TP, $result->first()->INT_PROC, $result->first()->STR_LOTE_ITEM_LICI);
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
                        'data' => json_encode($info['data'])
                    ]);

                    AcaoExecutadaEvent::dispatch($acaoDTO);
                    //TODO: informarAcaoPeloServidor('acao', $result->first->INT_PROC, $info);
                }
                sleep(1);
            }
        }
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
        // TODO: Implementar essa função
        return false;
    }

    protected function verificarCancelamentoEmAberto($intProc, $IntFaseItem, $strLoteItemLici): void
    {
        // TODO: Implementar essa função
    }

    protected function loopFase($intFaseTipo, $intProc, $strLoteItemLici, $intFaseItem = ''): bool
    {
        // TODO: Implementar essa função
        return true;
    }

    protected function registrarItemRecusado($intFaseItem, $intFaseTipo, $intProc, $strLoteItemLici): void
    {
        // TODO: Implementar essa função
    }

    protected function registrarFimItemFase($intFaseItem, $intProc, $strLoteItemLici, $intFaseTipo, $lgDisa, $lgCloseSocket): void
    {
        // TODO: Implementar essa função
    }
}
