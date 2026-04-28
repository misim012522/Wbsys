<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $officeId,
        public string $action,
        public ?int $queueEntryId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'office_id' => $this->officeId,
            'queue_entry_id' => $this->queueEntryId,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
