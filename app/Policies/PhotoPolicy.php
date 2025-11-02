<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    /**
     * Determine whether the user can view the photo.
     */
    public function view(?User $user, Photo $photo): bool
    {
        // Check album visibility
        $album = $photo->album;

        // Public albums are visible to everyone
        if ($album->visibility === 'public') {
            return true;
        }

        // Guests cannot view photos in non-public albums
        if (!$user) {
            return false;
        }

        // Owner can always view their own photos
        if ($user->id === $photo->user_id) {
            return true;
        }

        // Check album visibility rules for authenticated users
        return match ($album->visibility) {
            'private' => false, // Only owner can view private album photos
            'followers_only' => $this->isFollowing($user, $photo->user_id), // Followers only
            'link_only' => true, // Anyone with the link can view
            default => false,
        };
    }

    /**
     * Check if a user is following another user.
     * TODO: Implement proper followers system integration when available.
     */
    protected function isFollowing(User $user, int $userId): bool
    {
        // Temporary: Allow all authenticated users for followers_only photos
        // Replace with actual followers check when implemented
        return true;
    }

    /**
     * Determine whether the user can create photos.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create photos (in their own albums)
        return true;
    }

    /**
     * Determine whether the user can update the photo.
     */
    public function update(User $user, Photo $photo): bool
    {
        // Only the photo owner can update their photo
        return $user->id === $photo->user_id;
    }

    /**
     * Determine whether the user can delete the photo.
     */
    public function delete(User $user, Photo $photo): bool
    {
        // Only the photo owner can delete their photo
        return $user->id === $photo->user_id;
    }

    /**
     * Determine whether the user can restore the photo.
     */
    public function restore(User $user, Photo $photo): bool
    {
        // Only the photo owner can restore their photo
        return $user->id === $photo->user_id;
    }

    /**
     * Determine whether the user can permanently delete the photo.
     */
    public function forceDelete(User $user, Photo $photo): bool
    {
        // Only the photo owner can force delete their photo
        return $user->id === $photo->user_id;
    }

    /**
     * Determine whether the user can download the photo.
     */
    public function download(?User $user, Photo $photo): bool
    {
        // Use same visibility rules as viewing
        return $this->view($user, $photo);
    }
}
