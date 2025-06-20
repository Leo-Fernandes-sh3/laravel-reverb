<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoFase extends Model
{
    protected $table = 'PROCESSO_FASE';
    protected $primaryKey = 'INT_PROC_FASE';

    /**
     * @return BelongsTo
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'INT_PROC', 'INT_PROC');
    }

    /**
     * @return BelongsTo
     */
    public function faseTipo(): BelongsTo
    {
        return $this->belongsTo(FaseTipo::class, 'INT_FASE_TP', 'INT_FASE_TP');
    }
}
