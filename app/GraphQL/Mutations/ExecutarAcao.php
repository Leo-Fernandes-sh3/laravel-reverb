<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;
use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;

final readonly class ExecutarAcao
{
    /** @param array{} $args */
    public function __invoke(null $_, array $args): void
    {
        $tipo = $args['tipo'];
        if ($tipo === 'monitor') {

            $data = \json_decode($args['data']);
            // TODO criar função executar monitor
        }else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }
    }
}
