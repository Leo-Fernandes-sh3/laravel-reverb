<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoFase extends Model
{
    protected $table = 'PROCESSO_FASE';
    protected $primaryKey = 'INT_PROC_FASE';

//    public function processoItemIniciado(): BelongsTo
//    {
//        return $this->belongsTo(ProcessoItemIniciado::class, 'INT_PROC_FASE', 'INT_FASE_ITEM');
//    }

}
