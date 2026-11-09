<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
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
            'chatifyBoot' => \Chatify\Support\ChatifyBootData::fromRequest($request, null, 'login'),
            'assetJs' => rtrim($assetBase, '/').'/chatify.js'.$jsVersion,
            'assetCss' => rtrim($assetBase, '/').'/chatify.css'.$cssVersion,
            'isViteDev' => $isViteDev,
            'viteDevServer' => $devServerUrl,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): mixed
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect' => url('/v3rchat')]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
