<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Events\NotificationReceived;
use Chatify\Models\ChatifyNotification;
use Chatify\Models\Nameplate;
use Chatify\Models\UserSetting;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AdminNameplateController extends Controller
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

        $nameplates = Nameplate::withCount('users')->orderByDesc('id')->get();

        return response()->json([
            'data' => $nameplates->map(fn (Nameplate $n) => [
                'id' => $n->id,
                'name' => $n->name,
                'slug' => $n->slug,
                'file_name' => $n->file_name,
                'image_url' => $n->image_url,
                'is_system' => (bool) $n->is_system,
                'users_count' => $n->users_count,
                'created_at' => $n->created_at?->toISOString(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'nameplate' => ['required', 'file', 'mimes:png,apng,webp,gif', 'max:5120'],
        ]);

        $name = trim((string) $request->input('name'));
        $file = $request->file('nameplate');

        $mime = $file->getMimeType();
        $ext = match ($mime) {
            'image/png', 'image/apng' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => abort(422, 'Unsupported image format. Allowed formats: PNG, APNG, WebP, GIF.'),
        };

        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'nameplate';
        $uniqueSlug = $slug . '_' . time() . '_' . Str::random(6);
        $fileName = $uniqueSlug . '.' . $ext;

        $targetDir = public_path('vendor/chatify/nameplates');
        if (! file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);

        // Mirror into the Vite dev public dir so HMR serves it immediately.
        $frontendPublicDir = base_path('../frontend/public/vendor/chatify/nameplates');
        if (file_exists($frontendPublicDir)) {
            @copy($targetDir . '/' . $fileName, $frontendPublicDir . '/' . $fileName);
        }

        $nameplate = Nameplate::create([
            'name' => $name,
            'slug' => $uniqueSlug,
            'file_name' => $fileName,
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Nameplate uploaded successfully.',
            'data' => [
                'id' => $nameplate->id,
                'name' => $nameplate->name,
                'slug' => $nameplate->slug,
                'image_url' => $nameplate->image_url,
                'is_system' => false,
                'users_count' => 0,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $nameplate = Nameplate::find($id);
        if (! $nameplate) {
            abort(404, 'Nameplate not found.');
        }

        if ($nameplate->is_system) {
            return response()->json([
                'message' => 'System pre-installed nameplates cannot be deleted.',
            ], 422);
        }

        UserSetting::where('active_nameplate_id', $nameplate->id)->update(['active_nameplate_id' => null]);

        $filePath = public_path('vendor/chatify/nameplates/' . $nameplate->file_name);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $nameplate->users()->detach();
        $nameplate->delete();

        return response()->json([
            'message' => "Nameplate '{$nameplate->name}' was deleted successfully.",
        ]);
    }

    public function gift(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'nameplate_id' => ['required', 'integer', 'exists:nameplates,id'],
            'user_query' => ['required', 'string'],
            'gift_title' => ['nullable', 'string', 'max:255'],
            'gift_description' => ['nullable', 'string'],
        ]);

        $nameplate = Nameplate::findOrFail($validated['nameplate_id']);
        $query = trim((string) $validated['user_query']);

        $userModel = ChatifyModels::userClass();
        $targetUser = $userModel::where(function ($q) use ($query) {
            $q->where('username', $query)
              ->orWhere('email', $query);
        })->first();

        if (! $targetUser) {
            $safeQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
            return response()->json([
                'message' => "No user found matching '{$safeQuery}'. Please check the username or identifier.",
                'errors' => ['user_query' => ['No user found matching identifier.']],
            ], 422);
        }

        if ($targetUser->nameplates()->where('nameplates.id', $nameplate->id)->exists()) {
            return response()->json([
                'message' => "User {$targetUser->name} already owns the '{$nameplate->name}' nameplate.",
                'errors' => ['user_query' => ['User already owns this nameplate.']],
            ], 422);
        }

        $targetUser->nameplates()->attach($nameplate->id, [
            'gifted_by_id' => $request->user()->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $giftTitle = ! empty($validated['gift_title'])
            ? trim($validated['gift_title'])
            : "Special Gift: {$nameplate->name} Nameplate";

        $giftDescription = ! empty($validated['gift_description'])
            ? trim($validated['gift_description'])
            : "Congratulations! An administrator has gifted you the \"{$nameplate->name}\" nameplate. You can equip it now in your Profile settings.";

        $notif = ChatifyNotification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $targetUser->getKey(),
            'type' => ChatifyNotification::TYPE_INFORMATION,
            'title' => $giftTitle,
            'body' => $giftDescription,
            'image_url' => $nameplate->image_url,
            'data' => [
                'nameplate_id' => $nameplate->id,
                'nameplate_name' => $nameplate->name,
                'gifted_by_id' => $request->user()->getKey(),
                'gifted_by' => $request->user()->name,
            ],
            'status' => ChatifyNotification::STATUS_UNREAD,
        ]);

        NotificationReceived::dispatch($notif, (int) $targetUser->getKey());

        $displayName = $targetUser->username ? "@{$targetUser->username}" : $targetUser->email;

        return response()->json([
            'message' => "Nameplate '{$nameplate->name}' successfully gifted to {$targetUser->name} ({$displayName})!",
            'data' => [
                'nameplate_id' => $nameplate->id,
                'nameplate_name' => $nameplate->name,
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
            'nameplate_id' => ['required', 'integer', 'exists:nameplates,id'],
            'user_id' => ['required', 'integer'],
        ]);

        $userModel = ChatifyModels::userClass();
        $targetUser = $userModel::findOrFail($validated['user_id']);
        $nameplate = Nameplate::findOrFail($validated['nameplate_id']);

        $targetUser->nameplates()->detach($nameplate->id);

        $settings = UserSetting::where('user_id', $targetUser->getKey())->first();
        if ($settings && (int) $settings->active_nameplate_id === (int) $nameplate->id) {
            $settings->update(['active_nameplate_id' => null]);
        }

        return response()->json([
            'message' => "Nameplate '{$nameplate->name}' was revoked from {$targetUser->name}.",
        ]);
    }
}
