<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessoFaseEmpate extends Model
{
    protected $table = 'PROCESSO_FASE_EMPATE';
    protected $primaryKey = 'INT_PROC_FASE_EMP';

}
