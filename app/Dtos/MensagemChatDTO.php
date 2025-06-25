<?php

namespace App\Dtos;

class MensagemChatDTO
{
    public string $msg;
    public string $filtro;
    public string $id;
    public string $tipo;
    public string $idComentario;
    public string $idTipoConta;
    /**
     * Construtor que inicializa o DTO a partir de um array associativo de argumentos.
     *
     * Exemplo de uso:
     * $dto = new MensagemChatDTO($args);
     *
     * @param array $args Dados de entrada, deve conter as chaves: msg, filtro, id, tipo, idComentario
     */
    public function __construct(array $args)
    {
        $this->msg = $args['msg'] ?? '';
        $this->filtro = $args['filtro'] ?? '';
        $this->id = isset($args['id']) ? (int) $args['id'] : '0';
        $this->tipo = $args['tipo'] ?? '';
        $this->idComentario = isset($args['idComentario']) ? (int) $args['idComentario'] : '0';
        $this->idTipoConta  = isset($args['idTipoConta']) ? (int) $args['idTipoConta']   : '0';
    }
}
