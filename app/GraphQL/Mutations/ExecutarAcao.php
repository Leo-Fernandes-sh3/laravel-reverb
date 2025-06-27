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

            $INT_FASE_ITEM = $data['INT_FASE_ITEM'];
            $INT_CTA       = $data['INT_CTA'];
            $CNPJ_PREF     = $data['CNPJ_PREF'];
            $INT_CTA       = $data['INT_CTA'];
            $INT_USU       = $data['INT_USU'];
            $LG_EMPATE     = $data['LG_EMPATE'];
            $LG_REIN_ITEM  = $data['LG_REIN_ITEM'];

            $Path = '/home/desenv_web/desenv/laravel-reverb/app/Service';
            
            //$C = "nohup php /var/www/html/TimerTrigger.php $INT_FASE_ITEM $CNPJ_PREF $INT_CTA $INT_USU $LG_EMPATE $LG_REIN_ITEM  > /dev/null & echo \$!";	
            $C = "nohup php  $Path/TimerTrigger.php $INT_FASE_ITEM $CNPJ_PREF $INT_CTA $INT_USU $LG_EMPATE $LG_REIN_ITEM  > /dev/null & echo \$!";	
            $teste = shell_exec($C);		

            //MonitorJob::dispatch($args);

        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
