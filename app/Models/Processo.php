<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    protected $table = 'PROCESSO';
    protected $primaryKey = 'INT_PROC';

    /**
     * @return HasMany
     */
    public function processoFase(): HasMany
    {
        return $this->hasMany(ProcessoFase::class, 'INT_PROC', 'INT_PROC');
    }

    /**
     * @return HasMany
     */
    public function processoItemIniciado(): HasMany
    {
        return $this->hasMany(ProcessoItemIniciado::class, 'INT_PROC', 'INT_PROC');
    }
}
