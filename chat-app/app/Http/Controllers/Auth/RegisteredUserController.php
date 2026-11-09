<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
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
            'chatifyBoot' => \Chatify\Support\ChatifyBootData::fromRequest($request, null, 'register'),
            'assetJs' => rtrim($assetBase, '/').'/chatify.js'.$jsVersion,
            'assetCss' => rtrim($assetBase, '/').'/chatify.css'.$cssVersion,
            'isViteDev' => $isViteDev,
            'viteDevServer' => $devServerUrl,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): mixed
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect' => url('/v3rchat')]);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
