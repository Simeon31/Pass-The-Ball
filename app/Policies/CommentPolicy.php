<?php

namespace App\Policies;

use App\Enums\GroupPermission;
use App\Models\Comment;
use App\Models\User;
use App\Services\GroupPermissionService;

class CommentPolicy
{
    protected GroupPermissionService $permissionService;

    public function __construct(GroupPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Only the comment author can update their comment
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Comment author can always delete their own comment
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Post author can delete comments on their post
        if ($user->id === $comment->post->user_id) {
            return true;
        }

        // If post is in a group, only group admins (not moderators) can delete comments
        if ($comment->post->group_id) {
            $group = $comment->post->group;

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
