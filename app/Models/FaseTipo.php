<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaseTipo extends Model
{
    protected $table = 'FASE_TIPO';
    protected $primaryKey = 'INT_FASE_TP';

    /**
     * @return HasMany
     */
    public function processoFase(): HasMany
    {
        return $this->hasMany(ProcessoFase::class, 'INT_FASE_TP', 'INT_FASE_TP');
    }
}
