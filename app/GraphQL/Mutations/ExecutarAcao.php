<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use App\Jobs\MonitoramentoJob;

final readonly class ExecutarAcao
{
    /** @param array{} $args */
    public function __invoke(null $_, array $args): bool
    {
        $tipo = $args['tipo'];

        if ($tipo === 'monitor') {
            $intProc = (int) ($args['id'] ?? 0);

            if ($intProc === 0) {
                return false;
            }

            // Defina o número total de filas de monitoramento que você configurará no Horizon
            $numberOfMonitorQueues = 5;

            // Calcula o índice da fila baseado no INT_PROC
            $queueIndex = $intProc % $numberOfMonitorQueues;

            // Define o nome da fila
//            $targetQueue = "monitor-" . $queueIndex;
            $targetQueue = "monitor";

            // Despacha o job para a fila específica
            // Se o job precisar do nome da fila para o re-dispatch, passe nos $args
            $args['monitor_queue_name'] = $targetQueue;
            $args['number_of_monitor_queues'] = $numberOfMonitorQueues; // Para que o job possa re-calcular

            MonitoramentoJob::dispatch($args)->onQueue($targetQueue);
        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
