<?php

declare(strict_types=1);

namespace Chatify\Support;

use Chatify\Http\Resources\UserResource;
use Chatify\Services\AttachmentService;
use Chatify\Services\BlockService;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\Request;

final class ChatifyBootData
{
    public static function fromRequest(Request $request, ?string $conversationId = null, ?string $authMode = null): array
    {
        $user = $request->user();
        $locale = ChatifyLocale::current();
        $fallback = ChatifyLocale::fallback();

        $broadcastingDefault = in_array(config('broadcasting.default', 'null'), ['pusher', 'reverb'], true)
            ? config('broadcasting.default')
            : 'null';
        $connConfig = config("broadcasting.connections.{$broadcastingDefault}", []);
        $broadcast = config('chatify.frontend.broadcast', []);

        $broadcastKey = $broadcast['key'] ?? ($connConfig['key'] ?? env('REVERB_APP_KEY', env('PUSHER_APP_KEY')));
        $broadcastCluster = $broadcast['cluster'] ?? ($connConfig['options']['cluster'] ?? env('PUSHER_APP_CLUSTER', 'mt1'));
        $broadcastHost = $broadcast['wsHost'] ?? ($connConfig['options']['host'] ?? env('REVERB_HOST', env('PUSHER_HOST', '127.0.0.1')));
        $broadcastPort = (int) ($broadcast['wsPort'] ?? ($connConfig['options']['port'] ?? env('REVERB_PORT', 8080)));
        $broadcastTls = (bool) ($broadcast['forceTLS'] ?? ($connConfig['options']['useTLS'] ?? (env('REVERB_SCHEME', 'http') === 'https')));

        $attachments = app(AttachmentService::class);
        $blockService = app(BlockService::class);

        if ($user !== null) {
            $settings = app(UserSettingsService::class)->forUser($user);
            $userData = (new UserResource($user))->resolve($request);
            $blockedUserIds = $blockService->blockedUserIdsFor($user);
            $preferences = [
                'dark_mode' => (bool) $settings->dark_mode,
                'theme_preferences' => $settings->theme_preferences,
                'chat_background_url' => $attachments->chatBackgroundUrl($settings->chat_background),
                'show_online_status' => (bool) $settings->active_status,
            ];
        } else {
            $userData = null;
            $blockedUserIds = [];
            $preferences = [
                'dark_mode' => true,
                'theme_preferences' => [
                    'theme' => 'monochrome',
                    'font' => 'system',
                    'wallpaper' => [
                        'kind' => 'pattern',
                        'patternId' => 'glamorous',
                        'imageUrl' => null,
                        'blurEnabled' => false,
                        'blurAmount' => 50,
                        'darknessAmount' => 0,
                    ],
                ],
                'chat_background_url' => null,
                'show_online_status' => false,
            ];
        }

        return [
            'user' => $userData,
            'authMode' => $authMode,
            'loginUrl' => $request ? $request->root().'/login' : url('/login'),
            'registerUrl' => $request ? $request->root().'/register' : url('/register'),
            'logoutUrl' => $request ? $request->root().'/logout' : url('/logout'),
            'messaging_blocked_user_ids' => $blockedUserIds,
            'default_avatar_url' => $blockService->defaultAvatarUrl(),
            'apiBase' => $request ? $request->root().'/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/') : url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/')),
            'broadcastAuthUrl' => $request ? $request->root().'/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/').'/broadcasting/auth' : url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/').'/broadcasting/auth'),
            'csrfToken' => csrf_token(),
            'conversationId' => $conversationId,
            'webBase' => $request ? $request->root().'/'.trim(config('chatify.web.prefix', 'v3rchat'), '/') : url('/'.trim(config('chatify.web.prefix', 'chatify'), '/')),
            'locale' => $locale,
            'fallbackLocale' => $fallback,
            'dir' => ChatifyLocale::direction($locale),
            'translations' => ChatifyLocale::translationPayload($locale),
            'fallbackTranslations' => $locale !== $fallback
                ? ChatifyLocale::translationPayload($fallback)
                : null,
            'appName' => __('chatify::chatify.ui.app_name', ['name' => config('chatify.name', 'V3RCHAT')]),
            'debug' => (bool) config('app.debug', false),
            'groupsEnabled' => (bool) config('chatify.groups.enabled', true),
            'features' => ChatifyAppearanceConfig::features(),
            'colors' => ChatifyAppearanceConfig::colorValues(),
            'themes' => ChatifyAppearanceConfig::themeIds(),
            'fonts' => ChatifyAppearanceConfig::fontIds(),
            'wallpaperPatterns' => ChatBackgroundPatterns::all(),
            'preferences' => $preferences,
            'attachments' => [
                'maxUploadSize' => (int) config('chatify.attachments.max_upload_size', 150),
                'allowedImages' => config('chatify.attachments.allowed_images', []),
                'allowedFiles' => config('chatify.attachments.allowed_files', []),
            ],
            'giphy' => [
                'enabled' => ChatifyAppearanceConfig::features()['giphy'],
                'apiKey' => ChatifyAppearanceConfig::giphyApiKey(),
            ],
            'broadcast' => [
                'driver' => $broadcastingDefault,
                'key' => $broadcastKey,
                'cluster' => $broadcastCluster,
                'wsHost' => $broadcastHost,
                'wsPort' => $broadcastPort,
                'forceTLS' => $broadcastTls,
            ],
            'sounds' => [
                'enabled' => (bool) config('chatify.sounds.enabled', true),
                'incomingMessage' => [
                    'enabled' => (bool) config('chatify.sounds.incoming_message.enabled', true),
                    'url' => config('chatify.sounds.incoming_message.url'),
                ],
                'outgoingMessage' => [
                    'enabled' => (bool) config('chatify.sounds.outgoing_message.enabled', true),
                    'url' => config('chatify.sounds.outgoing_message.url'),
                ],
                'typing' => [
                    'enabled' => (bool) config('chatify.sounds.typing.enabled', false),
                    'url' => config('chatify.sounds.typing.url'),
                ],
            ],
            'savedMessages' => [
                'enabled' => (bool) config('chatify.saved_messages.enabled', true),
                'title' => __('chatify::chatify.ui.saved_messages'),
            ],
        ];
    }
}
