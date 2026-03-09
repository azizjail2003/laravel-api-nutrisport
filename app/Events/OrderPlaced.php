<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function broadcastOn(): Channel
    {
        return new Channel('backoffice');
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'   => $this->order->id,
            'client'     => $this->order->user->name ?? '—',
            'total'      => (float) $this->order->total,
            'site'       => $this->order->site->code ?? '—',
            'status'     => $this->order->status,
            'created_at' => $this->order->created_at,
        ];
    }
}
