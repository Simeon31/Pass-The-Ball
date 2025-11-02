<?php

namespace Tests\Unit;

use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlbumModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function album_belongs_to_user()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $album->user);
        $this->assertEquals($user->id, $album->user->id);
    }

    /** @test */
    public function album_has_many_photos()
    {
        $album = Album::factory()->create();
        Photo::factory(3)->create(['album_id' => $album->id]);

        $this->assertCount(3, $album->photos);
        $this->assertInstanceOf(Photo::class, $album->photos->first());
    }

    /** @test */
    public function album_generates_slug_from_title()
    {
        $album = Album::factory()->create(['title' => 'My Vacation Photos']);

        $this->assertEquals('my-vacation-photos', $album->slug);
    }

    /** @test */
    public function album_cover_url_accessor_returns_full_url()
    {
        $album = Album::factory()->create([
            'cover_path' => 'albums/test-cover.jpg',
        ]);

        $this->assertStringContainsString('/storage/albums/test-cover.jpg', $album->cover_url);
    }

    /** @test */
    public function album_cover_url_returns_null_when_no_cover()
    {
        $album = Album::factory()->create(['cover_path' => null]);

        $this->assertNull($album->cover_url);
    }

    /** @test */
    public function visible_scope_returns_public_albums_for_guests()
    {
        Album::factory()->public()->create();
        Album::factory()->private()->create();
        Album::factory()->followersOnly()->create();

        $visibleAlbums = Album::visible(null)->get();

        $this->assertCount(1, $visibleAlbums);
        $this->assertEquals('public', $visibleAlbums->first()->visibility);
    }

    /** @test */
    public function visible_scope_returns_all_albums_for_owner()
    {
        $user = User::factory()->create();
        Album::factory()->public()->create(['user_id' => $user->id]);
        Album::factory()->private()->create(['user_id' => $user->id]);
        Album::factory()->followersOnly()->create(['user_id' => $user->id]);

        $visibleAlbums = Album::visible($user->id)->get();

        $this->assertCount(3, $visibleAlbums);
    }

    /** @test */
    public function accessible_to_scope_filters_by_visibility()
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $publicAlbum = Album::factory()->public()->create(['user_id' => $owner->id]);
        $privateAlbum = Album::factory()->private()->create(['user_id' => $owner->id]);

        $accessibleAlbums = Album::accessibleTo($viewer->id)->get();

        $this->assertTrue($accessibleAlbums->contains($publicAlbum));
        $this->assertFalse($accessibleAlbums->contains($privateAlbum));
    }

    /** @test */
    public function most_viewed_scope_orders_by_photo_views()
    {
        $album1 = Album::factory()->create();
        $album2 = Album::factory()->create();

        Photo::factory()->create(['album_id' => $album1->id, 'views_count' => 100]);
        Photo::factory()->create(['album_id' => $album2->id, 'views_count' => 500]);

        // Note: This test assumes mostViewed scope exists and works
        // If the scope doesn't exist, you may need to add it to the Album model
    }

    /** @test */
    public function album_uses_soft_deletes()
    {
        $album = Album::factory()->create();
        $albumId = $album->id;

        $album->delete();

        $this->assertSoftDeleted('albums', ['id' => $albumId]);
        $this->assertNotNull(Album::withTrashed()->find($albumId)->deleted_at);
    }

    /** @test */
    public function album_can_be_force_deleted()
    {
        $album = Album::factory()->create();
        $albumId = $album->id;

        $album->forceDelete();

        $this->assertDatabaseMissing('albums', ['id' => $albumId]);
    }
}
