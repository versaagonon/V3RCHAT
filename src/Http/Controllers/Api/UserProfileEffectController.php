<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Models\ProfileEffect;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserProfileEffectController extends Controller
{
    public function index(Request $request, UserSettingsService $settingsService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';

        // Admins own the whole catalog. Keep the pivot in sync so the ownership
        // check in update() stays consistent with what the picker shows.
        if ($isAdmin) {
            $allIds = ProfileEffect::orderBy('id')->pluck('id');
            $ownedIds = $user->profileEffects()->pluck('profile_effects.id');
            $missing = $allIds->diff($ownedIds);

            if ($missing->isNotEmpty()) {
                $user->profileEffects()->syncWithoutDetaching(
                    $missing->mapWithKeys(fn ($id) => [$id => [
                        'gifted_by_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]])->all()
                );
            }

            $effects = ProfileEffect::orderBy('id')->get();
        } else {
            $effects = $user->profileEffects()->orderBy('profile_effects.id')->get();
        }

        $settings = $settingsService->forUser($user);

        return response()->json([
            'data' => [
                'is_admin' => $isAdmin,
                'active_profile_effect_id' => $settings->active_profile_effect_id,
                'available_profile_effects' => $effects->map(fn (ProfileEffect $effect) => $this->payload($effect)),
            ],
        ]);
    }

    public function update(Request $request, UserSettingsService $settingsService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validated = $request->validate([
            'profile_effect_id' => ['nullable', 'integer'],
        ]);

        $effectId = $validated['profile_effect_id'] ?? null;
        $settings = $settingsService->forUser($user);

        if ($effectId === null) {
            $settings->update(['active_profile_effect_id' => null]);
            return (new UserResource($user->fresh()))->response($request);
        }

        $effect = ProfileEffect::find($effectId);
        if (! $effect) {
            return response()->json([
                'message' => 'The selected profile effect does not exist.',
                'errors' => ['profile_effect_id' => ['The selected profile effect does not exist.']],
            ], 422);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';
        $ownsEffect = $isAdmin || $user->profileEffects()->where('profile_effects.id', $effectId)->exists();

        if (! $ownsEffect) {
            return response()->json([
                'message' => 'You do not own this profile effect. An admin must gift it to you.',
                'errors' => ['profile_effect_id' => ['You do not own this profile effect.']],
            ], 403);
        }

        $settings->update(['active_profile_effect_id' => $effect->id]);

        return (new UserResource($user->fresh()))->response($request);
    }

    private function payload(ProfileEffect $effect): array
    {
        return [
            'id' => $effect->id,
            'name' => $effect->name,
            'slug' => $effect->slug,
            'thumbnail_url' => $effect->thumbnail_url,
            'pair1_url' => $effect->pair1_url,
            'pair2_url' => $effect->pair2_url,
            'pair1_duration_ms' => (int) $effect->pair1_duration_ms,
            'pair2_duration_ms' => (int) $effect->pair2_duration_ms,
            'is_system' => (bool) $effect->is_system,
        ];
    }
}
