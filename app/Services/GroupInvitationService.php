<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Service for managing group invitations
 */
class GroupInvitationService
{
    /**
     * Default invitation expiration in days
     */
    private const INVITATION_EXPIRY_DAYS = 7;

    /**
     * Create an invitation for a user to join a group
     */
    public function createInvitation(
        Group $group,
        User $user,
        User $inviter,
        int $expiryDays = self::INVITATION_EXPIRY_DAYS
    ): GroupInvitation {
        // Check if invitation already exists and is pending
        $existing = GroupInvitation::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            // Extend expiration if invitation exists
            $existing->update([
                'token_expires_at' => now()->addDays($expiryDays),
                'invited_by' => $inviter->id,
            ]);
            return $existing;
        }

        // Create new invitation
        return GroupInvitation::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'invited_by' => $inviter->id,
            'token' => Str::random(64),
            'token_expires_at' => now()->addDays($expiryDays),
            'status' => 'pending',
        ]);
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(GroupInvitation $invitation, GroupRole $role = GroupRole::MEMBER): bool
    {
        if (!$invitation->isValid()) {
            return false;
        }

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        // Add user to group
        $invitation->group->members()->attach($invitation->user_id, [
            'status' => 'approved',
            'role' => $role->value,
            'created_by' => $invitation->invited_by,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject an invitation
     */
    public function rejectInvitation(GroupInvitation $invitation): bool
    {
        if (!$invitation->isValid()) {
            return false;
        }

        $invitation->markAsRejected();
        return true;
    }

    /**
     * Find invitation by token
     */
    public function findByToken(string $token): ?GroupInvitation
    {
        return GroupInvitation::where('token', $token)->first();
    }

    /**
     * Expire old invitations
     */
    public function expireOldInvitations(): int
    {
        $expiredInvitations = GroupInvitation::where('status', 'pending')
            ->where('token_expires_at', '<', now())
            ->get();

        foreach ($expiredInvitations as $invitation) {
            $invitation->markAsExpired();
        }

        return $expiredInvitations->count();
    }

    /**
     * Get pending invitations for a user
     */
    public function getPendingInvitationsForUser(User $user)
    {
        return GroupInvitation::where('user_id', $user->id)
            ->valid()
            ->with(['group', 'inviter'])
            ->get();
    }

    /**
     * Get pending invitations for a group
     */
    public function getPendingInvitationsForGroup(Group $group)
    {
        return GroupInvitation::where('group_id', $group->id)
            ->valid()
            ->with(['user', 'inviter'])
            ->get();
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(GroupInvitation $invitation): bool
    {
        if ($invitation->status !== 'pending') {
            return false;
        }

        $invitation->update(['status' => 'expired']);
        return true;
    }
}
