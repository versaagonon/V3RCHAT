<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A paired profile animation: pair-1 plays once as the entrance, pair-2 loops
 * afterwards. Both files are 450x880 APNG and are rendered across the whole
 * contact-info modal.
 */
class ProfileEffect extends ChatifyModel
{
    protected $table = 'profile_effects';

    protected $fillable = [
        'name',
        'slug',
        'pair1_file',
        'pair2_file',
        'pair1_duration_ms',
        'pair2_duration_ms',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'pair1_duration_ms' => 'integer',
            'pair2_duration_ms' => 'integer',
            'is_system' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getPair1UrlAttribute(): string
    {
        return url('/vendor/chatify/profileeffects/' . $this->pair1_file) . '?v=' . $this->fileVersion($this->pair1_file);
    }

    public function getPair2UrlAttribute(): string
    {
        return url('/vendor/chatify/profileeffects/' . $this->pair2_file) . '?v=' . $this->fileVersion($this->pair2_file);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->pair1_url;
    }

    /**
     * Cache-busting token for the served APNG. Without it a browser that already
     * cached a pair file keeps displaying the old (frozen) animation even after
     * the asset is re-encoded, because the URL is identical.
     */
    private function fileVersion(string $fileName): int
    {
        $path = public_path('vendor/chatify/profileeffects/' . $fileName);

        return is_file($path)
            ? (int) filemtime($path)
            : (int) ($this->updated_at?->timestamp ?? 0);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \Chatify\Support\ChatifyModels::userClass(),
            'user_profile_effects',
            'profile_effect_id',
            'user_id'
        )->withTimestamps();
    }
}
