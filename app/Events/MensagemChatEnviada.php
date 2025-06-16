<?php

namespace App\Events;

use App\Dtos\MensagemChatDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemChatEnviada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public MensagemChatDTO $msg)
    {
        //
    }

    public function broadcastAs(): string
    {
        return 'mensagem-chat';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("chat.{$this->msg->idComentario}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'msg' => $this->msg,
        ];
    }
}
