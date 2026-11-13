<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatifyNotification extends Model
{
    public const TYPE_INFORMATION = 'information';
    public const TYPE_REQUEST = 'request';

    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'body',
        'image_url',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function getTable(): string
    {
        return config('chatify.tables.notifications', 'ch_notifications');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'user_id');
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInformation(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INFORMATION);
    }

    public function scopeRequests(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_REQUEST);
    }
}
