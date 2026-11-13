<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\UserSetting;
use Chatify\Services\AttachmentService;
use Chatify\Support\UserAvatarUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attachments = app(AttachmentService::class);
        $avatarUrl = UserAvatarUrl::resolve($this->user, $this->avatar);

        return [
            'type' => 'user_settings',
            'id' => $this->user_id,
            'attributes' => [
                'avatar' => $this->avatar,
                'avatar_url' => $avatarUrl,
                'avatar_is_custom' => $this->avatar !== null
                    && $this->avatar !== ''
                    && $this->avatar !== config('chatify.user_avatar.default', 'avatar.png'),
                'dark_mode' => $this->dark_mode,
                'messenger_color' => $this->messenger_color,
                'theme_preferences' => $this->theme_preferences,
                'chat_background' => $this->chat_background,
                'chat_background_url' => $attachments->chatBackgroundUrl($this->chat_background),
                'active_status' => (bool) $this->active_status,
            ],
        ];
    }
}
