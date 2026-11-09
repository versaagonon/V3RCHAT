<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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

        $devServerUrl = \App\Support\ViteDevHelper::getDevServerUrl();
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
     * Verify the secret registration code.
     *
     * @throws ValidationException
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $throttleKey = 'verify_code:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'secret_code' => ["Too many authorization attempts. Syndicate lockdown active for {$minutes} minute(s)."],
            ])->status(429);
        }

        $request->validate([
            'secret_code' => ['required', 'string', 'max:100'],
        ]);

        $code = trim((string) $request->input('secret_code'));
        $isValid = $this->validateSecretCode($code);

        if (! $isValid) {
            RateLimiter::hit($throttleKey, 600); // 10 minutes penalty
            throw ValidationException::withMessages([
                'secret_code' => ['The provided syndicate access code is invalid.'],
            ])->status(422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Syndicate clearance code accepted.',
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): mixed
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $throttleKey = 'register_rate:' . $ip;
        $codeThrottleKey = 'verify_code:' . $ip;

        // Strict rate limiting: max 4 account creation requests per 15 minutes per IP
        if (RateLimiter::tooManyAttempts($throttleKey, 4)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'general' => ["Registration rate limit reached. IP throttled for {$minutes} minute(s)."],
            ])->status(429);
        }

        if (RateLimiter::tooManyAttempts($codeThrottleKey, 5)) {
            $seconds = RateLimiter::availableIn($codeThrottleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'secret_code' => ["Too many authorization attempts. Syndicate lockdown active for {$minutes} minute(s)."],
            ])->status(429);
        }

        $request->validate([
            'secret_code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:'.User::class.',username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $code = trim((string) $request->input('secret_code'));
        $codeRecord = $this->validateSecretCode($code, true);

        if (! $codeRecord) {
            RateLimiter::hit($codeThrottleKey, 600);
            RateLimiter::hit($throttleKey, 900);
            throw ValidationException::withMessages([
                'secret_code' => ['The provided syndicate access code is invalid or expired.'],
            ])->status(422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username ?: null,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Increment times used on the code record in database
        if (is_object($codeRecord) && isset($codeRecord->id)) {
            try {
                DB::table('registration_codes')
                    ->where('id', $codeRecord->id)
                    ->increment('times_used');
            } catch (\Throwable) {
                // Ignore if column doesn't support increment
            }
        }

        RateLimiter::hit($throttleKey, 900);
        RateLimiter::clear($codeThrottleKey);

        event(new Registered($user));

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect' => url('/v3rchat')]);
        }

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Check if secret code is valid in database (or fallback constant).
     */
    protected function validateSecretCode(string $code, bool $returnRecord = false): mixed
    {
        if ($code === '') {
            return false;
        }

        $record = null;

        if (Schema::hasTable('registration_codes')) {
            $record = DB::table('registration_codes')
                ->where('code', $code)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where(function ($q) {
                    $q->whereNull('max_uses')->orWhereColumn('times_used', '<', 'max_uses');
                })
                ->first();
        }

        // Hardcoded syndicate fallback key if DB table isn't present
        if (! $record && $code === 'V3ROURPARTNERSINCE2020') {
            return $returnRecord ? (object)['id' => null, 'code' => 'V3ROURPARTNERSINCE2020'] : true;
        }

        if (! $record) {
            return false;
        }

        return $returnRecord ? $record : true;
    }
}
