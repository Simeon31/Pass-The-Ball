# Group Invitations & Join Requests - Implementation Guide

**Date:** October 20, 2025  
**Feature:** Admin invitations and member join request approval system

---

## 📋 Overview

A complete implementation of group invitation and join request features following Laravel + Inertia.js + Vue 3 patterns. This allows group admins to invite users and approve/reject join requests from users wanting to join their groups.

---

## 🎯 Features Implemented

### 1. **Admin Invitation System**
- ✅ Admins/Moderators can search and invite users to join groups
- ✅ Token-based invitations with 7-day expiration
- ✅ Email + in-app notifications for invited users
- ✅ Accept/Reject functionality for invited users
- ✅ Invitation tracking (pending, accepted, rejected, expired)
- ✅ Automatic invitation expiry handling

### 2. **Join Request System**
- ✅ Users can request to join groups (manual approval mode)
- ✅ Users can instantly join groups (auto-approval mode)
- ✅ Admins/Moderators receive notifications of new join requests
- ✅ Admins can approve or reject join requests
- ✅ Pending requests page for admins
- ✅ Visual indicators for pending requests count

### 3. **User Experience**
- ✅ Modal interface for inviting members with live user search
- ✅ Dedicated invitations page for users to manage their invitations
- ✅ Dedicated pending requests page for admins
- ✅ Real-time search for users (debounced, 300ms delay)
- ✅ Status badges and visual feedback
- ✅ Flash messages for all actions

---

## 🏗️ Architecture & Patterns

### Backend Implementation

#### **Services**
- `GroupInvitationService` - Centralized invitation logic
  - `createInvitation()` - Create or extend invitation
  - `acceptInvitation()` - Accept invitation and add to group
  - `rejectInvitation()` - Reject invitation
  - `getPendingInvitationsForUser()` - Get user's invitations
  - `getPendingInvitationsForGroup()` - Get group's pending invitations
  - `expireOldInvitations()` - Expire old invitations (can be scheduled)

#### **Controllers**
- `GroupController` - Added methods:
  - `inviteMember()` - Send invitation to user
  - `respondToInvitation()` - Accept/reject invitation
  - `invitations()` - Display user's invitations page
  - `join()` - Request to join group
  - `approveRequest()` - Approve/reject join request
  - `pendingRequests()` - Display pending requests page

- `ProfileController` - Added method:
  - `search()` - Search users for invitations (API endpoint)

#### **Form Requests**
- `InviteMemberRequest` - Validates invitation data
- `ApproveJoinRequestRequest` - Validates approval/rejection
- `JoinGroupRequest` - Validates join request

#### **Models**
- `GroupInvitation` - Invitation model with:
  - Scopes: `valid()`, `pending()`
  - Methods: `isValid()`, `isExpired()`, `markAsAccepted()`, `markAsRejected()`, `markAsExpired()`
  - Auto-generates 64-character token on creation

- `Group` - Extended with:
  - `pendingRequests()` - Get pending join requests
  - `invitations()` - Get group invitations

#### **Notifications**
- `GroupInvitationNotification` - Email + Database notification
- `GroupJoinRequestNotification` - Email + Database notification

#### **Resources**
- `GroupInvitationResource` - Transform invitation data
- `GroupMemberResource` - Transform member data with status
- `GroupResource` - Includes permissions and membership status

### Frontend Implementation

#### **Pages**
- `Groups/Invitations.vue` - User's group invitations page
  - Lists all pending invitations
  - Accept/Reject actions
  - Shows expiration status
  - Group preview with thumbnail and details
  
- `Groups/PendingRequests.vue` - Admin's pending requests page
  - Lists all pending join requests
  - Approve/Reject actions
  - User profile previews
  - Request timestamps

- `Groups/Show.vue` - Updated with:
  - Invite button (opens modal)
  - Pending requests badge/link
  - Join/Request to Join button

#### **Components**
- `InviteMembersModal.vue` - Invitation modal
  - Live user search (debounced 300ms)
  - User avatars and details
  - Invite button with success feedback
  - Excludes current user from search
  - Auto-dismisses "Invited" status after 3s

#### **Composables**
- `useFlashMessage` - Flash message handling (existing)

---

## 🔄 User Flows

### Flow 1: Admin Invites User

```
1. Admin clicks "Invite" button on group page
2. InviteMembersModal opens
3. Admin searches for user by name/username/email
4. Admin clicks "Invite" on selected user
5. Backend creates invitation with 7-day expiration token
6. GroupInvitationNotification sent (email + database)
7. User receives notification email with link
8. User clicks link in email → redirected to /groups/invitations
9. User reviews invitation and clicks "Accept" or "Decline"
10. If accepted: User added to group as member
11. User redirected to group page with success message
```

### Flow 2: User Requests to Join (Manual Approval)

```
1. User views group page (not a member)
2. Group has auto_approval = false
3. User clicks "Request to Join" button
4. Backend creates pending membership (status: 'pending')
5. GroupJoinRequestNotification sent to all admins
6. Admins see pending requests badge on group page
7. Admin clicks "Requests" button or badge
8. Admin navigates to /groups/{slug}/admin/requests
9. Admin clicks "Approve" or "Reject"
10. If approved: Membership status updated to 'approved'
11. User can now access group content
```

### Flow 3: User Joins Group (Auto-Approval)

```
1. User views group page (not a member)
2. Group has auto_approval = true
3. User clicks "Join Group" button
4. Backend immediately creates membership (status: 'approved')
5. User added to group as member
6. User can now access group content
```

---

## 🗄️ Database Schema

### `group_invitations` Table
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
group_id            BIGINT UNSIGNED (FK to groups)
user_id             BIGINT UNSIGNED (FK to users)
invited_by          BIGINT UNSIGNED (FK to users)
token               VARCHAR(64) UNIQUE
token_expires_at    TIMESTAMP
token_used_at       TIMESTAMP NULLABLE
status              ENUM('pending', 'accepted', 'rejected', 'expired')
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### `group_users` Table (Updated)
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
group_id            BIGINT UNSIGNED (FK to groups)
user_id             BIGINT UNSIGNED (FK to users)
status              ENUM('pending', 'approved', 'rejected')
role                ENUM('admin', 'moderator', 'member')
created_by          BIGINT UNSIGNED (FK to users)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

## 🛣️ Routes

```php
// Group Invitations (User-facing)
GET     /groups/invitations                         groups.invitations
GET     /groups/invitations/{token}/accept          groups.invitations.accept (email link)
POST    /groups/invitations/{token}/respond         groups.invitations.respond

// Group Membership (User actions)
POST    /groups/{group}/join                        groups.join
POST    /groups/{group}/leave                       groups.leave

// Admin: Invite Members
POST    /groups/{group}/invite                      groups.invite

// Admin: Manage Join Requests
GET     /groups/{group}/admin/requests              groups.admin.requests
POST    /groups/{group}/admin/approve               groups.admin.approve

// API: User Search
GET     /api/users/search?q={query}&limit={limit}   users.search
```

---

## 🔒 Authorization

### Policy Checks (GroupPolicy)
- `inviteMembers` - Admin or Moderator
- `approveRequests` - Admin or Moderator
- `view` - Public (anyone can view)
- `join` - Authenticated user

### Permission Service (GroupPermissionService)
```php
// Check if user can invite members
$canInvite = $permissionService->hasPermission($user, $group, GroupPermission::INVITE_MEMBERS);

// Check if user can approve requests
$canApprove = $permissionService->hasPermission($user, $group, GroupPermission::APPROVE_JOIN_REQUESTS);
```

---

## 💾 TypeScript Types

```typescript
export interface GroupInvitation {
    id: number;
    group?: Group;
    user?: User;
    inviter?: User;
    token?: string;
    status: 'pending' | 'accepted' | 'rejected' | 'expired';
    is_valid: boolean;
    is_expired: boolean;
    expires_at: string;
    created_at: string;
}

export interface GroupMember {
    id: number;
    user: User;
    role: GroupRole;
    status: 'pending' | 'approved' | 'rejected';
    joined_at: string;
}

export interface Group {
    // ... existing fields
    permissions?: GroupPermission[];
    is_member: boolean;
    is_owner: boolean;
    member_count?: number;
}
```

---

## 🧪 Testing Checklist

### Admin Invitation Flow
- [ ] Admin can search for users
- [ ] Admin can invite users
- [ ] Invited user receives email notification
- [ ] Invited user sees invitation in /groups/invitations
- [ ] User can accept invitation
- [ ] User can reject invitation
- [ ] Expired invitations show as expired
- [ ] Invitation token is secure and unique

### Join Request Flow (Manual Approval)
- [ ] User can request to join group
- [ ] Admin receives notification
- [ ] Admin sees pending requests count
- [ ] Admin can view pending requests page
- [ ] Admin can approve request
- [ ] Admin can reject request
- [ ] Approved user becomes member
- [ ] Rejected request is removed

### Join Flow (Auto-Approval)
- [ ] User can instantly join group
- [ ] User immediately becomes member
- [ ] User can access group content
- [ ] No admin approval needed

### Edge Cases
- [ ] User cannot invite themselves
- [ ] User cannot invite existing members
- [ ] User cannot accept expired invitations
- [ ] User cannot join group twice
- [ ] Owner cannot leave their own group
- [ ] Pending request shows correctly on group page

---

## 🎨 UI/UX Features

### Visual Indicators
- **Pending Badge** - Yellow/orange badge for pending status
- **Success State** - Green checkmark for accepted invitations
- **Expired State** - Red badge for expired invitations
- **Request Count** - Red badge showing pending requests count

### User Feedback
- **Flash Messages** - Success messages for all actions
- **Loading States** - Disabled buttons during API calls
- **Empty States** - Helpful messages when no data
- **Confirmation Dialogs** - Confirm destructive actions

### Responsive Design
- **Mobile-friendly** - All components work on mobile
- **Flexbox Layouts** - Adapts to different screen sizes
- **Touch-friendly** - Large tap targets for mobile

---

## 🔧 Configuration

### Invitation Expiry
Default: 7 days (configurable in `GroupInvitationService`)

```php
private const INVITATION_EXPIRY_DAYS = 7;
```

### Search Debounce
Default: 300ms (configurable in `InviteMembersModal.vue`)

```typescript
const searchUsers = debounce(async () => {
    // Search logic
}, 300);
```

### Search Limit
Default: 10 users (configurable in API request)

```typescript
const response = await fetch(`/api/users/search?q=${query}&limit=10`);
```

---

## 📚 Code Examples

### Backend: Send Invitation
```php
// In GroupController@inviteMember
$invitation = $this->invitationService->createInvitation(
    $group,
    $user,
    auth()->user()
);

$user->notify(new GroupInvitationNotification($invitation));
```

### Backend: Approve Join Request
```php
// In GroupController@approveRequest
$group->members()->updateExistingPivot($userId, [
    'status' => 'approved',
    'role' => GroupRole::MEMBER->value,
]);
```

### Frontend: Open Invite Modal
```vue
<Button v-if="canInvite" variant="outline" @click="openInviteModal">
    <UserPlus class="mr-2 h-4 w-4" />
    Invite
</Button>

<InviteMembersModal v-model:isOpen="showInviteModal" :group="group" />
```

### Frontend: Accept Invitation
```vue
const handleAccept = (token: string) => {
    router.post(
        `/groups/invitations/${token}/respond`,
        { action: 'accept' }
    );
};
```

---

## 🚀 Future Enhancements

### Possible Improvements
1. **Batch Invitations** - Invite multiple users at once
2. **Invitation Templates** - Custom invitation messages
3. **Invitation Links** - Shareable invitation URLs
4. **Role Selection** - Invite as moderator or admin
5. **Invitation History** - View sent invitations
6. **Notification Preferences** - Customize notification settings
7. **Auto-Expire Job** - Scheduled task to expire old invitations
8. **Rate Limiting** - Prevent invitation spam
9. **Group Discovery** - Suggest groups to users
10. **Member Recommendations** - Suggest users to invite

---

## 📝 Notes

### DRY Principles Applied
- **Service Layer** - Centralized business logic in `GroupInvitationService`
- **Form Requests** - Reusable validation rules
- **Resources** - Consistent API transformations
- **Composables** - Reusable flash message handling
- **Components** - Modular UI components (modal, cards, badges)

### Best Practices
- **Type Safety** - Full TypeScript interfaces
- **Authorization** - Policy-based access control
- **Validation** - Form request validation
- **Error Handling** - User-friendly error messages
- **Performance** - Debounced search, eager loading
- **Security** - Secure tokens, CSRF protection
- **UX** - Loading states, confirmations, feedback

---

## 🐛 Known Issues

None at the time of implementation.

---

## 📞 Support

For questions or issues, refer to the main `GROUPS_FEATURE_GUIDE.md` documentation or check the existing implementation patterns in the codebase.
