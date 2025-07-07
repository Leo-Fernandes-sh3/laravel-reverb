<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;

/**
 * Classe ExecutarAcao
 *
 * Mutation responsável por executar uma ação no sistema de licitação eletrônica.
 * Pode disparar um evento diretamente no Laravel ou inicializar o monitoramento
 * de fases utilizando o serviço TimerTrigger.
 *
 * ### Funcionalidades principais:
 * - Quando o tipo de ação for `monitor`, inicializa o processo `TimerTrigger`
 *   via `shell_exec` para monitorar o andamento de fases em segundo plano.
 * - Quando o tipo de ação for diferente de `monitor`, dispara um evento
 *   `AcaoExecutadaEvent` com os dados fornecidos.
 *
 * ### Cenários de uso:
 * - **Monitoramento**: iniciar o acompanhamento em tempo real de uma fase específica
 *   de um item no processo licitatório.
 * - **Disparo direto**: enviar ações para os clientes conectados através do Reverb
 *   sem iniciar processos externos.
 *
 * @package App\GraphQL\Mutations
 * @author  Equipe LicitApp
 */
final readonly class ExecutarAcao
{
    /** @param array{} $args */
    public function __invoke(null $_, array $args): bool
    {
        $tipo = $args['tipo'];

        if ($tipo === 'monitor') {
            $data = json_decode($args['data'], true);

            $intFaseItem = $data['INT_FASE_ITEM'];
            $cnpjPref = $data['CNPJ_PREF'];
            $intCta = $data['INT_CTA'];
            $intUsu = $data['INT_USU'];
            $lgEmpate = $data['LG_EMPATE'];
            $lgReinicioItem = $data['LG_REIN_ITEM'];

            $artisanPath = base_path();

            $path = $artisanPath. '/app/Service';
            $command = "nohup php  {$path}/TimerTrigger.php {$intFaseItem} {$cnpjPref} {$intCta} {$intUsu} {$lgEmpate} {$lgReinicioItem} {$artisanPath} > /dev/null & echo \$!";
            shell_exec($command);
        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
