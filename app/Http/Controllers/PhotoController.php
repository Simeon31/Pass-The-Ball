<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhotoRequest;
use App\Http\Requests\UpdatePhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PhotoController extends Controller
{
    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Store newly uploaded photos in an album (batch upload).
     */
    public function store(StorePhotoRequest $request, Album $album)
    {
        $this->authorize('update', $album); // Must be able to update album to add photos

        $data = $request->validated();
        $photos = $request->file('photos');
        $uploadedCount = 0;

        foreach ($photos as $index => $file) {
            try {
                // Get title for SEO-friendly filename
                $photoTitle = $data['titles'][$index] ?? $album->title;

                // Process image with multiple sizes
                $result = $this->imageService->processPhoto(
                    $file,
                    "photos/{$album->user_id}/{$album->slug}",
                    preserveOriginal: true,
                    filename: $photoTitle
                );

                // Create photo record
                $photo = Photo::create([
                    'album_id' => $album->id,
                    'user_id' => auth()->id(),
                    'title' => $data['titles'][$index] ?? null,
                    'description' => $data['descriptions'][$index] ?? null,
                    'file_path' => $result['file_path'],
                    'original_file_path' => $result['original_file_path'] ?? null,
                    'thumbnail_path' => $result['thumbnail_path'] ?? null,
                    'medium_path' => $result['medium_path'] ?? null,
                    'mime_type' => $result['mime_type'],
                    'size' => $result['size'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'metadata' => $result['metadata'] ?? [],
                ]);

                // Handle tags if provided
                if (!empty($data['tags'])) {
                    $this->attachTags($photo, $data['tags']);
                }

                // Set as album cover if it's the first photo and album has no cover
                if ($uploadedCount === 0 && !$album->cover_path) {
                    $album->cover_path = $photo->thumbnail_url;
                    $album->save();
                }

                $uploadedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other photos
                logger()->error('Photo upload failed: ' . $e->getMessage());
            }
        }

        $message = $uploadedCount === 1
            ? 'Photo uploaded successfully.'
            : "{$uploadedCount} photos uploaded successfully.";

        return back()->with('status', $message);
    }

    /**
     * Display a single photo.
     */
    public function show(Request $request, string $username, Album $album, Photo $photo): Response
    {
        // Verify photo belongs to this album
        if ($photo->album_id !== $album->id) {
            abort(404);
        }

        // Check viewing permissions using policy
        $this->authorize('view', $photo);

        // Increment view count
        $photo->incrementViews();

        // Load relationships
        $photo->load(['album', 'user', 'tags']);

        return Inertia::render('Gallery/Photo', [
            'photo' => new PhotoResource($photo),
            'album' => $album,
            'can_edit' => auth()->id() === $photo->user_id,
        ]);
    }

    /**
     * Update photo metadata (title, description, tags).
     */
    public function update(UpdatePhotoRequest $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $data = $request->validated();

        // Update basic fields
        $photo->update([
            'title' => $data['title'] ?? $photo->title,
            'description' => $data['description'] ?? $photo->description,
        ]);

        // Handle tag updates
        if (isset($data['tags'])) {
            $this->attachTags($photo, $data['tags']);
        }

        // Handle tag removals
        if (!empty($data['removed_tags'])) {
            $photo->tags()->detach($data['removed_tags']);
        }

        return back()->with('status', 'Photo updated successfully.');
    }

    /**
     * Remove the specified photo (soft delete).
     */
    public function destroy(Photo $photo)
    {
        $this->authorize('delete', $photo);

        // Delete image files from storage
        $this->imageService->deletePhoto([
            'file_path' => $photo->file_path,
            'original_file_path' => $photo->original_file_path,
            'thumbnail_path' => $photo->thumbnail_path,
            'medium_path' => $photo->medium_path,
        ]);

        // Soft delete the photo
        $photo->deleted_by = auth()->id();
        $photo->save();
        $photo->delete();

        return back()->with('status', 'Photo deleted successfully.');
    }

    /**
     * Download a photo file.
     */
    public function download(Photo $photo)
    {
        $this->authorize('download', $photo);

        // Increment download count
        $photo->incrementDownloads();

        // Use original file if available, otherwise use main file
        $filePath = $photo->original_file_path ?? $photo->file_path;

        // Strip leading '/storage/' if present
        if (str_starts_with($filePath, '/storage/')) {
            $filePath = substr($filePath, strlen('/storage/'));
        }

        $fullPath = storage_path('app/public/' . $filePath);

        if (!file_exists($fullPath)) {
            abort(404, 'File not found.');
        }

        $filename = ($photo->title ?? 'photo') . '_' . $photo->id . '.jpg';

        return response()->download($fullPath, $filename);
    }

    /**
     * Increment photo view count (AJAX endpoint).
     */
    public function incrementView(Photo $photo)
    {
        $photo->incrementViews();

        return response()->json(['views_count' => $photo->views_count]);
    }

    /**
     * Attach tags to a photo, creating new tags as needed.
     */
    protected function attachTags(Photo $photo, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);

            if (empty($tagName)) {
                continue;
            }

            // Find or create tag for this user
            $tag = PhotoTag::firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'slug' => Str::slug($tagName),
                ],
                [
                    'name' => $tagName,
                ]
            );

            $tagIds[] = $tag->id;
        }

        // Sync tags (replace existing)
        if (!empty($tagIds)) {
            $photo->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
