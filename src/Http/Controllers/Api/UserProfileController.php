<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Requests\UpdateUserProfileRequest;
use Chatify\Http\Resources\UserResource;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        return (new UserResource($user))->response($request);
    }

    public function update(UpdateUserProfileRequest $request, UserSettingsService $settingsService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        // 1. Handle avatar changes or reset
        if ($request->hasFile('avatar') || $request->boolean('reset_avatar')) {
            $settingsService->update(
                $user,
                ['reset_avatar' => $request->boolean('reset_avatar')],
                $request->file('avatar')
            );
        }

        // 2. Update name without password
        if ($request->filled('name')) {
            $user->name = trim((string) $request->input('name'));
        }

        // 3. Update username without password
        if ($request->has('username')) {
            $rawUsername = $request->input('username');
            $user->username = ($rawUsername !== null && trim((string) $rawUsername) !== '')
                ? trim((string) $rawUsername)
                : null;
        }

        // 4. Update email (password verification already succeeded in FormRequest)
        if ($request->filled('email') && strtolower(trim((string) $request->input('email'))) !== strtolower((string) $user->email)) {
            $user->email = trim((string) $request->input('email'));
            if (isset($user->email_verified_at)) {
                $user->email_verified_at = null;
            }
        }

        $user->save();

        return (new UserResource($user->fresh()))->response($request);
    }
}
