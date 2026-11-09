<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Web;

use Chatify\Support\ChatifyBootData;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class MessengerController extends Controller
{
    public function index(Request $request, ?string $conversationId = null): View
    {
        if ($conversationId !== null && ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $conversationId)) {
            abort(404);
        }

        $assetUrl = config('chatify.frontend.asset_url');
        $assetBase = $assetUrl ?: asset('vendor/chatify');

        // Check if Vite Dev Server is running for instant HMR realtime updates
        $devServerUrl = class_exists(\App\Support\ViteDevHelper::class)
            ? \App\Support\ViteDevHelper::getDevServerUrl()
            : null;
        $isViteDev = ! empty($devServerUrl);

        // Auto cache-busting timestamp via filemtime for instant reflection
        $jsPath = public_path('vendor/chatify/chatify.js');
        $cssPath = public_path('vendor/chatify/chatify.css');
        $jsVersion = file_exists($jsPath) ? '?v=' . filemtime($jsPath) : '';
        $cssVersion = file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '';

        return view('Chatify::pages.app', [
            'chatifyBoot' => ChatifyBootData::fromRequest($request, $conversationId),
            'assetJs' => rtrim($assetBase, '/').'/chatify.js'.$jsVersion,
            'assetCss' => rtrim($assetBase, '/').'/chatify.css'.$cssVersion,
            'isViteDev' => $isViteDev,
            'viteDevServer' => $devServerUrl,
        ]);
    }
}
