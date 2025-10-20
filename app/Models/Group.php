<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Group extends Model
{
    use SoftDeletes, HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'cover_path',
        'thumbnail_path',
        'auto_approval',
        'about',
        'user_id',
        'deleted_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'auto_approval' => 'boolean',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the owner of the group
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all members of the group (through pivot)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
            ->withPivot(['status', 'role', 'created_at'])
            ->withTimestamps()
            ->wherePivot('status', 'approved');
    }

    /**
     * Get all approved members
     */
    public function approvedMembers(): BelongsToMany
    {
        return $this->members();
    }

    /**
     * Get pending join requests
     */
    public function pendingRequests(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
            ->withPivot(['status', 'role', 'created_at'])
            ->wherePivot('status', 'pending');
    }

    /**
     * Get the group's posts
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the group's invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }

    /**
     * Get the user who deleted the group
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Check if a user is a member of the group
     */
    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the owner of the group
     */
    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get the role of a user in the group
     */
    public function getUserRole(User $user): ?string
    {
        $membership = $this->members()
            ->where('user_id', $user->id)
            ->first();

        return $membership?->pivot->role;
    }

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }
}
