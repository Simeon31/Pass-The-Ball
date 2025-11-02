<?php

namespace Tests\Unit;

use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function photo_belongs_to_user()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $photo->user);
        $this->assertEquals($user->id, $photo->user->id);
    }

    /** @test */
    public function photo_belongs_to_album()
    {
        $album = Album::factory()->create();
        $photo = Photo::factory()->create(['album_id' => $album->id]);

        $this->assertInstanceOf(Album::class, $photo->album);
        $this->assertEquals($album->id, $photo->album->id);
    }

    /** @test */
    public function photo_has_many_tags()
    {
        $photo = Photo::factory()->create();
        $tag1 = PhotoTag::create(['name' => 'sunset', 'slug' => 'sunset']);
        $tag2 = PhotoTag::create(['name' => 'nature', 'slug' => 'nature']);

        $photo->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $photo->tags);
        $this->assertInstanceOf(PhotoTag::class, $photo->tags->first());
    }

    /** @test */
    public function photo_thumbnail_url_accessor_returns_full_url()
    {
        $photo = Photo::factory()->create([
            'thumbnail_path' => 'photos/test-thumbnail.jpg',
        ]);

        $this->assertStringContainsString('/storage/photos/test-thumbnail.jpg', $photo->thumbnail_url);
    }

    /** @test */
    public function photo_medium_url_accessor_returns_full_url()
    {
        $photo = Photo::factory()->create([
            'medium_path' => 'photos/test-medium.jpg',
        ]);

        $this->assertStringContainsString('/storage/photos/test-medium.jpg', $photo->medium_url);
    }

    /** @test */
    public function photo_large_url_accessor_returns_full_url()
    {
        $photo = Photo::factory()->create([
            'large_path' => 'photos/test-large.jpg',
        ]);

        $this->assertStringContainsString('/storage/photos/test-large.jpg', $photo->large_url);
    }

    /** @test */
    public function photo_original_url_accessor_returns_full_url()
    {
        $photo = Photo::factory()->create([
            'original_file_path' => 'photos/test-original.jpg',
        ]);

        $this->assertStringContainsString('/storage/photos/test-original.jpg', $photo->original_url);
    }

    /** @test */
    public function photo_generates_slug_from_title()
    {
        $photo = Photo::factory()->create(['title' => 'Beautiful Sunset']);

        $this->assertEquals('beautiful-sunset', $photo->slug);
    }

    /** @test */
    public function photo_uses_soft_deletes()
    {
        $photo = Photo::factory()->create();
        $photoId = $photo->id;

        $photo->delete();

        $this->assertSoftDeleted('photos', ['id' => $photoId]);
        $this->assertNotNull(Photo::withTrashed()->find($photoId)->deleted_at);
    }

    /** @test */
    public function photo_metadata_is_cast_to_array()
    {
        $metadata = [
            'exif' => [
                'Make' => 'Canon',
                'Model' => 'EOS R5',
                'ISO' => 400,
            ],
        ];

        $photo = Photo::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($photo->metadata);
        $this->assertEquals('Canon', $photo->metadata['exif']['Make']);
    }

    /** @test */
    public function photo_views_count_can_be_incremented()
    {
        $photo = Photo::factory()->create(['views_count' => 0]);

        $photo->increment('views_count');

        $this->assertEquals(1, $photo->fresh()->views_count);
    }

    /** @test */
    public function photo_downloads_count_can_be_incremented()
    {
        $photo = Photo::factory()->create(['downloads_count' => 0]);

        $photo->increment('downloads_count');

        $this->assertEquals(1, $photo->fresh()->downloads_count);
    }

    /** @test */
    public function most_viewed_scope_orders_by_views_count()
    {
        $photo1 = Photo::factory()->create(['views_count' => 10]);
        $photo2 = Photo::factory()->create(['views_count' => 100]);
        $photo3 = Photo::factory()->create(['views_count' => 50]);

        $photos = Photo::mostViewed()->get();

        $this->assertEquals($photo2->id, $photos->first()->id);
        $this->assertEquals($photo3->id, $photos->skip(1)->first()->id);
        $this->assertEquals($photo1->id, $photos->last()->id);
    }

    /** @test */
    public function recent_scope_orders_by_created_at()
    {
        $photo1 = Photo::factory()->create(['created_at' => now()->subDays(5)]);
        $photo2 = Photo::factory()->create(['created_at' => now()->subDay()]);
        $photo3 = Photo::factory()->create(['created_at' => now()]);

        $photos = Photo::recent()->get();

        $this->assertEquals($photo3->id, $photos->first()->id);
        $this->assertEquals($photo2->id, $photos->skip(1)->first()->id);
        $this->assertEquals($photo1->id, $photos->last()->id);
    }
}
