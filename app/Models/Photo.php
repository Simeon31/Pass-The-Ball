<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Photo extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'album_id',
        'user_id',
        'title',
        'slug',
        'description',
        'file_path',
        'original_file_path',
        'thumbnail_path',
        'medium_path',
        'mime_type',
        'size',
        'width',
        'height',
        'views_count',
        'downloads_count',
        'metadata',
        'deleted_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
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
            ->generateSlugsFrom(['title', 'id'])
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
     * Get the album that owns the photo.
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get the user who uploaded the photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who deleted the photo.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the tags associated with the photo.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PhotoTag::class, 'photo_photo_tag')
            ->withTimestamps();
    }

    /**
     * Get the main photo URL.
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return $this->buildAssetUrl($this->file_path);
    }

    /**
     * Get the thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return $this->url; // Fallback to main image
        }

        return $this->buildAssetUrl($this->thumbnail_path);
    }

    /**
     * Get the medium size URL.
     */
    public function getMediumUrlAttribute(): ?string
    {
        if (!$this->medium_path) {
            return $this->url; // Fallback to main image
        }

        return $this->buildAssetUrl($this->medium_path);
    }

    /**
     * Get the original photo URL.
     */
    public function getOriginalUrlAttribute(): ?string
    {
        if (!$this->original_file_path) {
            return $this->url; // Fallback to main image
        }

        return $this->buildAssetUrl($this->original_file_path);
    }

    /**
     * Build asset URL from path.
     */
    protected function buildAssetUrl(string $path): string
    {
        // Check if the file actually exists
        if (!Storage::disk('public')->exists($path)) {
            // Return placeholder image using a service like placeholder.com or picsum.photos
            // Using dimensions from the photo if available
            $width = $this->width ?? 800;
            $height = $this->height ?? 600;
            return "https://picsum.photos/{$width}/{$height}?random=" . $this->id;
        }

        // If the path starts with '/storage/', it's already a full path
        if (str_starts_with($path, '/storage/')) {
            return asset($path);
        }

        // Otherwise, construct the full path
        return asset('storage/' . $path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Increment the views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment the downloads count.
     */
    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    /**
     * Scope to filter by album.
     */
    public function scopeByAlbum($query, $albumId)
    {
        return $query->where('album_id', $albumId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by tag.
     */
    public function scopeWithTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('photo_tags.id', $tagId);
        });
    }

    /**
     * Scope to order by most viewed.
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope to order by most downloaded.
     */
    public function scopeMostDownloaded($query)
    {
        return $query->orderBy('downloads_count', 'desc');
    }
}
