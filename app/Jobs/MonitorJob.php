<?php

namespace App\Jobs;

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
        while ($i < 10) {
            $i ++;

            $data = json_decode($this->args['data'], true);
            $info['data'] = array(
                'id'  => $this->args['id'],
                'time' => date('H:i:s'),
                'INT_FASE_ITEM' => $data['INT_FASE_ITEM'],
                'STR_LOTE_ITEM_LICI' => 1,
                'MN_LC' => '0',
                'LG_PISC' => 'N',
                'LG_DISA' => 'N',
                'LG_RAND' => 'n'
            );
            $info['tipo'] = 'timer';

            AcaoExecutadaEvent::dispatch($info);

            sleep(1);
        }
    }
}
