<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GroupInvitation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'invited_by',
        'token',
        'token_expires_at',
        'token_used_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'token_used_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (!$invitation->token) {
                $invitation->token = self::generateToken();
            }
        });
    }

    /**
     * Generate a unique token
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Get the group that this invitation belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who was invited
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who sent the invitation
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation is valid
     */
    public function isValid(): bool
    {
        return $this->status === 'pending'
            && !$this->token_used_at
            && $this->token_expires_at->isFuture();
    }

    /**
     * Check if the invitation has expired
     */
    public function isExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    /**
     * Mark the invitation as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'token_used_at' => now(),
        ]);
    }

    /**
     * Mark the invitation as rejected
     */
    public function markAsRejected(): void
    {
        $this->update([
            'status' => 'rejected',
            'token_used_at' => now(),
        ]);
    }

    /**
     * Mark the invitation as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Scope for pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for valid invitations
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'pending')
            ->whereNull('token_used_at')
            ->where('token_expires_at', '>', now());
    }
}
