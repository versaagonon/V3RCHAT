<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\UserSetting;
use Chatify\Services\AttachmentService;
use Chatify\Services\BlockService;
use Chatify\Services\PresenceService;
use Chatify\Services\UserSettingsService;
use Chatify\Support\UserAvatarUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        $settingsService = app(UserSettingsService::class);
        $settings = $settingsService->forUser($this->resource);
        $viewer = $request->user();
        $blockService = app(BlockService::class);
        $hideIdentity = $viewer !== null && ! $blockService->shouldRevealIdentity($viewer, $this->resource);

        $activeBorder = $settings->activeBorder;
        $borderData = $activeBorder ? [
            'id' => $activeBorder->id,
            'name' => $activeBorder->name,
            'slug' => $activeBorder->slug,
            'image_url' => $activeBorder->image_url,
        ] : null;

        $activeNameplate = $settings->activeNameplate;
        $nameplateData = $activeNameplate ? [
            'id' => $activeNameplate->id,
            'name' => $activeNameplate->name,
            'slug' => $activeNameplate->slug,
            'image_url' => $activeNameplate->image_url,
        ] : null;

        $activeProfileEffect = $settings->activeProfileEffect;
        $profileEffectData = $activeProfileEffect ? [
            'id' => $activeProfileEffect->id,
            'name' => $activeProfileEffect->name,
            'slug' => $activeProfileEffect->slug,
            'thumbnail_url' => $activeProfileEffect->thumbnail_url,
            'pair1_url' => $activeProfileEffect->pair1_url,
            'pair2_url' => $activeProfileEffect->pair2_url,
            'pair1_duration_ms' => (int) $activeProfileEffect->pair1_duration_ms,
            'pair2_duration_ms' => (int) $activeProfileEffect->pair2_duration_ms,
        ] : null;

        $isSelf = $viewer !== null && (int) $viewer->getKey() === (int) $this->resource->getKey();
        $viewerIsAdmin = $viewer !== null && (($viewer->role ?? 'node') === 'admin');

        $data = [
            'type' => 'user',
            'id' => $this->resource->getKey(),
            'attributes' => [
                'name' => $hideIdentity ? BlockService::hiddenUserName() : $this->resource->name,
                'avatar' => $hideIdentity
                    ? $blockService->defaultAvatarUrl()
                    : $this->avatarUrl($settings),
                'avatar_is_custom' => $hideIdentity ? false : $this->isCustomAvatar($settings),
                'avatar_border' => $hideIdentity ? null : $borderData,
                'nameplate' => $hideIdentity ? null : $nameplateData,
                'profile_effect' => $hideIdentity ? null : $profileEffectData,
                'role' => ($isSelf || $viewerIsAdmin) ? ($this->resource->role ?? 'node') : 'node',
                'profile_banner' => $hideIdentity ? null : $this->bannerUrl($settings),
                'created_at' => $this->resource->created_at?->toIso8601String(),
            ],
        ];

        if ($hideIdentity) {
            $data['attributes']['is_identity_hidden'] = true;
        }

        if ($isSelf || (! $hideIdentity && config('chatify.api.expose_email', false))) {
            $data['attributes']['email'] = $this->resource->email ?? null;
        }

        if ($isSelf || ! $hideIdentity) {
            $data['attributes']['username'] = $this->resource->username ?? null;
        }

        if ($viewer !== null) {
            $data['attributes']['is_blocked_by_me'] = $blockService->isBlocked($viewer, $this->resource);
            $data['attributes']['is_blocked_by_them'] = $blockService->isBlocked($this->resource, $viewer);

            if (! $hideIdentity) {
                $presenceService = app(PresenceService::class);
                $isOnline = $presenceService->visibleIsOnline($viewer, $this->resource);

                if ($isOnline !== null) {
                    $data['attributes']['is_online'] = $isOnline;
                }
            }
        }

        return $data;
    }

    private function bannerUrl(UserSetting $settings): ?string
    {
        if (! $settings->profile_banner) {
            return null;
        }

        $folder = config('chatify.user_avatar.folder', 'users-avatar');

        return app(AttachmentService::class)->storage()->url($folder.'/'.$settings->profile_banner);
    }

    private function avatarUrl(UserSetting $settings): string
    {
        return UserAvatarUrl::resolve($this->resource, $settings->avatar);
    }

    private function isCustomAvatar(UserSetting $settings): bool
    {
        $avatar = $settings->avatar;

        return $avatar !== null
            && $avatar !== ''
            && $avatar !== config('chatify.user_avatar.default', 'avatar.png');
    }
}
