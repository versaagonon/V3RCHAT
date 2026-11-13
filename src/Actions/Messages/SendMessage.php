<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Contracts\RecipientResolver;
use Chatify\Data\SendMessageData;
use Chatify\Events\MessageSent;
use Chatify\Events\NotificationReceived;
use Chatify\Models\ChatifyNotification;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Services\MessageService;
use Chatify\Services\UserSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SendMessage
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly ConversationService $conversationService,
        private readonly AttachmentService $attachmentService,
        private readonly RecipientResolver $recipientResolver,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(
        Conversation $conversation,
        Model $sender,
        ?string $body,
        ?UploadedFile $attachment = null,
        array $attachments = [],
        ?string $replyToMessageId = null,
        ?string $forwardedFromMessageId = null,
        ?array $prebuiltAttachmentMeta = null,
    ): Message {
        $conversation->loadMissing(['participants.user']);
        $participant = $conversation->participants->firstWhere('user_id', (int) $sender->getKey());

        if ($participant === null) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.not_participant')],
            ]);
        }

        if ($conversation->isDirect()) {
            $recipient = $conversation->participants
                ->firstWhere('user_id', '!=', (int) $sender->getKey())
                ?->user;

            if ($recipient !== null && ! $this->recipientResolver->canMessage($sender, $recipient)) {
                throw ValidationException::withMessages([
                    'recipient' => [__('chatify::chatify.errors.not_allowed_to_message')],
                ]);
            }
        }

        $uploadedFiles = $attachments !== [] ? $attachments : ($attachment !== null ? [$attachment] : []);
        $attachmentMeta = $prebuiltAttachmentMeta;

        if ($attachmentMeta === null && $uploadedFiles !== []) {
            $attachmentMeta = $this->attachmentService->storeMessageAttachments($uploadedFiles);
        }

        if (($body === null || trim($body) === '') && $attachmentMeta === null) {
            throw ValidationException::withMessages([
                'body' => [__('chatify::chatify.errors.body_or_attachment_required')],
            ]);
        }

        if ($replyToMessageId !== null) {
            $replyExists = Message::query()
                ->forConversation($conversation->id)
                ->where('id', $replyToMessageId)
                ->exists();

            if (! $replyExists) {
                throw ValidationException::withMessages([
                    'reply_to_message_id' => [__('chatify::chatify.errors.reply_target_not_found')],
                ]);
            }
        }

        $message = $this->messageService->send(
            $conversation,
            $sender,
            new SendMessageData(
                body: $body !== null ? trim($body) : null,
                attachmentMeta: $attachmentMeta,
                replyToMessageId: $replyToMessageId,
                forwardedFromMessageId: $forwardedFromMessageId,
            )
        );

        if ($conversation->isDirect()) {
            $recipient = $conversation->participants
                ->firstWhere('user_id', '!=', (int) $sender->getKey())
                ?->user;

            if ($recipient !== null) {
                // Replying to a pending chat request counts as accepting it: close the
                // sender's request notification so it stops rendering on their side.
                $acceptedRequest = ChatifyNotification::query()
                    ->where('user_id', (int) $sender->getKey())
                    ->where('type', ChatifyNotification::TYPE_REQUEST)
                    ->where('status', ChatifyNotification::STATUS_PENDING)
                    ->whereJsonContains('data->conversation_id', $conversation->id)
                    ->first();

                if ($acceptedRequest !== null) {
                    $acceptedRequest->update(['status' => ChatifyNotification::STATUS_ACCEPTED]);
                    $this->conversationService->unhideForUser($conversation, (int) $sender->getKey());

                    // Re-broadcast so the accepting user's open tab drops the request card live.
                    NotificationReceived::dispatch($acceptedRequest, (int) $sender->getKey());
                }

                $hasRecipientReplied = Message::query()
                    ->forConversation($conversation->id)
                    ->where('user_id', (int) $recipient->getKey())
                    ->exists();

                $existingRequest = ChatifyNotification::query()
                    ->where('user_id', (int) $recipient->getKey())
                    ->where('type', ChatifyNotification::TYPE_REQUEST)
                    ->whereJsonContains('data->conversation_id', $conversation->id)
                    ->first();

                if (! $hasRecipientReplied && ! $existingRequest) {
                    $settingsService = app(UserSettingsService::class);
                    $senderSettings = $settingsService->forUser($sender);
                    $activeBorder = $senderSettings->activeBorder;

                    $avatarFolder = config('chatify.user_avatar.folder', 'users-avatar');
                    $avatarUrl = $senderSettings->avatar ? app(\Chatify\Services\AttachmentService::class)->storage()->url($avatarFolder . '/' . $senderSettings->avatar) : null;

                    $notif = ChatifyNotification::create([
                        'id' => (string) Str::uuid(),
                        'user_id' => (int) $recipient->getKey(),
                        'type' => ChatifyNotification::TYPE_REQUEST,
                        'title' => "Chat request from {$sender->name}",
                        'body' => $body ?? ($attachmentMeta ? 'Sent an attachment' : 'Wants to start a conversation'),
                        'image_url' => $avatarUrl,
                        'data' => [
                            'conversation_id' => $conversation->id,
                            'sender_id' => $sender->getKey(),
                            'sender_name' => $sender->name,
                            'sender_username' => $sender->username,
                            'sender_avatar' => $avatarUrl,
                            'sender_border' => $activeBorder ? [
                                'id' => $activeBorder->id,
                                'name' => $activeBorder->name,
                                'image_url' => $activeBorder->image_url,
                            ] : null,
                            'message_id' => $message->id,
                        ],
                        'status' => ChatifyNotification::STATUS_PENDING,
                    ]);

                    NotificationReceived::dispatch($notif, (int) $recipient->getKey());
                }
            }
        }

        $this->conversationService->unhideForAllParticipants($conversation);

        MessageSent::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation(
            $conversation->loadMissing('participants'),
            $message,
            (int) $sender->getKey()
        );

        return $message;
    }

    private function assertSenderIsParticipant(Conversation $conversation, Model $sender): void
    {
        $participant = $this->conversationService->getParticipant($conversation, (int) $sender->getKey());

        if ($participant === null) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.not_participant')],
            ]);
        }
    }
}
