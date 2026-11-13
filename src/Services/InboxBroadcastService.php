<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Events\ConversationInboxUpdated;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Support\ChatifyModels;

final class InboxBroadcastService
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    public function broadcastForConversation(
        Conversation $conversation,
        ?Message $lastMessage = null,
        ?int $excludeUserId = null,
    ): void {
        $conversation->loadMissing(['participants']);

        if ($lastMessage === null) {
            $lastMessage = ChatifyModels::messageClass()::query()
                ->forConversation($conversation->id)
                ->withSender()
                ->latest()
                ->first();
        } else {
            $lastMessage->loadMissing('sender');
        }

        foreach ($conversation->participants as $participant) {
            $userId = (int) $participant->user_id;

            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            // For empty direct conversations, never broadcast to the recipient
            if ($lastMessage === null && $conversation->isDirect() && (int) $conversation->created_by !== $userId) {
                continue;
            }

            // If this participant has a pending chat request for this direct conversation, do not broadcast to their inbox
            if ($conversation->isDirect()) {
                $hasPendingRequest = \Chatify\Models\ChatifyNotification::query()
                    ->where('user_id', $userId)
                    ->where('type', \Chatify\Models\ChatifyNotification::TYPE_REQUEST)
                    ->where('status', \Chatify\Models\ChatifyNotification::STATUS_PENDING)
                    ->whereJsonContains('data->conversation_id', $conversation->id)
                    ->exists();

                if ($hasPendingRequest) {
                    continue;
                }
            }

            $unread = $this->messageService->unreadCount($conversation, $userId);

            ConversationInboxUpdated::dispatch(
                $conversation,
                $userId,
                $lastMessage,
                $unread,
            );
        }
    }
}
