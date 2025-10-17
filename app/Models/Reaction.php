<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
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
        'reactable_id',
        'reactable_type',
        'user_id',
        'type',
    ];

    /**
     * Get the owning reactable model (Post or Comment).
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
