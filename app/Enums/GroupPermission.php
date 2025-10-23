<?php

namespace App\Enums;

enum GroupPermission: string
{
    case POST_IN_GROUP = 'post_in_group';
    case INVITE_MEMBERS = 'invite_members';
    case EDIT_GROUP_SETTINGS = 'edit_group_settings';
    case EDIT_GROUP_IMAGES = 'edit_group_images';
    case APPROVE_JOIN_REQUESTS = 'approve_join_requests';
    case REMOVE_MEMBERS = 'remove_members';
    case MODERATE_POSTS = 'moderate_posts';
    case DELETE_GROUP = 'delete_group';
    case CHANGE_MEMBER_ROLES = 'change_member_roles';

    /**
     * Get permission label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::POST_IN_GROUP => 'Post in Group',
            self::INVITE_MEMBERS => 'Invite Members',
            self::EDIT_GROUP_SETTINGS => 'Edit Group Settings',
            self::EDIT_GROUP_IMAGES => 'Edit Group Images',
            self::APPROVE_JOIN_REQUESTS => 'Approve Join Requests',
            self::REMOVE_MEMBERS => 'Remove Members',
            self::MODERATE_POSTS => 'Moderate Posts',
            self::DELETE_GROUP => 'Delete Group',
            self::CHANGE_MEMBER_ROLES => 'Change Member Roles',
        };
    }

    /**
     * Get permissions for a specific role
     */
    public static function forRole(GroupRole $role): array
    {
        return match ($role) {
            GroupRole::ADMIN => [
                self::POST_IN_GROUP,
                self::INVITE_MEMBERS,
                self::EDIT_GROUP_SETTINGS,
                self::EDIT_GROUP_IMAGES,
                self::APPROVE_JOIN_REQUESTS,
                self::REMOVE_MEMBERS,
                self::MODERATE_POSTS,
                self::DELETE_GROUP,
                self::CHANGE_MEMBER_ROLES,
            ],
            GroupRole::MODERATOR => [
                self::POST_IN_GROUP,
                self::INVITE_MEMBERS,
                self::APPROVE_JOIN_REQUESTS,
                self::MODERATE_POSTS,
            ],
            GroupRole::MEMBER => [
                self::POST_IN_GROUP,
            ],
        };
    }
}
