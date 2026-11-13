<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Chatify\Traits\InteractsWithChatify;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, InteractsWithChatify;

    public function borders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Chatify\Models\AvatarBorder::class,
            'user_avatar_borders',
            'user_id',
            'border_id'
        )->withTimestamps();
    }

    public function nameplates(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Chatify\Models\Nameplate::class,
            'user_nameplates',
            'user_id',
            'nameplate_id'
        )->withTimestamps();
    }

    public function profileEffects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Chatify\Models\ProfileEffect::class,
            'user_profile_effects',
            'user_id',
            'profile_effect_id'
        )->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? 'node') === 'admin';
    }

    public function hasBorder(int|string $borderId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->borders()->where('avatar_borders.id', (int) $borderId)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
