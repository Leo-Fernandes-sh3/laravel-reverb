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
            MonitorJob::dispatch($args);
        } else {
            $acaoDTO = new AcaoDTO($args);
            AcaoExecutadaEvent::dispatch($acaoDTO);
        }

        return true;
    }
}
