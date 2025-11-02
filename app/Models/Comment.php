<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    /**
     * Maximum nesting depth for comments
     */
    public const MAX_DEPTH = 5;

    protected $fillable = [
        'post_id',
        'user_id',
        'comment',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the post that owns the comment.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user that created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (for nested replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get direct replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all nested replies with eager loading.
     */
    public function repliesWithRelations(): HasMany
    {
        return $this->replies()->with(['user', 'reactions']);
    }

    /**
     * Get all reactions for this comment.
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Calculate the depth of this comment in the tree.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent_id !== null && $depth < self::MAX_DEPTH) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }

    /**
     * Check if comment is at maximum nesting depth.
     */
    public function isAtMaxDepth(): bool
    {
        return $this->getDepth() >= self::MAX_DEPTH;
    }

    /**
     * Check if this is a top-level comment.
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get all ancestor comments (path to root).
     */
    public function ancestors()
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Scope to get only top-level comments.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get comments with all nested replies loaded.
     */
    public function scopeWithNestedReplies($query)
    {
        return $query->with([
            'user',
            'reactions',
            'replies' => function ($query) {
                $query->withNestedReplies();
            }
        ]);
    }
}

