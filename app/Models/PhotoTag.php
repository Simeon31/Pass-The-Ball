<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class PhotoTag extends Model
{
    use HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
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
            ->slugsShouldBeNoLongerThan(100);
    }

    /**
     * Get the user who owns the tag.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the photos associated with the tag.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'photo_photo_tag')
            ->withTimestamps();
    }

    /**
     * Get the photos count for the tag.
     */
    public function getPhotosCountAttribute(): int
    {
        return $this->photos()->count();
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get popular tags (with most photos).
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('photos')
            ->orderBy('photos_count', 'desc')
            ->limit($limit);
    }
}
