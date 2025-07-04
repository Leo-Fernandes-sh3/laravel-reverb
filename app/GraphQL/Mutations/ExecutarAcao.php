<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use App\Jobs\MonitorJob;
use Illuminate\Support\Facades\Log;


final readonly class ExecutarAcao
{
    /** @param array{} $args */
    public function __invoke(null $_, array $args): bool
    {
        $tipo = $args['tipo'];

       // Log::debug('Valor da variável:', ['tipo' => $tipo]);

        if ($tipo == 'monitor') {

            $data      = \json_decode($args['data'],true);

            $INT_FASE_ITEM = $data['INT_FASE_ITEM'];
            $INT_CTA       = $data['INT_CTA'];
            $CNPJ_PREF     = $data['CNPJ_PREF'];
            $INT_CTA       = $data['INT_CTA'];
            $INT_USU       = $data['INT_USU'];
            $LG_EMPATE     = $data['LG_EMPATE'];
            $LG_REIN_ITEM  = $data['LG_REIN_ITEM'];

            $artisanPath = base_path();

             $texto = 'executando no hohup '. PHP_EOL; //quebra de linha

            \file_put_contents($artisanPath.'/public/Saida.txt',$texto,FILE_APPEND);

            $Path = $artisanPath. '/app/Service';
            
            //$C = "nohup php /var/www/html/TimerTrigger.php $INT_FASE_ITEM $CNPJ_PREF $INT_CTA $INT_USU $LG_EMPATE $LG_REIN_ITEM  > /dev/null & echo \$!";	
            $C = "nohup php  $Path/TimerTrigger.php $INT_FASE_ITEM $CNPJ_PREF $INT_CTA $INT_USU $LG_EMPATE $LG_REIN_ITEM $artisanPath > /dev/null & echo \$!";
            
          //  Log::debug('Valor da variável:', ['nohup' => $C]);
            $teste = shell_exec($C);		

            //MonitorJob::dispatch($args);

        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
