<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Models\Nameplate;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserNameplateController extends Controller
{
    public function index(Request $request, UserSettingsService $settingsService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';

        // Admins own the whole catalog. Keep the pivot in sync so the ownership
        // check in update() (and any future code) stays consistent with what the
        // picker shows.
        if ($isAdmin) {
            $allIds = Nameplate::orderBy('id')->pluck('id');
            $ownedIds = $user->nameplates()->pluck('nameplates.id');
            $missing = $allIds->diff($ownedIds);

            if ($missing->isNotEmpty()) {
                $user->nameplates()->syncWithoutDetaching(
                    $missing->mapWithKeys(fn ($id) => [$id => [
                        'gifted_by_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]])->all()
                );
            }

            $nameplates = Nameplate::orderBy('id')->get();
        } else {
            $nameplates = $user->nameplates()->orderBy('nameplates.id')->get();
        }

        $settings = $settingsService->forUser($user);

        return response()->json([
            'data' => [
                'is_admin' => $isAdmin,
                'active_nameplate_id' => $settings->active_nameplate_id,
                'available_nameplates' => $nameplates->map(fn (Nameplate $n) => [
                    'id' => $n->id,
                    'name' => $n->name,
                    'slug' => $n->slug,
                    'image_url' => $n->image_url,
                    'is_system' => (bool) $n->is_system,
                ]),
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
            'nameplate_id' => ['nullable', 'integer'],
        ]);

        $nameplateId = $validated['nameplate_id'] ?? null;
        $settings = $settingsService->forUser($user);

        if ($nameplateId === null) {
            $settings->update(['active_nameplate_id' => null]);
            return (new UserResource($user->fresh()))->response($request);
        }

        $nameplate = Nameplate::find($nameplateId);
        if (! $nameplate) {
            return response()->json([
                'message' => 'The selected nameplate does not exist.',
                'errors' => ['nameplate_id' => ['The selected nameplate does not exist.']],
            ], 422);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';
        $ownsNameplate = $isAdmin || $user->nameplates()->where('nameplates.id', $nameplateId)->exists();

        if (! $ownsNameplate) {
            return response()->json([
                'message' => 'You do not own this nameplate. An admin must gift it to you.',
                'errors' => ['nameplate_id' => ['You do not own this nameplate.']],
            ], 403);
        }

        $settings->update(['active_nameplate_id' => $nameplate->id]);

        return (new UserResource($user->fresh()))->response($request);
    }
}
