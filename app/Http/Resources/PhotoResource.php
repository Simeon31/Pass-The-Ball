<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'album_id' => $this->album_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,

            // Image URLs for different sizes
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'medium_url' => $this->medium_url,
            'original_url' => $this->original_url,

            // File information
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,

            // Image dimensions
            'width' => $this->width,
            'height' => $this->height,

            // Engagement metrics
            'views_count' => $this->views_count,
            'downloads_count' => $this->downloads_count,

            // EXIF and metadata
            'metadata' => $this->metadata,

            // Relationships
            'album' => new AlbumResource($this->whenLoaded('album')),
            'user' => new UserResource($this->whenLoaded('user')),
            'tags' => PhotoTagResource::collection($this->whenLoaded('tags')),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
