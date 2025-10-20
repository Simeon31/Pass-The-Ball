<?php

namespace App\Services;

use App\Enums\GroupPermission;
use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;

/**
 * Service for managing group permissions
 */
class GroupPermissionService
{
    /**
     * Check if a user has a specific permission in a group
     */
    public function hasPermission(User $user, Group $group, GroupPermission $permission): bool
    {
        // Group owner always has all permissions
        if ($group->isOwner($user)) {
            return true;
        }

        // Get user's role in the group
        $role = $group->getUserRole($user);

        if (!$role) {
            return false;
        }

        // Convert string to GroupRole enum
        $roleEnum = GroupRole::from($role);

        // Get permissions for this role
        $rolePermissions = GroupPermission::forRole($roleEnum);

        return in_array($permission, $rolePermissions);
    }

    /**
     * Check if a user can post in the group
     */
    public function canPost(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::POST_IN_GROUP);
    }

    /**
     * Check if a user can invite members to the group
     */
    public function canInviteMembers(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::INVITE_MEMBERS);
    }

    /**
     * Check if a user can edit group settings
     */
    public function canEditSettings(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::EDIT_GROUP_SETTINGS);
    }

    /**
     * Check if a user can edit group images
     */
    public function canEditImages(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::EDIT_GROUP_IMAGES);
    }

    /**
     * Check if a user can approve join requests
     */
    public function canApproveRequests(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::APPROVE_JOIN_REQUESTS);
    }

    /**
     * Check if a user can remove members
     */
    public function canRemoveMembers(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::REMOVE_MEMBERS);
    }

    /**
     * Check if a user can moderate posts
     */
    public function canModeratePosts(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::MODERATE_POSTS);
    }

    /**
     * Check if a user can delete the group
     */
    public function canDeleteGroup(User $user, Group $group): bool
    {
        return $this->hasPermission($user, $group, GroupPermission::DELETE_GROUP);
    }

    /**
     * Get all permissions for a user in a group
     */
    public function getUserPermissions(User $user, Group $group): array
    {
        if ($group->isOwner($user)) {
            return GroupPermission::forRole(GroupRole::ADMIN);
        }

        $role = $group->getUserRole($user);

        if (!$role) {
            return [];
        }

        $roleEnum = GroupRole::from($role);
        return GroupPermission::forRole($roleEnum);
    }

    /**
     * Check if user can change another user's role
     */
    public function canChangeRole(User $user, Group $group, User $targetUser, GroupRole $newRole): bool
    {
        // Only owner or admins can change roles
        if (!$this->hasPermission($user, $group, GroupPermission::EDIT_GROUP_SETTINGS)) {
            return false;
        }

        // Can't change owner's role
        if ($group->isOwner($targetUser)) {
            return false;
        }

        // Only owner can assign admin role
        if ($newRole === GroupRole::ADMIN && !$group->isOwner($user)) {
            return false;
        }

        return true;
    }
}
