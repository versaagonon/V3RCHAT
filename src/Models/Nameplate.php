<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Nameplate extends ChatifyModel
{
    protected $table = 'nameplates';

    protected $fillable = [
        'name',
        'slug',
        'file_name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        return url('/vendor/chatify/nameplates/' . $this->file_name);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \Chatify\Support\ChatifyModels::userClass(),
            'user_nameplates',
            'nameplate_id',
            'user_id'
        )->withTimestamps();
    }
}
