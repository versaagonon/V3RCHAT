<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;

final class UpdateGroupConversation
{
    public function handle(Conversation $conversation, ?string $name = null, ?string $description = null): Conversation
    {
        $updates = [];

        if ($name !== null) {
            $updates['name'] = mb_substr(strip_tags(trim($name)), 0, 100);
        }

        if ($description !== null) {
            $updates['description'] = mb_substr(strip_tags(trim($description)), 0, 500);
        }

        if ($updates !== []) {
            $conversation->forceFill($updates)->save();
        }

        return $conversation->fresh(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);
    }
}
