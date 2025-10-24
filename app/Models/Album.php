<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Album extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'visibility',
        'cover_path',
        'deleted_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
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
            ->generateSlugsFrom('title')
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
     * Get the owner of the album.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who deleted the album.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get all photos in the album.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Get the cover photo URL.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_path) {
            return null;
        }

        // Check if the file actually exists
        if (!Storage::disk('public')->exists($this->cover_path)) {
            // Return placeholder image - using a fixed size for album covers
            return "https://picsum.photos/800/600?random=album-" . $this->id;
        }

        // If the path starts with '/storage/', it's already a full path
        if (str_starts_with($this->cover_path, '/storage/')) {
            return asset($this->cover_path);
        }

        // Otherwise, construct the full path
        return asset('storage/' . $this->cover_path);
    }

    /**
     * Get the photos count for the album.
     */
    public function getPhotosCountAttribute(): int
    {
        return $this->photos()->count();
    }

    /**
     * Scope to filter by visibility.
     */
    public function scopeVisible($query, $visibility = 'public')
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get albums accessible to a given user.
     */
    public function scopeAccessibleTo($query, $userId = null)
    {
        if (!$userId) {
            // Public albums only for guests
            return $query->where('visibility', 'public');
        }

        // For authenticated users, show public albums and their own albums
        return $query->where(function ($q) use ($userId) {
            $q->where('visibility', 'public')
                ->orWhere('user_id', $userId);
        });
    }
}
