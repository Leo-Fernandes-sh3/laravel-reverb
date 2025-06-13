<?php

namespace App\Console\Commands;

use App\Events\EnviarHorarioServidor;
use App\Events\Hello;
use Illuminate\Console\Command;

class DispararHorarioServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:disparar-horario-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        EnviarHorarioServidor::dispatch(Date('H:i:s'));
    }

    public function broadcastAs(): string{
        return 'horario';
    }
}
