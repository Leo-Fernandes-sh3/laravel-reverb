<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Rotas excluídas da verificação CSRF.
     */
    protected $except = [
        'graphql',
    ];
}