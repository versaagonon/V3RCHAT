<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Events\NotificationReceived;
use Chatify\Http\Resources\NotificationResource;
use Chatify\Models\ChatifyNotification;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        $notifications = ChatifyNotification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadInfoCount = $notifications
            ->where('type', ChatifyNotification::TYPE_INFORMATION)
            ->where('status', ChatifyNotification::STATUS_UNREAD)
            ->count();

        $pendingRequestsCount = $notifications
            ->where('type', ChatifyNotification::TYPE_REQUEST)
            ->where('status', ChatifyNotification::STATUS_PENDING)
            ->count();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'total_count' => $notifications->count(),
                'unread_info_count' => $unreadInfoCount,
                'pending_requests_count' => $pendingRequestsCount,
                'total_badge_count' => $unreadInfoCount + $pendingRequestsCount,
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = ChatifyNotification::forUser($request->user()->getKey())->findOrFail($id);
        $notification->update(['status' => ChatifyNotification::STATUS_READ]);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        ChatifyNotification::forUser($request->user()->getKey())
            ->where('type', ChatifyNotification::TYPE_INFORMATION)
            ->where('status', ChatifyNotification::STATUS_UNREAD)
            ->update(['status' => ChatifyNotification::STATUS_READ]);

        return response()->json([
            'message' => 'All information notifications marked as read',
        ]);
    }

    public function confirmRequest(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->getKey();
        $notification = ChatifyNotification::forUser($userId)
            ->where('type', ChatifyNotification::TYPE_REQUEST)
            ->findOrFail($id);

        $notification->update(['status' => ChatifyNotification::STATUS_ACCEPTED]);

        $conversationId = $notification->data['conversation_id'] ?? null;
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                $this->conversationService->unhideForUser($conversation, (int) $userId);
                app(\Chatify\Services\InboxBroadcastService::class)->broadcastForConversation($conversation);
            }
        }

        return response()->json([
            'message' => 'Chat request accepted',
            'conversation_id' => $conversationId,
            'data' => new NotificationResource($notification),
        ]);
    }

    public function deleteRequest(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->getKey();
        $notification = ChatifyNotification::forUser($userId)
            ->where('type', ChatifyNotification::TYPE_REQUEST)
            ->findOrFail($id);

        $conversationId = $notification->data['conversation_id'] ?? null;
        if ($conversationId) {
            $conversation = ChatifyModels::conversationClass()::find($conversationId);
            if ($conversation) {
                $this->conversationService->hideForUser($conversation, (int) $userId);
            }
        }

        $notification->delete();

        return response()->json([
            'message' => 'Chat request deleted',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = ChatifyNotification::forUser($request->user()->getKey())->findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'Notification removed',
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        // Pending chat requests own a hidden conversation. Mirror deleteRequest so
        // clearing the notification list does not leave those request chats behind.
        $pendingRequests = ChatifyNotification::forUser($userId)
            ->where('type', ChatifyNotification::TYPE_REQUEST)
            ->where('status', ChatifyNotification::STATUS_PENDING)
            ->get();

        foreach ($pendingRequests as $notification) {
            $conversationId = $notification->data['conversation_id'] ?? null;
            if (! $conversationId) {
                continue;
            }

            $conversation = ChatifyModels::conversationClass()::find($conversationId);
            if ($conversation) {
                $this->conversationService->hideForUser($conversation, $userId);
            }
        }

        ChatifyNotification::forUser($userId)->delete();

        return response()->json([
            'message' => 'All notifications removed',
        ]);
    }

    public function broadcastAnnouncement(Request $request): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? 'node') !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Administrator clearance required.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:10240'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $mime = $file->getMimeType();
            $ext = match ($mime) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => abort(422, 'Unsupported image format.'),
            };

            $fileName = 'announcement_' . time() . '_' . Str::random(16) . '.' . $ext;

            $destDir = public_path('vendor/chatify/announcements');
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $file->move($destDir, $fileName);
            $imageUrl = url('vendor/chatify/announcements/' . $fileName);
        }

        $userModel = ChatifyModels::userClass();
        $now = now();
        $title = strip_tags(trim($validated['title']));
        $body = strip_tags(trim($validated['body']));
        $authorName = $user->name ?? 'Admin';
        $authorId = $user->getKey();

        $userModel::query()->chunkById(100, function ($users) use ($title, $body, $imageUrl, $authorName, $authorId, $now) {
            $records = [];
            foreach ($users as $target) {
                $records[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $target->getKey(),
                    'type' => ChatifyNotification::TYPE_INFORMATION,
                    'title' => $title,
                    'body' => $body,
                    'image_url' => $imageUrl,
                    'data' => json_encode([
                        'is_announcement' => true,
                        'author_id' => $authorId,
                        'author_name' => $authorName,
                    ]),
                    'status' => ChatifyNotification::STATUS_UNREAD,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($records !== []) {
                ChatifyNotification::insert($records);
            }
        });

        return response()->json([
            'message' => 'Announcement broadcasted successfully to all users.',
        ]);
    }
}
