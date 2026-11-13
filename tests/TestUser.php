<?php

declare(strict_types=1);

namespace Chatify\Tests;

use Chatify\Traits\InteractsWithChatify;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class TestUser extends Authenticatable
{
    use HasApiTokens;
    use InteractsWithChatify;

    protected $table = 'users';

    protected $fillable = ['name', 'username', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

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
}
