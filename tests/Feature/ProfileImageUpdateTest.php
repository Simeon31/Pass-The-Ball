<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function user_can_update_avatar()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.show', ['username' => $user->username]));
        $response->assertSessionHas('status', 'Profile images updated successfully.');

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        $this->assertStringContainsString('/storage/users/' . $user->id . '/', $user->profile_picture_path);

        // Check that file exists in storage
        $path = str_replace('/storage/', '', $user->profile_picture_path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function user_can_update_cover()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $file,
        ]);

        $response->assertRedirect(route('profile.show', ['username' => $user->username]));
        $response->assertSessionHas('status', 'Profile images updated successfully.');

        $user->refresh();
        $this->assertNotNull($user->cover_path);
        $this->assertStringContainsString('/storage/user/' . $user->id . '/', $user->cover_path);

        // Check that file exists in storage
        $path = str_replace('/storage/', '', $user->cover_path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function user_can_update_both_avatar_and_cover()
    {
        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->image('avatar.jpg', 400, 400);
        $cover = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $avatar,
            'cover' => $cover,
        ]);

        $response->assertRedirect(route('profile.show', ['username' => $user->username]));

        $user->refresh();
        $this->assertNotNull($user->profile_picture_path);
        $this->assertNotNull($user->cover_path);

        // Check that both files exist in storage
        $avatarPath = str_replace('/storage/', '', $user->profile_picture_path);
        $coverPath = str_replace('/storage/', '', $user->cover_path);

        Storage::disk('public')->assertExists($avatarPath);
        Storage::disk('public')->assertExists($coverPath);
    }

    /** @test */
    public function old_avatar_is_deleted_when_uploading_new_one()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload first avatar
        $firstAvatar = UploadedFile::fake()->image('first-avatar.jpg', 400, 400);
        $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $firstAvatar,
        ]);

        $user->refresh();
        $firstPath = str_replace('/storage/', '', $user->profile_picture_path);
        Storage::disk('public')->assertExists($firstPath);

        // Upload second avatar
        $secondAvatar = UploadedFile::fake()->image('second-avatar.jpg', 400, 400);
        $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $secondAvatar,
        ]);

        $user->refresh();
        $secondPath = str_replace('/storage/', '', $user->profile_picture_path);

        // Old file should be deleted
        Storage::disk('public')->assertMissing($firstPath);
        // New file should exist
        Storage::disk('public')->assertExists($secondPath);
    }

    /** @test */
    public function old_cover_is_deleted_when_uploading_new_one()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload first cover
        $firstCover = UploadedFile::fake()->image('first-cover.jpg', 1200, 400);
        $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $firstCover,
        ]);

        $user->refresh();
        $firstPath = str_replace('/storage/', '', $user->cover_path);
        Storage::disk('public')->assertExists($firstPath);

        // Upload second cover
        $secondCover = UploadedFile::fake()->image('second-cover.jpg', 1200, 400);
        $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $secondCover,
        ]);

        $user->refresh();
        $secondPath = str_replace('/storage/', '', $user->cover_path);

        // Old file should be deleted
        Storage::disk('public')->assertMissing($firstPath);
        // New file should exist
        Storage::disk('public')->assertExists($secondPath);
    }

    /** @test */
    public function avatar_must_be_an_image()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    /** @test */
    public function cover_must_be_an_image()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $file,
        ]);

        $response->assertSessionHasErrors('cover');
    }

    /** @test */
    public function avatar_cannot_exceed_max_size()
    {
        $user = User::factory()->create();
        // Create a file larger than 2MB
        $file = UploadedFile::fake()->create('avatar.jpg', 3000);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    /** @test */
    public function cover_cannot_exceed_max_size()
    {
        $user = User::factory()->create();
        // Create a file larger than 2MB
        $file = UploadedFile::fake()->create('cover.jpg', 3000);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $file,
        ]);

        $response->assertSessionHasErrors('cover');
    }

    /** @test */
    public function avatar_must_be_valid_image_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.bmp', 100);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    /** @test */
    public function cover_must_be_valid_image_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('cover.bmp', 100);

        $response = $this->actingAs($user)->post(route('profile.updateImages'), [
            'cover' => $file,
        ]);

        $response->assertSessionHasErrors('cover');
    }

    /** @test */
    public function guest_cannot_update_profile_images()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_cannot_update_another_users_images()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($anotherUser)->post(route('profile.updateImages'), [
            'avatar' => $file,
        ]);

        // Should update the authenticated user, not the route parameter user
        $user->refresh();
        $anotherUser->refresh();

        $this->assertNull($user->profile_picture_path);
        $this->assertNotNull($anotherUser->profile_picture_path);
    }
}
