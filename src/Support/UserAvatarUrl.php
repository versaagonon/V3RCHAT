<?php

declare(strict_types=1);

namespace Chatify\Support;

use Chatify\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;

/**
 * Single source of truth for a user's avatar URL.
 *
 * Users who never uploaded (and users who removed their photo) keep the default
 * file name in ch_user_settings. Resolving that name to the stored file returns
 * a 404 because the default avatar is not shipped in storage; the intended
 * behaviour — and what the boot payload shows on registration — is a Gravatar
 * identicon derived from the account email. Both UserResource and
 * UserSettingsResource use this resolver so an avatar removal immediately shows
 * the same generated image without requiring a page reload.
 */
final class UserAvatarUrl
{
    public static function resolve(?Model $user, ?string $avatar): string
    {
        $default = (string) config('chatify.user_avatar.default', 'avatar.png');
        $fileName = $avatar ?: $default;

        if (
            $fileName === $default
            && config('chatify.gravatar.enabled', true)
            && $user !== null
            && ! empty($user->email)
        ) {
            $size = (int) config('chatify.gravatar.image_size', 200);
            $set = (string) config('chatify.gravatar.imageset', 'identicon');

            return 'https://www.gravatar.com/avatar/'
                . md5(strtolower(trim((string) $user->email)))
                . '?s=' . $size . '&d=' . $set;
        }

        $folder = (string) config('chatify.user_avatar.folder', 'users-avatar');

        return app(AttachmentService::class)->storage()->url($folder . '/' . $fileName);
    }
}
