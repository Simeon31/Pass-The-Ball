<?php

namespace App\Policies;

use App\Enums\GroupPermission;
use App\Models\Post;
use App\Models\User;
use App\Services\GroupPermissionService;

class PostPolicy
{
    protected GroupPermissionService $permissionService;

    public function __construct(GroupPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Only the post author can update their post
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Post author can always delete their own post
        if ($user->id === $post->user_id) {
            return true;
        }

        // If post is in a group, only group admins (not moderators) can delete posts
        if ($post->group_id) {
            $group = $post->group;

            // Check if user is group owner
            if ($group->isOwner($user)) {
                return true;
            }

            // Check if user is admin (not just moderator)
            $userRole = $group->getUserRole($user);
            return $userRole === 'admin';
        }

        return false;
    }
}
