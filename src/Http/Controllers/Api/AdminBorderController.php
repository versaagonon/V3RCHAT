<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Models\AvatarBorder;
use Chatify\Models\UserSetting;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AdminBorderController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ($user->role ?? 'node') !== 'admin') {
            abort(403, 'Unauthorized. Admin privilege required.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $borders = AvatarBorder::withCount('users')->orderByDesc('id')->get();

        return response()->json([
            'data' => $borders->map(fn (AvatarBorder $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'file_name' => $b->file_name,
                'image_url' => $b->image_url,
                'is_system' => (bool) $b->is_system,
                'users_count' => $b->users_count,
                'created_at' => $b->created_at?->toISOString(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'border' => ['required', 'file', 'max:5120'], // Max 5MB APNG/PNG
        ]);

        $name = trim((string) $request->input('name'));
        $file = $request->file('border');

        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'border';
        $uniqueSlug = $slug . '_' . time();
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $fileName = $uniqueSlug . '.' . $ext;

        $targetDir = public_path('vendor/chatify/borders');
        if (! file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);

        // Also copy to frontend public directory if present for Vite HMR
        $frontendPublicDir = base_path('../frontend/public/vendor/chatify/borders');
        if (file_exists($frontendPublicDir)) {
            @copy($targetDir . '/' . $fileName, $frontendPublicDir . '/' . $fileName);
        }

        $border = AvatarBorder::create([
            'name' => $name,
            'slug' => $uniqueSlug,
            'file_name' => $fileName,
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Animated border uploaded successfully.',
            'data' => [
                'id' => $border->id,
                'name' => $border->name,
                'slug' => $border->slug,
                'image_url' => $border->image_url,
                'is_system' => false,
                'users_count' => 0,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $border = AvatarBorder::find($id);
        if (! $border) {
            abort(404, 'Border not found.');
        }

        if ($border->is_system) {
            return response()->json([
                'message' => 'System pre-installed borders cannot be deleted.',
            ], 422);
        }

        // Unequip from any user settings
        UserSetting::where('active_border_id', $border->id)->update(['active_border_id' => null]);

        // Remove file if exists
        $filePath = public_path('vendor/chatify/borders/' . $border->file_name);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $border->users()->detach();
        $border->delete();

        return response()->json([
            'message' => "Border '{$border->name}' was deleted successfully.",
        ]);
    }

    public function gift(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'border_id' => ['required', 'integer', 'exists:avatar_borders,id'],
            'user_query' => ['required', 'string'],
        ]);

        $border = AvatarBorder::findOrFail($validated['border_id']);
        $query = trim((string) $validated['user_query']);

        $userModel = ChatifyModels::userClass();
        $targetUser = $userModel::where('username', $query)
            ->orWhere('email', $query)
            ->first();

        if (! $targetUser) {
            return response()->json([
                'message' => "No user found matching '{$query}'. Please check the username or email.",
                'errors' => ['user_query' => ["No user found matching '{$query}'."]],
            ], 422);
        }

        // Check if user already owns this border
        if ($targetUser->borders()->where('avatar_borders.id', $border->id)->exists()) {
            return response()->json([
                'message' => "User {$targetUser->name} already owns the '{$border->name}' border.",
                'errors' => ['user_query' => ["User already owns this border."]],
            ], 422);
        }

        $targetUser->borders()->attach($border->id, [
            'gifted_by_id' => $request->user()->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $displayName = $targetUser->username ? "@{$targetUser->username}" : $targetUser->email;

        return response()->json([
            'message' => "Border '{$border->name}' successfully gifted to {$targetUser->name} ({$displayName})!",
            'data' => [
                'border_id' => $border->id,
                'border_name' => $border->name,
                'user_id' => $targetUser->getKey(),
                'user_name' => $targetUser->name,
                'username' => $targetUser->username,
                'email' => $targetUser->email,
            ],
        ]);
    }

    public function revokeGift(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'border_id' => ['required', 'integer', 'exists:avatar_borders,id'],
            'user_id' => ['required', 'integer'],
        ]);

        $userModel = ChatifyModels::userClass();
        $targetUser = $userModel::findOrFail($validated['user_id']);
        $border = AvatarBorder::findOrFail($validated['border_id']);

        $targetUser->borders()->detach($border->id);

        // Unequip if active
        $settings = UserSetting::where('user_id', $targetUser->getKey())->first();
        if ($settings && (int) $settings->active_border_id === (int) $border->id) {
            $settings->update(['active_border_id' => null]);
        }

        return response()->json([
            'message' => "Border '{$border->name}' was revoked from {$targetUser->name}.",
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $q = trim((string) $request->input('q', ''));
        $userModel = ChatifyModels::userClass();

        $query = $userModel::query();
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->take(25)->get();

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id' => $u->getKey(),
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role ?? 'node',
                'borders_count' => $u->borders()->count(),
            ]),
        ]);
    }
}
