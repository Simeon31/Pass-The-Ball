<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{

    const UPDATED_AT = null;

    // Reaction types
    const TYPE_LIKE = 'like';
    const TYPE_LOVE = 'love';
    const TYPE_HAHA = 'haha';
    const TYPE_WOW = 'wow';
    const TYPE_SAD = 'sad';
    const TYPE_ANGRY = 'angry';

    const TYPES = [
        self::TYPE_LIKE,
        self::TYPE_LOVE,
        self::TYPE_HAHA,
        self::TYPE_WOW,
        self::TYPE_SAD,
        self::TYPE_ANGRY,
    ];

    protected $fillable = [
        'post_id',
        'user_id',
        'type',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
