<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnviarHorarioServidor implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public string $msg;
    /**
     * Create a new event instance.
     */
    public function __construct(string $msg)
    {
        $this->msg = $msg;
    }

    /**
     * Define o nome customizado do evento no canal de broadcast.
     *
     * Este nome será usado pelo frontend (Laravel Echo) ao escutar o evento.
     *
     * Exemplo de escuta no frontend:
     * Echo.channel('nome-do-canal')
     *     .listen('.horario', (e) => { ... });
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'horario';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("horario"),
        ];
    }
}
