<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Dtos\MensagemChatDTO;
use App\Events\MensagemChatEnviada;

final readonly class DispararMensagemChat
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args): MensagemChatDTO
    {
        $mensagemChatDTO = new MensagemChatDTO($args);
        MensagemChatEnviada::dispatch($mensagemChatDTO);

        return $mensagemChatDTO;
    }
}
