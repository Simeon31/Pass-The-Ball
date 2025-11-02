<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follower extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at in the migration.
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected $table = 'followers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'follower_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user being followed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who is following.
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
}
