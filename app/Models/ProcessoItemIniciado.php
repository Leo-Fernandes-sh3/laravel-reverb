<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoItemIniciado extends Model
{
    protected $table = 'PROCESSO_ITEM_INICIADO';
    protected $primaryKey = 'INT_FASE_ITEM';

    protected $fillable = [
        'INT_FASE_ITEM',
        'DH_SUSP'
    ];

    /**
     * Funciona
     * @return BelongsTo
     */
    public function processo(): belongsTo
    {
        return $this->belongsTo(Processo::class, 'INT_PROC', 'INT_PROC');
    }
}
