<?php

namespace App\Jobs;

use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use App\Models\ProcessoItemIniciado;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitoramentoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $args;
    private string $monitorQueueName;

    /**
     * Cria uma nova instância do Job.
     *
     * @param array $args Argumentos de entrada, incluindo 'data' e outros.
     */
    public function __construct(array $args)
    {
        $this->args = $args;
        // Obter o nome da fila e o número de filas dos argumentos
        // Fallback para 'monitor' e 1 se não forem passados (para segurança)
        $this->monitorQueueName = $args['monitor_queue_name'] ?? 'monitor';
    }

    /**
     * Executa o Job de monitoramento.
     * Esta função verifica o status atual do item de processo, dispara eventos conforme necessário
     * e reagenda o Job caso o monitoramento deva continuar.
     */
    public function handle(): void
    {
        $data = json_decode($this->args['data']);
        $intFaseItem = (int) $data->INT_FASE_ITEM;

        $result = $this->getProcessoItemIniciadoCollection($intFaseItem);

        if ($result->isEmpty() || $result->first()->DH_FIM !== null) {
            return;
        }

        $lgRand = 'N';

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
                return;
            }
        } else {
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
                $formatador = 'PT' .
                    ($data[0] !== '00' ? $data[0] . 'H' : '') .
                    ($data[1] !== '00' ? $data[1] . 'M' : '') .
                    ($data[2] !== '00' ? $data[2] . 'S' : '');

                $date = new DateTime('now');
                try {
                    if ($formatador !== 'PT') {
                        $date->add(new DateInterval($formatador));
                    }
                    // TODO: atualizar ProcessoItemIniciado com novo DH_CALC_FIM e limpar DH_SUSP

                    // $processoItemIniciado->INT_FASE_ITEM = $intFaseItem;
                    // $processoItemIniciado->DH_CALC_FIM = $date->format('Y-m-d H:i:s');
                    // $processoItemIniciado->DH_SUSP = '';

                } catch (Exception $e) {
                    //TODO: Lançar exceção
                }
            }

            try {
                $nowDateTime = new DateTime('now');
                $dataCalculoFim = new DateTime($result->first()->DH_CALC_FIM);
                $intervalo = $dataCalculoFim->diff($nowDateTime);
            } catch (Exception $e) {
                $intervalo = new DateInterval('PT0S');
            }

            if ($intervalo->invert < 1) {
                $info['data'] = [
                    'time' => 'Analisando',
                    'INT_FASE_ITEM' => $intFaseItem,
                    'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                    'LG_DISA' => 'S'
                ];
                $info['tipo'] = 'login';

                //TODO: informarAcaoPeloServidor('acao', $result->first()->INT_PROC, $info);
//                sleep(2);

                $this->verificarCancelamentoEmAberto(
                    $result->first()->INT_PROC,
                    $intFaseItem,
                    $result->first()->STR_LOTE_ITEM_LICI
                );

                $newResult = $this->getProcessoItemIniciadoCollection($intFaseItem);

                if ($newResult->first()->DH_CALC_FIM == $result->first()->DH_CALC_FIM) {
                    if ($this->loopFase(
                        $newResult->first()->INT_FASE_TP,
                        $result->first()->INT_PROC,
                        $result->first()->STR_LOTE_ITEM_LICI,
                        $intFaseItem
                    )) {
                        $this->registrarItemRecusado(
                            $intFaseItem,
                            $newResult->first()->INT_FASE_TP,
                            $result->first()->INT_PROC,
                            $result->first()->STR_LOTE_ITEM_LICI
                        );
                    }

                    $lgDisa = $newResult->first()->H_DUR_PREG === '00:00:00' ? 'N' : 'S';
                    $lgCloseSocket = in_array($newResult->first()->INT_FASE_TP, ['7', '4', '6']) ? 'N' : 'S';

                    $this->registrarFimItemFase(
                        $intFaseItem,
                        $result->first()->INT_PROC,
                        $result->first()->STR_LOTE_ITEM_LICI,
                        $newResult->first()->INT_FASE_TP,
                        $lgDisa,
                        $lgCloseSocket
                    );
                    return;
                }
            } else {
                $time = base64_encode($intervalo->format('%H:%I:%S'));

                if (($result->first()->INT_FASE_TP != '11') && ($intervalo->format('%i') < 2)) {
                    $piscar = ($intervalo->format('%s') % 3 != 0) ? $result->first()->INT_FASE_TP : 'N';
                } else {
                    $piscar = 'N';
                    if ($result->first()->INT_FASE_TP == 11) {
                        $lgRand = 'S';
                    }
                }

                $info['data'] = [
                    'id'  => $result->first()->INT_PROC,
                    'time' => $time,
                    'INT_FASE_ITEM' => $intFaseItem,
                    'STR_LOTE_ITEM_LICI' => $result->first()->STR_LOTE_ITEM_LICI,
                    'MN_LC' => $mnLicitante,
                    'LG_PISC' => $piscar,
                    'LG_DISA' => 'N',
                    'LG_RAND' => $lgRand
                ];
                $info['tipo'] = 'timer';

                $acaoDTO = new AcaoDTO([
                    'id' => $this->args['id'],
                    'acao' => '',
                    'tipo' => 'timer',
                    'data' => json_encode($info)
                ]);

                AcaoExecutadaEvent::dispatch($acaoDTO);
            }
        }

        MonitoramentoJob::dispatch($this->args)->onQueue($this->monitorQueueName)->delay(now()->addSeconds(1));
    }

    /**
     * Obtém a coleção de ProcessoItemIniciado com os joins necessários.
     *
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
