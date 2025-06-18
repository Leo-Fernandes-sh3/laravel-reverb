<?php

namespace App\Dtos;

class AcaoDTO
{
    public string $idComentario;
    public string $acao;
    public string $tipo;
    public string $data;

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->idComentario = $args['idComentario'] ?? '';
        $this->acao = $args['acao'] ?? '';
        $this->tipo = $args['tipo'] ?? '';
        $this->data = $args['data'] ?? '';
    }
}
