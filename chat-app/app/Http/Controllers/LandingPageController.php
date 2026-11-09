<?php

namespace App\Http\Controllers;

use Chatify\Support\ChatifyBootData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Display the V3RCHAT sovereign syndicate landing page.
     */
    public function __invoke(Request $request): View
    {
        $assetUrl = config('chatify.frontend.asset_url');
        $assetBase = $assetUrl ?: asset('vendor/chatify');

        $devServerUrl = config('chatify.frontend.dev_server');
        if ($devServerUrl === null && (bool) config('app.debug', false)) {
            $configured = env('CHATIFY_DEV_SERVER', 'auto');
            if ($configured === true || $configured === 'auto' || $configured === 'true') {
                $socket = @fsockopen('localhost', 5173, $errno, $errstr, 0.05) ?: @fsockopen('127.0.0.1', 5173, $errno, $errstr, 0.05);
                if ($socket) {
                    fclose($socket);
                    $devServerUrl = 'http://localhost:5173';
                }
            } elseif (is_string($configured) && $configured !== '') {
                $devServerUrl = $configured;
            }
        }

        $isViteDev = ! empty($devServerUrl);

        $jsPath = public_path('vendor/chatify/chatify.js');
        $cssPath = public_path('vendor/chatify/chatify.css');
        $jsVersion = file_exists($jsPath) ? '?v=' . filemtime($jsPath) : '';
        $cssVersion = file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '';

        return view('Chatify::pages.app', [
            'chatifyBoot' => ChatifyBootData::fromRequest($request, null, 'landing'),
            'assetJs' => rtrim($assetBase, '/').'/chatify.js'.$jsVersion,
            'assetCss' => rtrim($assetBase, '/').'/chatify.css'.$cssVersion,
            'isViteDev' => $isViteDev,
            'viteDevServer' => $devServerUrl,
        ]);
    }
}

