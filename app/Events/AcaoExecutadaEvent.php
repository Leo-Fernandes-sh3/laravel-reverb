<?php

namespace App\Events;

use App\Dtos\AcaoDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcaoExecutadaEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public AcaoDTO $acaoDTO)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("acao-executada.{$this->acaoDTO->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'acao-executada';
    }

    public function broadcastWith(): array
    {
        return [
            'acao' => $this->acaoDTO,
        ];
    }
}
