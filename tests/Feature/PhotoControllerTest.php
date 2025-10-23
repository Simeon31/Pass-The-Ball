<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function user_can_upload_photos_to_their_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => [$photo],
            'titles' => ['My Photo'],
            'descriptions' => ['A beautiful sunset'],
            'tags' => [['sunset', 'nature']],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('photos', [
            'album_id' => $album->id,
            'user_id' => $user->id,
            'title' => 'My Photo',
            'description' => 'A beautiful sunset',
        ]);

        // Check that photo files were created
        $photo = Photo::where('album_id', $album->id)->first();
        $this->assertTrue(Storage::disk('public')->exists($photo->thumbnail_path));
        $this->assertTrue(Storage::disk('public')->exists($photo->medium_path));
        $this->assertTrue(Storage::disk('public')->exists($photo->large_path));
        $this->assertTrue(Storage::disk('public')->exists($photo->original_file_path));
    }

    /** @test */
    public function user_can_upload_multiple_photos()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photos = [
            UploadedFile::fake()->image('photo1.jpg', 1920, 1080),
            UploadedFile::fake()->image('photo2.jpg', 1920, 1080),
            UploadedFile::fake()->image('photo3.jpg', 1920, 1080),
        ];

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => $photos,
            'titles' => ['Photo 1', 'Photo 2', 'Photo 3'],
            'descriptions' => ['Desc 1', 'Desc 2', 'Desc 3'],
            'tags' => [['tag1'], ['tag2'], ['tag3']],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('photos', 3);
    }

    /** @test */
    public function cannot_upload_more_than_20_photos_at_once()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        // Create 21 fake photos
        $photos = [];
        for ($i = 0; $i < 21; $i++) {
            $photos[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => $photos,
        ]);

        $response->assertSessionHasErrors(['photos']);
    }

    /** @test */
    public function photo_must_be_valid_image()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => [UploadedFile::fake()->create('document.pdf')],
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function photo_must_not_exceed_max_size()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $largePhoto = UploadedFile::fake()->image('photo.jpg')->size(11000); // 11MB

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => [$largePhoto],
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function user_cannot_upload_to_another_users_album()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $otherUser->id]);
        $photo = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $otherUser,
            'album' => $album->slug,
        ]), [
            'photos' => [$photo],
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function tags_are_created_and_attached_to_photos()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => [$photo],
            'tags' => [['sunset', 'nature', 'landscape']],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('photo_tags', ['name' => 'sunset', 'slug' => 'sunset']);
        $this->assertDatabaseHas('photo_tags', ['name' => 'nature', 'slug' => 'nature']);
        $this->assertDatabaseHas('photo_tags', ['name' => 'landscape', 'slug' => 'landscape']);

        $photo = Photo::first();
        $this->assertCount(3, $photo->tags);
    }

    /** @test */
    public function duplicate_tags_are_not_created()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        // Create existing tag
        PhotoTag::create(['name' => 'sunset', 'slug' => 'sunset']);

        $photo = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->post(route('gallery.photos.store', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'photos' => [$photo],
            'tags' => [['sunset', 'nature']],
        ]);

        $response->assertRedirect();

        // Should still only have 2 tags (sunset already existed)
        $this->assertDatabaseCount('photo_tags', 2);
    }

    /** @test */
    public function user_can_view_single_photo()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('gallery.photos.show', [
            'user' => $user,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Gallery/PhotoShow')
                ->has('photo')
                ->has('album')
        );
    }

    /** @test */
    public function viewing_photo_increments_view_count()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $user->id,
            'views_count' => 5,
        ]);

        $this->post(route('gallery.photos.incrementView', [
            'user' => $user,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]));

        $photo->refresh();
        $this->assertEquals(6, $photo->views_count);
    }

    /** @test */
    public function user_can_update_photo_metadata()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('gallery.photos.update', [
            'user' => $user,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'tags' => ['new-tag', 'another-tag'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Photo updated successfully.');

        $photo->refresh();
        $this->assertEquals('Updated Title', $photo->title);
        $this->assertEquals('Updated description', $photo->description);
        $this->assertCount(2, $photo->tags);
    }

    /** @test */
    public function user_cannot_update_another_users_photo()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $otherUser->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->put(route('gallery.photos.update', [
            'user' => $otherUser,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]), [
            'title' => 'Hacked Title',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_photo()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('gallery.photos.destroy', [
            'user' => $user,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Photo deleted successfully.');

        $this->assertSoftDeleted('photos', ['id' => $photo->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_photo()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $otherUser->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->delete(route('gallery.photos.destroy', [
            'user' => $otherUser,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]));

        $response->assertForbidden();
        $this->assertDatabaseHas('photos', ['id' => $photo->id, 'deleted_at' => null]);
    }

    /** @test */
    public function user_can_download_photo()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $photo = Photo::factory()->create([
            'album_id' => $album->id,
            'user_id' => $user->id,
            'downloads_count' => 0,
        ]);

        // Create a fake file for the photo
        Storage::disk('public')->put($photo->original_file_path, 'fake-image-content');

        $response = $this->actingAs($user)->get(route('gallery.photos.download', [
            'user' => $user,
            'album' => $album->slug,
            'photo' => $photo->slug,
        ]));

        $response->assertOk();
        $response->assertDownload();

        // Check that download count was incremented
        $photo->refresh();
        $this->assertEquals(1, $photo->downloads_count);
    }
}
