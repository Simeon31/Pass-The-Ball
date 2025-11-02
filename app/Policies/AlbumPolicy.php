<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    /**
     * Determine whether the user can view the album.
     */
    public function view(?User $user, Album $album): bool
    {
        // Public albums are visible to everyone
        if ($album->visibility === 'public') {
            return true;
        }

        // Guests cannot view non-public albums
        if (!$user) {
            return false;
        }

        // Owner can always view their own albums
        if ($user->id === $album->user_id) {
            return true;
        }

        // Check visibility rules for authenticated users
        return match ($album->visibility) {
            'private' => false, // Only owner can view private albums
            'followers_only' => $this->isFollowing($user, $album->user_id), // Followers only
            'link_only' => true, // Anyone with the link can view (handled separately)
            default => false,
        };
    }

    /**
     * Check if a user is following another user.
     * TODO: Implement proper followers system integration when available.
     */
    protected function isFollowing(User $user, int $userId): bool
    {
        // Temporary: Allow all authenticated users for followers_only albums
        // Replace with actual followers check when implemented
        return true;
    }

    /**
     * Determine whether the user can create albums.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create albums
        return true;
    }

    /**
     * Determine whether the user can update the album.
     */
    public function update(User $user, Album $album): bool
    {
        // Only the album owner can update their album
        return $user->id === $album->user_id;
    }

    /**
     * Determine whether the user can delete the album.
     */
    public function delete(User $user, Album $album): bool
    {
        // Only the album owner can delete their album
        return $user->id === $album->user_id;
    }

    /**
     * Determine whether the user can restore the album.
     */
    public function restore(User $user, Album $album): bool
    {
        // Only the album owner can restore their album
        return $user->id === $album->user_id;
    }

    /**
     * Determine whether the user can permanently delete the album.
     */
    public function forceDelete(User $user, Album $album): bool
    {
        // Only the album owner can force delete their album
        return $user->id === $album->user_id;
    }

    /**
     * Determine whether the user can download photos from the album.
     */
    public function download(?User $user, Album $album): bool
    {
        // Use same visibility rules as viewing
        return $this->view($user, $album);
    }
}
