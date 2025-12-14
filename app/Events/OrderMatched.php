<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trade;
    public $userId;
    /**
     * Create a new event instance.
     */
    public function __construct(Trade $trade, $userId)
    {
        $this->trade = $trade;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user .' . $this->userId),
        ];
    }

    public function broadcastWith()
    {
        return [
            'trade' => $this->trade->load(['buyOrder', 'sellOrder']),
            'message' => 'Your order has been matched!',
        ];
    }
}
