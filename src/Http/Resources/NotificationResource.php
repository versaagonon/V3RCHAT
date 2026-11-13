<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data;
        if (is_array($data) && ! empty($data['sender_avatar']) && is_string($data['sender_avatar'])) {
            if (str_contains($data['sender_avatar'], '/storage/') && ! str_contains($data['sender_avatar'], '/storage/users-avatar/')) {
                $data['sender_avatar'] = str_replace('/storage/', '/storage/users-avatar/', $data['sender_avatar']);
            }
        }
        $imageUrl = $this->image_url;
        if (is_string($imageUrl) && str_contains($imageUrl, '/storage/') && ! str_contains($imageUrl, '/storage/users-avatar/')) {
            $imageUrl = str_replace('/storage/', '/storage/users-avatar/', $imageUrl);
        }

        $author = null;
        if (is_array($data) && $this->type === 'information') {
            $author = $this->resolveAuthor($request, $data);
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'image_url' => $imageUrl,
            'data' => $data,
            'author' => $author,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    /**
     * Resolve the administrator behind an information notification.
     *
     * New records embed author_id / gifted_by_id. Older broadcasts only carry a
     * display name, and legacy system information may carry nothing at all, so
     * the lookup falls back to the name and finally to the primary admin. The
     * avatar and animated border come from the live UserResource (current
     * equipment), not from a snapshot stored at send time.
     */
    private function resolveAuthor(Request $request, array $data): ?array
    {
        $cache = $request->attributes->get('chatify_notification_authors');
        if (! is_array($cache)) {
            $cache = [];
        }

        $authorId = $data['author_id'] ?? $data['gifted_by_id'] ?? null;
        $authorName = $data['author_name'] ?? $data['gifted_by'] ?? null;
        $cacheKey = $authorId !== null
            ? 'id:' . $authorId
            : (is_string($authorName) && $authorName !== '' ? 'name:' . $authorName : 'fallback');

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $userModel = \Chatify\Support\ChatifyModels::userClass();
        $author = null;

        if ($authorId !== null) {
            $author = $userModel::query()->find($authorId);
        }

        if (! $author && is_string($authorName) && $authorName !== '') {
            $author = $userModel::query()->where('name', $authorName)->orderBy('id')->first();
        }

        if (! $author) {
            $author = $userModel::query()->where('role', 'admin')->orderBy('id')->first();
        }

        $resolved = null;

        if ($author) {
            $attributes = (new UserResource($author))->resolve($request)['attributes'] ?? [];

            $resolved = [
                'id' => $author->getKey(),
                'name' => $attributes['name'] ?? ($author->name ?? 'Administrator'),
                'avatar' => $attributes['avatar'] ?? null,
                'avatar_border' => $attributes['avatar_border'] ?? null,
            ];
        }

        $cache[$cacheKey] = $resolved;
        $request->attributes->set('chatify_notification_authors', $cache);

        return $resolved;
    }
}
