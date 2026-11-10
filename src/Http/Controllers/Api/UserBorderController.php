<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Models\AvatarBorder;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserBorderController extends Controller
{
    public function index(Request $request, UserSettingsService $settingsService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';

        if ($isAdmin) {
            $borders = AvatarBorder::orderBy('id')->get();
        } else {
            $borders = $user->borders()->orderBy('avatar_borders.id')->get();
        }

        $settings = $settingsService->forUser($user);

        return response()->json([
            'data' => [
                'is_admin' => $isAdmin,
                'active_border_id' => $settings->active_border_id,
                'available_borders' => $borders->map(fn (AvatarBorder $b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'image_url' => $b->image_url,
                    'is_system' => (bool) $b->is_system,
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
            'border_id' => ['nullable', 'integer'],
        ]);

        $borderId = $validated['border_id'] ?? null;
        $settings = $settingsService->forUser($user);

        if ($borderId === null) {
            $settings->update(['active_border_id' => null]);
            return (new UserResource($user->fresh()))->response($request);
        }

        $border = AvatarBorder::find($borderId);
        if (! $border) {
            return response()->json([
                'message' => 'The selected border does not exist.',
                'errors' => ['border_id' => ['The selected border does not exist.']],
            ], 422);
        }

        $isAdmin = ($user->role ?? 'node') === 'admin';
        $ownsBorder = $isAdmin || $user->borders()->where('avatar_borders.id', $borderId)->exists();

        if (! $ownsBorder) {
            return response()->json([
                'message' => 'You do not own this animated border. An admin must gift it to you.',
                'errors' => ['border_id' => ['You do not own this animated border.']],
            ], 403);
        }

        $settings->update(['active_border_id' => $border->id]);

        return (new UserResource($user->fresh()))->response($request);
    }
}
