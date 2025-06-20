<?php

namespace App\Dtos;

class AcaoDTO
{
    public string $id;
    public string $acao;
    public string $tipo;
    public string $data;

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->id = $args['id'] ?? '';
        $this->acao = $args['acao'] ?? '';
        $this->tipo = $args['tipo'] ?? '';
        $this->data = $args['data'] ?? '';
    }
}
