<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcessoItemIniciado extends Model
{
    protected $table = 'PROCESSO_ITEM_INICIADO';
    protected $primaryKey = 'INT_FASE_ITEM';

    public function processoFase(): HasOne
    {
        return $this->hasOne(ProcessoFase::class, 'INT_FASE', 'INT_FASE');
    }
}
