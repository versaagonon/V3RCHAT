<?php

declare(strict_types=1);

return [
    'name' => env('CHATIFY_NAME', 'V3RCHAT'),

    'storage_disk_name' => env('CHATIFY_STORAGE_DISK', 'public'),

    'models' => [
        'user' => env('CHATIFY_USER_MODEL', App\Models\User::class),
        'conversation' => Chatify\Models\Conversation::class,
        'message' => Chatify\Models\Message::class,
        'participant' => Chatify\Models\ConversationParticipant::class,
        'favorite' => Chatify\Models\Favorite::class,
        'block' => Chatify\Models\UserBlock::class,
        'user_setting' => Chatify\Models\UserSetting::class,
    ],

    'tables' => [
        'conversations' => 'ch_conversations',
        'participants' => 'ch_conversation_participants',
        'messages' => 'ch_messages',
        'favorites' => 'ch_favorites',
        'blocks' => 'ch_user_blocks',
        'user_settings' => 'ch_user_settings',
    ],

    'api' => [
        'prefix' => env('CHATIFY_API_PREFIX', 'api/chatify/v1'),
        'middleware' => array_map('trim', array_filter(explode(',', env(
            'CHATIFY_API_MIDDLEWARE',
            filter_var(env('CHATIFY_WEB_ENABLED', true), FILTER_VALIDATE_BOOL) ? 'web,auth' : 'api,auth:sanctum',
        )))),
        'expose_email' => env('CHATIFY_EXPOSE_EMAIL', false),
    ],

    'user_avatar' => [
        'folder' => 'users-avatar',
        'default' => 'avatar.png',
    ],

    'gravatar' => [
        'enabled' => true,
        'image_size' => 200,
        'imageset' => 'identicon',
    ],

    'attachments' => [
        'folder' => 'attachments',
        'allowed_images' => ['png', 'jpg', 'jpeg', 'gif'],
        'allowed_files' => [
            'zip', 'rar', 'txt',
            'webm', 'ogg', 'mp4', 'm4a', 'mp3', 'pdf',
            'wav', 'aac', 'flac', 'opus',
            'mov', 'avi', 'mkv', 'm4v', 'ogv', '3gp', 'mpeg', 'mpg',
        ],
        'max_upload_size' => env('CHATIFY_MAX_FILE_SIZE', 150),
    ],

    'giphy' => [
        'enabled' => env('CHATIFY_GIPHY_ENABLED', true),
        'api_key' => env('CHATIFY_GIPHY_API_KEY'),
    ],

    'colors' => [
        'enabled' => env('CHATIFY_COLORS_ENABLED', true),
        'list' => [
            '#ffffff',
            '#000000',
            '#2180f3',
            '#2196F3',
            '#00BCD4',
            '#3F51B5',
            '#673AB7',
            '#4CAF50',
            '#FFC107',
            '#FF9800',
            '#ff2522',
            '#9C27B0',
        ],
    ],

    'themes' => [
        'enabled' => env('CHATIFY_THEMES_ENABLED', true),
        'list' => [
            'monochrome',
            'classic',
            'day',
            'tinted',
            'night',
        ],
    ],

    'fonts' => [
        'enabled' => env('CHATIFY_FONTS_ENABLED', true),
        'list' => [
            'system',
            'segoe',
            'arial',
            'helvetica',
            'georgia',
            'times',
            'courier',
            'verdana',
            'tahoma',
            'trebuchet',
            'palatino',
            'garamond',
            'consolas',
            'calibri',
            'cambria',
            'lucida',
            'impact',
            'comic',
        ],
    ],

    'chat_background' => [
        'enabled' => env('CHATIFY_WALLPAPER_ENABLED', true),
        'folder' => 'chat-backgrounds',
        'patterns_url' => env('CHATIFY_PATTERNS_URL', '/vendor/chatify/patterns'),
        'patterns' => [
            ['name' => 'Glamorous', 'filename' => 'glamorous.svg'],
            ['name' => 'Bubbles', 'filename' => 'bubbles.svg'],
            ['name' => 'Circuit', 'filename' => 'circuit-board.svg'],
            ['name' => 'Hideout', 'filename' => 'hideout.svg'],
        ],
    ],

    'web' => [
        'enabled' => env('CHATIFY_WEB_ENABLED', true),
        'prefix' => env('CHATIFY_ROUTES_PREFIX', 'v3rchat'),
        'middleware' => ['web', 'auth'],
        'layout' => env('CHATIFY_WEB_LAYOUT', 'Chatify::layouts.app'),
    ],

    'frontend' => [
        'asset_url' => env('CHATIFY_ASSET_URL'),
        'broadcast' => [
            'driver' => env('CHATIFY_BROADCAST_DRIVER', env('BROADCAST_CONNECTION', 'null')),
            'key' => env('PUSHER_APP_KEY'),
            'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            'wsHost' => env('PUSHER_HOST'),
            'wsPort' => (int) env('PUSHER_PORT', 443),
            'forceTLS' => filter_var(env('PUSHER_APP_USETLS', true), FILTER_VALIDATE_BOOL),
        ],
    ],

    'groups' => [
        'enabled' => env('CHATIFY_GROUPS_ENABLED', true),
        'min_participants' => 2,
        'max_participants' => env('CHATIFY_GROUP_MAX_PARTICIPANTS', 50),
        'max_name_length' => 100,
        'max_description_length' => 500,
        'avatar_folder' => 'groups-avatar',
        'preview_members' => 6,
    ],

    'sounds' => [
        'enabled' => env('CHATIFY_SOUNDS_ENABLED', true),
        'incoming_message' => [
            'enabled' => env('CHATIFY_SOUND_INCOMING_ENABLED', true),
            'url' => env('CHATIFY_SOUND_INCOMING', '/vendor/chatify/sounds/incoming.wav'),
        ],
        'outgoing_message' => [
            'enabled' => env('CHATIFY_SOUND_OUTGOING_ENABLED', true),
            'url' => env('CHATIFY_SOUND_OUTGOING', '/vendor/chatify/sounds/outgoing.wav'),
        ],
        'typing' => [
            'enabled' => env('CHATIFY_SOUND_TYPING_ENABLED', false),
            'url' => env('CHATIFY_SOUND_TYPING', '/vendor/chatify/sounds/typing.wav'),
        ],
    ],

    'saved_messages' => [
        'enabled' => env('CHATIFY_SAVED_MESSAGES_ENABLED', true),
        'title' => env('CHATIFY_SAVED_MESSAGES_TITLE', 'Saved Messages'),
    ],

    'frontend' => [
        'dev_server' => env('CHATIFY_DEV_SERVER', 'http://127.0.0.1:5173'),
        'asset_url' => env('CHATIFY_ASSET_URL', null),
        'broadcast' => [
            'key' => env('REVERB_APP_KEY', env('PUSHER_APP_KEY')),
            'wsHost' => env('REVERB_HOST', '127.0.0.1'),
            'wsPort' => (int) env('REVERB_PORT', 8080),
            'forceTLS' => env('REVERB_SCHEME', 'http') === 'https',
            'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
        ],
    ],

    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],

    'locale' => [
        'middleware' => env('CHATIFY_LOCALE_MIDDLEWARE', true),
        'detect_via' => ['header', 'query', 'user', 'app'],
        'header' => 'Accept-Language',
        'fallback_header' => 'X-Chatify-Locale',
        'query_parameter' => 'locale',
        'user_attribute' => 'locale',
    ],
];
