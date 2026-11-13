<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Events\NotificationReceived;
use Chatify\Models\ChatifyNotification;
use Chatify\Models\ProfileEffect;
use Chatify\Models\UserSetting;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AdminProfileEffectController extends Controller
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

        $effects = ProfileEffect::withCount('users')->orderByDesc('id')->get();

        return response()->json([
            'data' => $effects->map(fn (ProfileEffect $effect) => $this->payload($effect)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'pair1' => ['required', 'file', 'mimes:png,apng,webp,gif', 'max:10240'],
            'pair2' => ['required', 'file', 'mimes:png,apng,webp,gif', 'max:10240'],
        ]);

        $name = trim((string) $request->input('name'));
        $pair1 = $request->file('pair1');
        $pair2 = $request->file('pair2');

        if (! $pair1 instanceof UploadedFile || ! $pair2 instanceof UploadedFile) {
            abort(422, 'Both pair files are required.');
        }

        $ext1 = $this->extensionFor($pair1);
        $ext2 = $this->extensionFor($pair2);

        $baseSlug = Str::slug($name) ?: 'effect';
        $uniqueSlug = $baseSlug . '_' . time() . '_' . Str::random(6);
        $fileName1 = $uniqueSlug . '-pair-1.' . $ext1;
        $fileName2 = $uniqueSlug . '-pair-2.' . $ext2;

        $targetDir = public_path('vendor/chatify/profileeffects');
        if (! file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $pair1->move($targetDir, $fileName1);
        $pair2->move($targetDir, $fileName2);

        // APNGs encoded with num_plays=1 stop on their final frame and never
        // replay while the same resource URL is reused (thumbnails, modal
        // re-opens). Force both layers to loop; the client already owns the
        // pair-1 -> pair-2 hand-off with its duration timer.
        $this->forceApngLoop($targetDir . '/' . $fileName1);
        $this->forceApngLoop($targetDir . '/' . $fileName2);

        // Mirror into the Vite dev public dir so HMR serves it immediately.
        $frontendPublicDir = base_path('../frontend/public/vendor/chatify/profileeffects');
        if (file_exists($frontendPublicDir)) {
            @copy($targetDir . '/' . $fileName1, $frontendPublicDir . '/' . $fileName1);
            @copy($targetDir . '/' . $fileName2, $frontendPublicDir . '/' . $fileName2);
        }

        $effect = ProfileEffect::create([
            'name' => $name,
            'slug' => $uniqueSlug,
            'pair1_file' => $fileName1,
            'pair2_file' => $fileName2,
            'pair1_duration_ms' => $this->apngDurationMs($targetDir . '/' . $fileName1),
            'pair2_duration_ms' => $this->apngDurationMs($targetDir . '/' . $fileName2),
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Profile effect uploaded successfully.',
            'data' => $this->payload($effect),
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $effect = ProfileEffect::find($id);
        if (! $effect) {
            abort(404, 'Profile effect not found.');
        }

        if ($effect->is_system) {
            return response()->json([
                'message' => 'System pre-installed profile effects cannot be deleted.',
            ], 422);
        }

        UserSetting::where('active_profile_effect_id', $effect->id)->update(['active_profile_effect_id' => null]);

        foreach ([$effect->pair1_file, $effect->pair2_file] as $fileName) {
            $filePath = public_path('vendor/chatify/profileeffects/' . $fileName);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $effect->users()->detach();
        $effect->delete();

        return response()->json([
            'message' => "Profile effect '{$effect->name}' was deleted successfully.",
        ]);
    }

    public function gift(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'profile_effect_id' => ['required', 'integer', 'exists:profile_effects,id'],
            'user_query' => ['required', 'string'],
            'gift_title' => ['nullable', 'string', 'max:255'],
            'gift_description' => ['nullable', 'string'],
        ]);

        $effect = ProfileEffect::findOrFail($validated['profile_effect_id']);
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

        if ($targetUser->profileEffects()->where('profile_effects.id', $effect->id)->exists()) {
            return response()->json([
                'message' => "User {$targetUser->name} already owns the '{$effect->name}' profile effect.",
                'errors' => ['user_query' => ['User already owns this profile effect.']],
            ], 422);
        }

        $targetUser->profileEffects()->attach($effect->id, [
            'gifted_by_id' => $request->user()->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $giftTitle = ! empty($validated['gift_title'])
            ? trim($validated['gift_title'])
            : "Special Gift: {$effect->name} Profile Effect";

        $giftDescription = ! empty($validated['gift_description'])
            ? trim($validated['gift_description'])
            : "Congratulations! An administrator has gifted you the \"{$effect->name}\" profile effect. You can equip it now in your Profile settings.";

        $notif = ChatifyNotification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $targetUser->getKey(),
            'type' => ChatifyNotification::TYPE_INFORMATION,
            'title' => $giftTitle,
            'body' => $giftDescription,
            'image_url' => $effect->thumbnail_url,
            'data' => [
                'profile_effect_id' => $effect->id,
                'profile_effect_name' => $effect->name,
                'gifted_by_id' => $request->user()->getKey(),
                'gifted_by' => $request->user()->name,
            ],
            'status' => ChatifyNotification::STATUS_UNREAD,
        ]);

        NotificationReceived::dispatch($notif, (int) $targetUser->getKey());

        $displayName = $targetUser->username ? "@{$targetUser->username}" : $targetUser->email;

        return response()->json([
            'message' => "Profile effect '{$effect->name}' successfully gifted to {$targetUser->name} ({$displayName})!",
            'data' => [
                'profile_effect_id' => $effect->id,
                'profile_effect_name' => $effect->name,
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
            'profile_effect_id' => ['required', 'integer', 'exists:profile_effects,id'],
            'user_id' => ['required', 'integer'],
        ]);

        $userModel = ChatifyModels::userClass();
        $targetUser = $userModel::findOrFail($validated['user_id']);
        $effect = ProfileEffect::findOrFail($validated['profile_effect_id']);

        $targetUser->profileEffects()->detach($effect->id);

        $settings = UserSetting::where('user_id', $targetUser->getKey())->first();
        if ($settings && (int) $settings->active_profile_effect_id === (int) $effect->id) {
            $settings->update(['active_profile_effect_id' => null]);
        }

        return response()->json([
            'message' => "Profile effect '{$effect->name}' was revoked from {$targetUser->name}.",
        ]);
    }

    private function payload(ProfileEffect $effect): array
    {
        return [
            'id' => $effect->id,
            'name' => $effect->name,
            'slug' => $effect->slug,
            'pair1_file' => $effect->pair1_file,
            'pair2_file' => $effect->pair2_file,
            'thumbnail_url' => $effect->thumbnail_url,
            'pair1_url' => $effect->pair1_url,
            'pair2_url' => $effect->pair2_url,
            'pair1_duration_ms' => (int) $effect->pair1_duration_ms,
            'pair2_duration_ms' => (int) $effect->pair2_duration_ms,
            'is_system' => (bool) $effect->is_system,
            'users_count' => (int) ($effect->users_count ?? 0),
            'created_at' => $effect->created_at?->toISOString(),
        ];
    }

    private function extensionFor(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/png', 'image/apng' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => abort(422, 'Unsupported image format. Allowed formats: PNG, APNG, WebP, GIF.'),
        };
    }

    /**
     * Rewrite the APNG acTL chunk so num_plays becomes 0 (loop forever).
     *
     * Animated images are decoded once per resource URL. A pair file encoded
     * with num_plays=1 therefore stays frozen on its last frame every time the
     * same URL is shown again (admin thumbnail, profile picker, modal re-open).
     * Forcing the loop keeps every surface animated; the modal still controls
     * the entrance -> loop hand-off with the parsed pair-1 duration.
     */
    private function forceApngLoop(string $path): void
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false || strlen($bytes) < 20 || substr($bytes, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return;
        }

        $offset = 8;
        $total = strlen($bytes);

        while ($offset + 12 <= $total) {
            $length = (int) unpack('N', substr($bytes, $offset, 4))[1];
            $type = substr($bytes, $offset + 4, 4);

            if ($length < 0 || $offset + 12 + $length > $total) {
                return;
            }

            if ($type === 'acTL') {
                if ($length < 8) {
                    return;
                }

                $plays = (int) unpack('N', substr($bytes, $offset + 12, 4))[1];
                if ($plays === 0) {
                    return;
                }

                $data = substr($bytes, $offset + 8, $length);
                $data = substr($data, 0, 4) . pack('N', 0);
                $chunk = pack('N', strlen($data)) . 'acTL' . $data . pack('N', crc32('acTL' . $data) & 0xffffffff);

                $bytes = substr_replace($bytes, $chunk, $offset, 12 + $length);
                @file_put_contents($path, $bytes);

                return;
            }

            if ($type === 'IEND') {
                return;
            }

            $offset += 12 + $length;
        }
    }

    /**
     * Sum every fcTL frame delay inside an APNG file. The browser plays the
     * entrance (pair-1) on its own schedule, so the client needs this duration
     * to know when to swap to the looping pair-2 layer. Non-animated PNGs fall
     * back to the 5s default used by the seeder.
     */
    private function apngDurationMs(string $path): int
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return 5000;
        }

        $signature = fread($handle, 8);
        if ($signature !== "\x89PNG\r\n\x1a\n") {
            fclose($handle);
            return 5000;
        }

        $totalMs = 0.0;
        $frameCount = 0;

        while (! feof($handle)) {
            $header = fread($handle, 8);
            if ($header === false || strlen($header) < 8) {
                break;
            }

            $chunk = unpack('Nlength/a4type', $header);
            $length = (int) $chunk['length'];
            $type = $chunk['type'];

            if ($type === 'fcTL') {
                $data = fread($handle, 26);
                if ($data === false || strlen($data) < 26) {
                    break;
                }

                $frame = unpack('Nseq/Nwidth/Nheight/Nx/Ny/ndelayNum/ndelayDen', $data);
                $delayNum = (int) $frame['delayNum'];
                $delayDen = (int) $frame['delayDen'];
                if ($delayDen === 0) {
                    $delayDen = 100;
                }
                if ($delayNum > 0) {
                    $totalMs += ($delayNum / $delayDen) * 1000;
                }
                $frameCount++;

                // Skip the remaining fcTL data (dispose_op + blend_op) and its CRC.
                fseek($handle, $length - 26 + 4, SEEK_CUR);
            } else {
                // Skip chunk data + CRC.
                fseek($handle, $length + 4, SEEK_CUR);
            }
        }

        fclose($handle);

        if ($frameCount === 0) {
            return 5000;
        }

        return max(300, (int) round($totalMs));
    }
}
