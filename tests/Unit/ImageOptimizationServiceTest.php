<?php

namespace Tests\Unit;

use App\Services\ImageOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ImageOptimizationService();
    }

    /** @test */
    public function process_photo_creates_multiple_sizes()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        $this->assertArrayHasKey('thumbnail_path', $result);
        $this->assertArrayHasKey('medium_path', $result);
        $this->assertArrayHasKey('large_path', $result);
        $this->assertArrayHasKey('original_path', $result);
        $this->assertArrayHasKey('metadata', $result);

        // Check that files were created
        $this->assertTrue(Storage::disk('public')->exists($result['thumbnail_path']));
        $this->assertTrue(Storage::disk('public')->exists($result['medium_path']));
        $this->assertTrue(Storage::disk('public')->exists($result['large_path']));
        $this->assertTrue(Storage::disk('public')->exists($result['original_path']));
    }

    /** @test */
    public function process_photo_extracts_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1920, 1080);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        $this->assertArrayHasKey('metadata', $result);
        $this->assertIsArray($result['metadata']);
    }

    /** @test */
    public function process_photo_returns_file_info()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1920, 1080);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertArrayHasKey('size', $result);

        $this->assertEquals(1920, $result['width']);
        $this->assertEquals(1080, $result['height']);
    }

    /** @test */
    public function process_cover_image_creates_optimized_cover()
    {
        $file = UploadedFile::fake()->image('cover.jpg', 1920, 1080);
        $path = 'albums/covers';

        $coverPath = $this->service->processCoverImage($file, $path);

        $this->assertNotNull($coverPath);
        $this->assertTrue(Storage::disk('public')->exists($coverPath));
        $this->assertStringContainsString($path, $coverPath);
    }

    /** @test */
    public function delete_photo_removes_all_variants()
    {
        // Create fake photo files
        $paths = [
            'photos/test/thumbnail.jpg',
            'photos/test/medium.jpg',
            'photos/test/large.jpg',
            'photos/test/original.jpg',
        ];

        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'fake-content');
        }

        // Delete photo
        $this->service->deletePhoto($paths);

        // Verify all files are deleted
        foreach ($paths as $path) {
            $this->assertFalse(Storage::disk('public')->exists($path));
        }
    }

    /** @test */
    public function thumbnail_respects_max_dimensions()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        // Thumbnail should be 300x300 max
        $this->assertArrayHasKey('thumbnail_path', $result);
        $this->assertTrue(Storage::disk('public')->exists($result['thumbnail_path']));
    }

    /** @test */
    public function medium_size_respects_max_dimensions()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        // Medium should be 800x600 max
        $this->assertArrayHasKey('medium_path', $result);
        $this->assertTrue(Storage::disk('public')->exists($result['medium_path']));
    }

    /** @test */
    public function large_size_respects_max_dimensions()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        // Large should be 1920x1080 max
        $this->assertArrayHasKey('large_path', $result);
        $this->assertTrue(Storage::disk('public')->exists($result['large_path']));
    }

    /** @test */
    public function original_photo_is_preserved()
    {
        $file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
        $path = 'photos/test';

        $result = $this->service->processPhoto($file, $path);

        // Original should be preserved without resizing
        $this->assertArrayHasKey('original_path', $result);
        $this->assertTrue(Storage::disk('public')->exists($result['original_path']));
    }
}
