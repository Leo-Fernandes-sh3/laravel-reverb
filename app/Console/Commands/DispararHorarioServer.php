<?php

namespace App\Console\Commands;

use App\Events\EnviarHorarioServidor;
use Illuminate\Console\Command;

/**
 * Class DispararHorarioServer
 *
 * Comando Artisan responsável por disparar o evento EnviarHorarioServidor,
 * enviando o horário atual do servidor para o frontend via Laravel Reverb.
 *
 * Este comando pode ser agendado no Laravel Schedule para execução periódica,
 * permitindo que o frontend receba o horário do servidor em tempo real.
 *
 * Exemplo de execução manual:
 * php artisan app:disparar-horario-server
 */
class DispararHorarioServer extends Command
{
    private const FORMAT_DATE = 'H:i:s';
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
    protected $description = 'Dispara o horário atual do servidor via evento EnviarHorarioServidor (Laravel Reverb)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $horario = date(self::FORMAT_DATE);

        EnviarHorarioServidor::dispatch($horario);
    }
}
