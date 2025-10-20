<?php

namespace App\Policies;

use App\Enums\GroupPermission;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupPermissionService;
use Illuminate\Auth\Access\Response;

class GroupPolicy
{
    protected GroupPermissionService $permissionService;

    public function __construct(GroupPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Determine whether the user can view any groups.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the group.
     * Members and non-members can view (non-members see limited info)
     */
    public function view(?User $user, Group $group): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create groups.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::EDIT_GROUP_SETTINGS
        );
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::DELETE_GROUP
        );
    }

    /**
     * Determine whether the user can restore the group.
     */
    public function restore(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the group.
     */
    public function forceDelete(User $user, Group $group): bool
    {
        return $group->isOwner($user);
    }

    /**
     * Determine whether the user can update group images.
     */
    public function updateImages(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::EDIT_GROUP_IMAGES
        );
    }

    /**
     * Determine whether the user can invite members.
     */
    public function inviteMembers(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::INVITE_MEMBERS
        );
    }

    /**
     * Determine whether the user can approve join requests.
     */
    public function approveRequests(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::APPROVE_JOIN_REQUESTS
        );
    }

    /**
     * Determine whether the user can post in the group.
     */
    public function post(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::POST_IN_GROUP
        );
    }

    /**
     * Determine whether the user can remove members.
     */
    public function removeMembers(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::REMOVE_MEMBERS
        );
    }

    /**
     * Determine whether the user can moderate posts.
     */
    public function moderatePosts(User $user, Group $group): bool
    {
        return $this->permissionService->hasPermission(
            $user,
            $group,
            GroupPermission::MODERATE_POSTS
        );
    }
}
