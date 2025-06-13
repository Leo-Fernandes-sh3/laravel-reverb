<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\Hello as EventsHello;

final readonly class Hello
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver

        $args['nome'];

        $msg = "Ola ". $args['nome'];

        EventsHello::dispatch($msg);

        return $msg;
    }
}
