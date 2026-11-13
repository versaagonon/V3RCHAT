<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Http\Resources\NotificationResource;
use Chatify\Models\ChatifyNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ChatifyNotification $notification,
        public int|string $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationReceived';
    }

    public function broadcastWith(): array
    {
        $unreadInfo = ChatifyNotification::forUser($this->userId)
            ->where('type', ChatifyNotification::TYPE_INFORMATION)
            ->where('status', ChatifyNotification::STATUS_UNREAD)
            ->count();

        $pendingRequests = ChatifyNotification::forUser($this->userId)
            ->where('type', ChatifyNotification::TYPE_REQUEST)
            ->where('status', ChatifyNotification::STATUS_PENDING)
            ->count();

        return [
            'notification' => (new NotificationResource($this->notification))->resolve(),
            'meta' => [
                'unread_info_count' => $unreadInfo,
                'pending_requests_count' => $pendingRequests,
                'total_badge_count' => $unreadInfo + $pendingRequests,
            ],
        ];
    }
}
