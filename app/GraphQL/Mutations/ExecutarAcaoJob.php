<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use App\Jobs\MonitorJob;

final readonly class ExecutarAcao
{
    /** @param array{} $args */
    public function __invoke(null $_, array $args): bool
    {
        $tipo = $args['tipo'];


        if ($tipo === 'monitor') {

            $data      = \json_decode($args['data'],true);
            $queueName = 'monitor'.$args['id'].'_'.$data['INT_FASE_ITEM'];

            $artisanPath = base_path('artisan');
            $comando     = "php $artisanPath queue:work --queue={$queueName} --sleep=1 --tries=0 --max-jobs=1 ";

            // Executa de forma assíncrona
            exec($comando . ' > /dev/null 2>&1 & echo $!');
            MonitorJob::dispatch($args)->onQueue($queueName);

        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
