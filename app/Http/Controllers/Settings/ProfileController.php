<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */

    public function index(User $user)
    {
        return Inertia::render('settings/View', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'user' => new UserResource($user),
        ]);
    }
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Update the user's cover and avatar images.
     */

    public function updateImage(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'], // Max size 2MB
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'], // Max size 2MB
        ]);

        $avatar = $data['avatar'] ?? null;
        $cover = $data['cover'] ?? null;

        // Using the authenticated user as the resource to update
        $u = $request->user();

        // Handling cover upload
        if ($request->hasFile('cover')) {
            // Delete old cover if present
            if ($u->cover_path) {
                $old = $u->cover_path;
                // Strip leading '/storage/' if present
                if (str_starts_with($old, '/storage/')) {
                    $old = substr($old, strlen('/storage/'));
                }
                Storage::disk('public')->delete($old);
            }

            // Processing and optimizinng cover image
            $file = $request->file('cover');
            $timestamp = time();
            $filename = 'cover_' . $timestamp . '_' . uniqid() . '.jpg';
            // Storing in user-specific directory: users/{user_id}/cover_timestamp_uniqid.jpg
            $path = 'user/' . $u->id . '/' . $filename;

            // Resizing and optimizing cover
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->cover(1200, 400);

            // Save to storage
            Storage::disk('public')->put($path, $image->toJpeg(85));
            $u->cover_path = '/storage/' . $path;
        }

        // Handling avatar/profile picture upload
        if ($request->hasFile('avatar')) {
            // Deleting old avatar if present
            if ($u->profile_picture_path) {
                $old = $u->profile_picture_path;
                if (str_starts_with($old, '/storage/')) {
                    $old = substr($old, strlen('/storage/'));
                }
                Storage::disk('public')->delete($old);
            }

            // Process and optimize avatar image
            $file = $request->file('avatar');
            $timestamp = time();
            $filename = 'avatar_' . $timestamp . '_' . uniqid() . '.jpg';
            // Storing in user-specific directory: users/{user_id}/avatar_timestamp_uniqid.jpg
            $path = 'users/' . $u->id . '/' . $filename;

            // Resizing and optimizing avatar
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->cover(300, 300);

            // Save to storage
            Storage::disk('public')->put($path, $image->toJpeg(85));

            $u->profile_picture_path = '/storage/' . $path;
        }

        $u->save();

        // Redirecting back to the profile page with a success flash message
        return redirect()->route('profile', ['user' => $u->username])
            ->with('status', 'Profile image updated successfully.');
    }
}
