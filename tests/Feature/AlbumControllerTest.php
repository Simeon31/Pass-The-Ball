<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlbumControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function user_can_view_their_gallery_index()
    {
        $user = User::factory()->create();
        Album::factory(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('gallery.index', ['user' => $user]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Gallery/Index')
                ->has('albums.data', 3)
        );
    }

    /** @test */
    public function guest_can_view_public_albums()
    {
        $user = User::factory()->create();
        Album::factory()->public()->create(['user_id' => $user->id]);
        Album::factory()->private()->create(['user_id' => $user->id]);

        $response = $this->get(route('gallery.index', ['user' => $user]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->has('albums.data', 1) // Only public album
        );
    }

    /** @test */
    public function user_can_create_album_without_cover()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'title' => 'My Vacation Photos',
            'description' => 'Photos from my summer vacation',
            'visibility' => 'public',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Album created successfully.');

        $this->assertDatabaseHas('albums', [
            'user_id' => $user->id,
            'title' => 'My Vacation Photos',
            'slug' => 'my-vacation-photos',
            'visibility' => 'public',
        ]);
    }

    /** @test */
    public function user_can_create_album_with_cover()
    {
        $user = User::factory()->create();
        $cover = UploadedFile::fake()->image('cover.jpg', 1920, 1080);

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'title' => 'My Vacation Photos',
            'description' => 'Photos from my summer vacation',
            'visibility' => 'public',
            'cover' => $cover,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Album created successfully.');

        $album = Album::where('user_id', $user->id)->first();
        $this->assertNotNull($album->cover_path);

        // Check that cover file exists in storage
        $this->assertTrue(Storage::disk('public')->exists($album->cover_path));
    }

    /** @test */
    public function album_title_is_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'description' => 'Some description',
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    /** @test */
    public function album_visibility_must_be_valid()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'title' => 'Test Album',
            'visibility' => 'invalid_visibility',
        ]);

        $response->assertSessionHasErrors(['visibility']);
    }

    /** @test */
    public function cover_image_must_be_valid_image()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'title' => 'Test Album',
            'visibility' => 'public',
            'cover' => UploadedFile::fake()->create('document.pdf'),
        ]);

        $response->assertSessionHasErrors(['cover']);
    }

    /** @test */
    public function cover_image_must_not_exceed_max_size()
    {
        $user = User::factory()->create();
        $largeCover = UploadedFile::fake()->image('cover.jpg')->size(6000); // 6MB

        $response = $this->actingAs($user)->post(route('gallery.store'), [
            'title' => 'Test Album',
            'visibility' => 'public',
            'cover' => $largeCover,
        ]);

        $response->assertSessionHasErrors(['cover']);
    }

    /** @test */
    public function user_can_view_album_with_photos()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('gallery.show', [
            'user' => $user,
            'album' => $album->slug,
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Gallery/Show')
                ->has('album')
                ->has('photos.data')
        );
    }

    /** @test */
    public function user_can_update_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('gallery.update', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'title' => 'Updated Album Title',
            'description' => 'Updated description',
            'visibility' => 'private',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Album updated successfully.');

        $album->refresh();
        $this->assertEquals('Updated Album Title', $album->title);
        $this->assertEquals('private', $album->visibility);
    }

    /** @test */
    public function user_can_update_album_cover()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);
        $newCover = UploadedFile::fake()->image('new-cover.jpg', 1920, 1080);

        $response = $this->actingAs($user)->put(route('gallery.update', [
            'user' => $user,
            'album' => $album->slug,
        ]), [
            'title' => $album->title,
            'visibility' => $album->visibility,
            'cover' => $newCover,
        ]);

        $response->assertRedirect();

        $album->refresh();
        $this->assertNotNull($album->cover_path);
        $this->assertTrue(Storage::disk('public')->exists($album->cover_path));
    }

    /** @test */
    public function user_cannot_update_another_users_album()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('gallery.update', [
            'user' => $otherUser,
            'album' => $album->slug,
        ]), [
            'title' => 'Hacked Title',
            'visibility' => 'public',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_their_album()
    {
        $user = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('gallery.destroy', [
            'user' => $user,
            'album' => $album->slug,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Album deleted successfully.');

        $this->assertSoftDeleted('albums', ['id' => $album->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_album()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = Album::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('gallery.destroy', [
            'user' => $otherUser,
            'album' => $album->slug,
        ]));

        $response->assertForbidden();
        $this->assertDatabaseHas('albums', ['id' => $album->id, 'deleted_at' => null]);
    }

    /** @test */
    public function guest_cannot_create_album()
    {
        $response = $this->post(route('gallery.store'), [
            'title' => 'Test Album',
            'visibility' => 'public',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function albums_are_paginated()
    {
        $user = User::factory()->create();
        Album::factory(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('gallery.index', ['user' => $user]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->has('albums.data', 12) // Default pagination
                ->has('albums.links')
        );
    }

    /** @test */
    public function user_can_search_albums_by_title()
    {
        $user = User::factory()->create();
        Album::factory()->create([
            'user_id' => $user->id,
            'title' => 'Vacation Photos',
        ]);
        Album::factory()->create([
            'user_id' => $user->id,
            'title' => 'Work Events',
        ]);

        $response = $this->actingAs($user)->get(route('gallery.index', [
            'user' => $user,
            'search' => 'Vacation',
        ]));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->has('albums.data', 1)
        );
    }
}
