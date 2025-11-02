<?php

namespace App\Models;

use App\Enums\GroupRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupUser extends Model
{
    /**
     * Disable updated_at timestamp
     */
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'group_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'status',
        'role',
        'token',
        'token_expired_at',
        'token_used',
        'user_id',
        'group_id',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'role' => GroupRole::class,
            'token_expired_at' => 'datetime',
            'token_used' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user that belongs to this group membership
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group that this membership belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who created this membership (invited or approved)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the membership is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the membership is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the token is valid
     */
    public function isTokenValid(): bool
    {
        return $this->token
            && !$this->token_used
            && $this->token_expired_at
            && $this->token_expired_at->isFuture();
    }
}
