<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizationService
{
    protected ImageManager $manager;
    protected string $disk = 'public';

    // Image size configurations
    protected array $sizes = [
        'thumbnail' => ['width' => 300, 'height' => 300, 'quality' => 85],
        'medium' => ['width' => 800, 'height' => 600, 'quality' => 85],
        'large' => ['width' => 1920, 'height' => 1080, 'quality' => 85],
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Process and optimize an uploaded photo with multiple sizes.
     *
     * @param UploadedFile $file
     * @param string $basePath Base storage path (e.g., 'photos/1/my-album')
     * @param bool $preserveOriginal Whether to keep the original uncompressed file
     * @param string|null $filename Optional descriptive filename (will be slugified)
     * @return array Array containing paths and metadata
     */
    public function processPhoto(UploadedFile $file, string $basePath, bool $preserveOriginal = true, ?string $filename = null): array
    {
        $timestamp = time();
        $uniqueId = uniqid();
        $extension = $file->getClientOriginalExtension();

        // Create SEO-friendly filename
        $baseFilename = $filename
            ? \Illuminate\Support\Str::slug($filename) . "_{$timestamp}_{$uniqueId}"
            : "{$timestamp}_{$uniqueId}";

        // Read the image once
        $image = $this->manager->read($file);

        // Extract EXIF data before any modifications
        $metadata = $this->extractMetadata($file);

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        $paths = [
            'metadata' => $metadata,
            'width' => $originalWidth,
            'height' => $originalHeight,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        // Save original file if requested
        if ($preserveOriginal) {
            $originalPath = "{$basePath}/original_{$baseFilename}.{$extension}";
            Storage::disk($this->disk)->put($originalPath, $file->get());
            $paths['original_file_path'] = $originalPath;
        }

        // Generate thumbnail (square crop)
        $thumbnailPath = "{$basePath}/thumb_{$baseFilename}.jpg";
        $thumbnail = clone $image;
        $thumbnail->cover(
            $this->sizes['thumbnail']['width'],
            $this->sizes['thumbnail']['height']
        );
        Storage::disk($this->disk)->put(
            $thumbnailPath,
            $thumbnail->toJpeg($this->sizes['thumbnail']['quality'])
        );
        $paths['thumbnail_path'] = $thumbnailPath;

        // Generate medium size (maintain aspect ratio)
        $mediumPath = "{$basePath}/medium_{$baseFilename}.jpg";
        $medium = clone $image;
        $medium->scale(
            width: $this->sizes['medium']['width'],
            height: $this->sizes['medium']['height']
        );
        Storage::disk($this->disk)->put(
            $mediumPath,
            $medium->toJpeg($this->sizes['medium']['quality'])
        );
        $paths['medium_path'] = $mediumPath;

        // Generate large/main size (maintain aspect ratio)
        $largePath = "{$basePath}/large_{$baseFilename}.jpg";
        $large = clone $image;
        $large->scale(
            width: $this->sizes['large']['width'],
            height: $this->sizes['large']['height']
        );
        Storage::disk($this->disk)->put(
            $largePath,
            $large->toJpeg($this->sizes['large']['quality'])
        );
        $paths['file_path'] = $largePath; // Main display image

        return $paths;
    }

    /**
     * Process an album cover image.
     *
     * @param UploadedFile $file
     * @param string $basePath
     * @param string|null $filename Optional descriptive filename (will be slugified)
     * @return string Path to the cover image
     */
    public function processCoverImage(UploadedFile $file, string $basePath, ?string $filename = null): string
    {
        $timestamp = time();
        $uniqueId = uniqid();

        // Create SEO-friendly filename
        $baseFilename = $filename
            ? \Illuminate\Support\Str::slug($filename) . "_{$timestamp}_{$uniqueId}"
            : "{$timestamp}_{$uniqueId}";

        $coverPath = "{$basePath}/cover_{$baseFilename}.jpg";

        // Process cover image (16:9 aspect ratio, 1200x675)
        $image = $this->manager->read($file);
        $image->cover(1200, 675);

        Storage::disk($this->disk)->put(
            $coverPath,
            $image->toJpeg(85)
        );

        return $coverPath;
    }

    /**
     * Extract and sanitize EXIF metadata from an image.
     *
     * @param UploadedFile $file
     * @return array Sanitized metadata
     */
    protected function extractMetadata(UploadedFile $file): array
    {
        $metadata = [];

        try {
            // Try to read EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getPathname());

                if ($exif !== false) {
                    // Extract common useful EXIF data
                    $metadata['camera'] = [
                        'make' => $exif['Make'] ?? null,
                        'model' => $exif['Model'] ?? null,
                    ];

                    $metadata['settings'] = [
                        'iso' => $exif['ISOSpeedRatings'] ?? null,
                        'aperture' => $exif['FNumber'] ?? null,
                        'shutter_speed' => $exif['ExposureTime'] ?? null,
                        'focal_length' => $exif['FocalLength'] ?? null,
                    ];

                    $metadata['datetime'] = [
                        'taken' => $exif['DateTimeOriginal'] ?? null,
                    ];

                    // GPS data (sanitized - no exact coordinates)
                    if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
                        $metadata['location'] = [
                            'has_gps' => true,
                            // Optionally store city/region but not exact coordinates for privacy
                        ];
                    }

                    // Remove empty values
                    $metadata = array_filter($metadata, function ($value) {
                        return !empty(array_filter((array) $value));
                    });
                }
            }
        } catch (\Exception $e) {
            // Silently fail if EXIF extraction fails
            $metadata['error'] = 'Could not extract EXIF data';
        }

        return $metadata;
    }

    /**
     * Delete all sizes of a photo from storage.
     *
     * @param array $paths Array of file paths to delete
     * @return void
     */
    public function deletePhoto(array $paths): void
    {
        $pathKeys = ['file_path', 'original_file_path', 'thumbnail_path', 'medium_path'];

        foreach ($pathKeys as $key) {
            if (!empty($paths[$key])) {
                $path = $paths[$key];

                // Strip leading '/storage/' if present
                if (str_starts_with($path, '/storage/')) {
                    $path = substr($path, strlen('/storage/'));
                }

                Storage::disk($this->disk)->delete($path);
            }
        }
    }

    /**
     * Delete a single image file.
     *
     * @param string $path
     * @return void
     */
    public function deleteImage(string $path): void
    {
        if (empty($path)) {
            return;
        }

        // Strip leading '/storage/' if present
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, strlen('/storage/'));
        }

        Storage::disk($this->disk)->delete($path);
    }

    /**
     * Set custom size configurations.
     *
     * @param string $size Size name (thumbnail, medium, large)
     * @param int $width
     * @param int $height
     * @param int $quality
     * @return self
     */
    public function setSize(string $size, int $width, int $height, int $quality = 85): self
    {
        $this->sizes[$size] = compact('width', 'height', 'quality');
        return $this;
    }

    /**
     * Set the storage disk.
     *
     * @param string $disk
     * @return self
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Generate a WebP version of an image (optional optimization).
     *
     * @param string $sourcePath Path to the source image
     * @param string $destinationPath Path where WebP should be saved
     * @param int $quality Quality (0-100)
     * @return string|null Path to WebP file or null on failure
     */
    public function generateWebP(string $sourcePath, string $destinationPath, int $quality = 85): ?string
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($sourcePath);
            $image = $this->manager->read($fullPath);

            $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $destinationPath);
            Storage::disk($this->disk)->put(
                $webpPath,
                $image->toWebp($quality)
            );

            return $webpPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get image dimensions from a file.
     *
     * @param UploadedFile $file
     * @return array ['width' => int, 'height' => int]
     */
    public function getDimensions(UploadedFile $file): array
    {
        try {
            $image = $this->manager->read($file);
            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            return ['width' => null, 'height' => null];
        }
    }
}
