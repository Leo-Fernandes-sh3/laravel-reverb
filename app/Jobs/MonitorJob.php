<?php

namespace App\Jobs;

use App\Dtos\AcaoDTO;
use App\Dtos\AcaoMonitorDTO;
use App\Events\AcaoExecutadaEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $i = 0;
        while ($i < 30) {
            $i ++;

            $data = json_decode($this->args['data'], true);

            $info['tipo'] = 'timer';  
            
            $info['data'] = array(
                'id'  => '7949',
                'time' => \base64_encode(date('H:i:s')),
                'INT_FASE_ITEM' => $data['INT_FASE_ITEM'],
                'STR_LOTE_ITEM_LICI' => 1,
                'MN_LC' => '0',
                'LG_PISC' => 'N',
                'LG_DISA' => 'N',
                'LG_RAND' => 'N'
            );

            $dataout = \json_encode($info);

            $acaoMonitorDTO = new AcaoDTO([
                        'id'   => '7949',
                        'acao' => '',
                        'tipo' => 'timer',
                        'data' => $dataout
            ]);

            AcaoExecutadaEvent::dispatch($acaoMonitorDTO);

            sleep(1);
        }
    }
}
