# Groups Feature - Complete Implementation Guide

**Date:** October 18, 2025  
**Feature:** Social Groups with Full Member Management

---

## 📋 Overview

A comprehensive social groups system with role-based permissions, invitation system, auto-approval settings, and complete member management. Groups allow users to create communities, share posts, and manage members.

---

## 🎯 Features Implemented

### 1. **Group Creation & Management**
- ✅ Create groups with name, about section, and auto-approval settings
- ✅ Upload cover images (1200x400px) and thumbnails (300x300px)
- ✅ Edit group settings (name, about, auto-approval)
- ✅ Soft delete groups (admin only)
- ✅ Automatic slug generation for SEO-friendly URLs

### 2. **Member Roles & Permissions**
Three hierarchical roles with specific permissions:

#### **Admin**
- Post in group
- Invite members
- Edit group settings
- Edit group images
- Approve join requests
- Remove members
- Moderate posts
- Delete group

#### **Moderator**
- Post in group
- Invite members
- Approve join requests
- Moderate posts

#### **Member**
- Post in group

### 3. **Join Mechanisms**

#### **Auto-Approval Enabled**
- User clicks "Join" → Immediately becomes a member
- No admin intervention required

#### **Manual Approval** 
- User clicks "Request to Join" → Status: `pending`
- Admins/Moderators receive notifications (email + in-app)
- Admins can approve or reject requests

### 4. **Invitation System**
- Admins/Moderators can invite users via email
- Token-based invitations with 7-day expiration
- Email + in-app notifications
- Accept/Reject functionality
- Invitation tracking (pending, accepted, rejected, expired)

### 5. **Group Profile Page**
- Cover image with upload functionality
- Thumbnail/avatar with upload functionality
- Member count and role badges
- Tabbed interface:
  - **Posts**: Group feed (members only)
  - **About**: Group description
  - **Members**: Member list with roles
  - **Requests**: Pending join requests (admins only)

### 6. **Discovery & Search**
- Browse all groups
- Search by name
- Filter: "All Groups" or "My Groups"
- Grid layout with group cards showing:
  - Cover image
  - Thumbnail
  - Name and description
  - Member count
  - Membership status

---

## 🗄️ Database Structure

### Tables

#### `groups`
```sql
id, name, slug, cover_path, thumbnail_path, auto_approval, about, 
user_id (owner), deleted_at, deleted_by, created_at, updated_at
```

#### `group_users` (Pivot)
```sql
id, status (pending/approved/rejected), role (admin/moderator/member), 
token, token_expired_at, token_used, user_id, group_id, created_by, created_at
```

#### `group_invitations`
```sql
id, group_id, user_id, invited_by, token (64 chars), token_expires_at, 
token_used_at, status (pending/accepted/rejected/expired), created_at, updated_at
```

---

## 🏗️ Architecture

### Backend Components

#### **Models**
- `Group` - Main group model with relationships
- `GroupUser` - Pivot model for membership
- `GroupInvitation` - Invitation tracking
- `User` - Extended with group relationships
- `Post` - Extended with group_id

#### **Enums**
- `GroupRole` - admin, moderator, member (with hierarchy)
- `GroupPermission` - 8 permissions with role mapping

#### **Services**
- `GroupPermissionService` - Centralized permission checks
- `GroupInvitationService` - Invitation creation, acceptance, rejection

#### **Policies**
- `GroupPolicy` - Authorization for all group actions

#### **Form Requests**
- `StoreGroupRequest` - Validation for group creation
- `UpdateGroupRequest` - Validation for group updates
- `UpdateGroupImagesRequest` - Image upload validation
- `InviteMemberRequest` - Invitation validation
- `JoinGroupRequest` - Join request validation
- `ApproveJoinRequestRequest` - Approval/rejection validation

#### **Resources**
- `GroupResource` - API transformation with permissions
- `GroupMemberResource` - Member data with roles
- `GroupInvitationResource` - Invitation data

#### **Notifications**
- `GroupInvitationNotification` - Email + Database
- `GroupJoinRequestNotification` - Email + Database

#### **Controller**
`GroupController` with actions:
- `index` - List/discover groups
- `create` - Show create form
- `store` - Create group
- `show` - Group profile page
- `edit` - Edit form
- `update` - Update group
- `destroy` - Delete group
- `updateImages` - Upload cover/thumbnail
- `members` - List all members
- `inviteMember` - Send invitation
- `respondToInvitation` - Accept/reject invitation
- `join` - Join or request to join
- `approveRequest` - Approve/reject join request
- `pendingRequests` - Admin panel for requests
- `leave` - Leave group

### Frontend Components

#### **Pages**
- `Groups/Discover.vue` - Browse and search groups
- `Groups/Create.vue` - Create new group
- `Groups/Show.vue` - Group profile page
- `Groups/Edit.vue` - Edit group settings

#### **Updated Components**
- `CreatePost.vue` - Added `groupId` prop for group posts
- `CreatePostModal.vue` - Added `group_id` to form
- `AppSidebar.vue` - Added "Groups" navigation link

---

## 🔒 Security & Authorization

### Policy Checks
All sensitive actions are protected via `GroupPolicy`:
- View group: Public (anyone can view)
- Create group: Authenticated users
- Update settings: Admin only
- Update images: Admin/Moderator
- Delete group: Admin only
- Invite members: Admin/Moderator
- Approve requests: Admin/Moderator
- Post in group: All members

### Permission Service
Centralized permission checks in `GroupPermissionService`:
```php
$permissionService->hasPermission($user, $group, GroupPermission::POST_IN_GROUP);
```

---

## 🚀 Usage Examples

### Creating a Group (Frontend)
```vue
<script setup>
const form = useForm({
    name: 'Laravel Developers',
    about: 'A community for Laravel enthusiasts',
    auto_approval: true,
});

form.post('/groups');
</script>
```

### Checking Permissions (Backend)
```php
use App\Services\GroupPermissionService;
use App\Enums\GroupPermission;

$permissionService = app(GroupPermissionService::class);

if ($permissionService->hasPermission($user, $group, GroupPermission::INVITE_MEMBERS)) {
    // User can invite members
}
```

### Inviting a User
```php
use App\Services\GroupInvitationService;

$invitationService = app(GroupInvitationService::class);

$invitation = $invitationService->createInvitation(
    $group,
    $userToInvite,
    $inviter,
    expiryDays: 7
);

$userToInvite->notify(new GroupInvitationNotification($invitation));
```

---

## 📝 API Routes

```php
GET    /groups                          - List groups (discovery)
GET    /groups/create                   - Create form
POST   /groups                          - Store new group
GET    /groups/{slug}                   - Group profile
GET    /groups/{slug}/edit              - Edit form
PUT    /groups/{slug}                   - Update group
DELETE /groups/{slug}                   - Delete group
POST   /groups/{slug}/images            - Upload images
GET    /groups/{slug}/members           - List members
POST   /groups/{slug}/invite            - Invite member
POST   /groups/{slug}/join              - Join/request
POST   /groups/{slug}/leave             - Leave group
POST   /groups/invitations/{token}/respond - Accept/reject invitation
GET    /groups/{slug}/admin/requests    - Pending requests (admin)
POST   /groups/{slug}/admin/approve     - Approve/reject request
```

---

## 🎨 UI/UX Features

### Group Cards (Discovery Page)
- Gradient background fallback if no cover image
- Icon placeholder if no thumbnail
- Member count badge
- "Member" badge for joined groups

### Group Profile
- Cover image hover to upload (admins)
- Thumbnail hover to upload (admins)
- Role-based action buttons:
  - "Join Group" (non-members)
  - "Request Pending" (disabled if pending)
  - "Leave Group" (members, not owner)
  - "Invite" button (admins/mods)
  - "Settings" button (owner)
- Tabbed interface with badge count on "Requests" tab

### Responsive Design
- Mobile-friendly grid layouts
- Responsive navigation
- Touch-friendly buttons

---

## 🧪 Testing Recommendations

### Feature Tests
1. **Group CRUD**
   - Create group as authenticated user
   - Update group as owner
   - Delete group as owner
   - Non-owner cannot update/delete

2. **Permissions**
   - Admin can perform all actions
   - Moderator can invite and approve
   - Member can only post
   - Non-member cannot post

3. **Join Flow**
   - Auto-approval: immediate membership
   - Manual approval: pending status
   - Cannot join twice
   - Owner cannot leave their group

4. **Invitations**
   - Invitation creates token
   - Token expires after 7 days
   - Accept invitation adds to group
   - Reject invitation updates status
   - Cannot accept expired invitation

5. **Notifications**
   - Invitation sends email + database notification
   - Join request notifies all admins
   - Notifications contain correct data

---

## 🔄 Future Enhancements

Possible extensions to consider:
- [ ] Group categories/tags
- [ ] Private vs Public groups
- [ ] Group analytics dashboard
- [ ] Member roles customization
- [ ] Pinned posts
- [ ] Group events/calendar
- [ ] File/document sharing
- [ ] Member search within group
- [ ] Transfer ownership
- [ ] Ban/mute members
- [ ] Group chat/messaging

---

## 📚 Related Documentation

- [PROFILE_IMAGES_CHANGELOG.md](./PROFILE_IMAGES_CHANGELOG.md) - Image upload patterns
- [FLASH_MESSAGES_FLOW.md](./FLASH_MESSAGES_FLOW.md) - Flash messages
- [UI_UX_GUIDE.md](./UI_UX_GUIDE.md) - UI/UX patterns

---

## 🐛 Troubleshooting

### Common Issues

**1. Routes not found**
- Run `npm run dev` to regenerate TypeScript routes
- Check `routes/web.php` for correct route definitions

**2. Permissions not working**
- Verify user is a member: `$group->isMember($user)`
- Check role in pivot table: `group_users.role`
- Ensure GroupPermissionService is injected

**3. Images not uploading**
- Check file permissions on `storage/app/public/`
- Verify symlink: `php artisan storage:link`
- Check max upload size in php.ini

**4. Notifications not sending**
- Queue workers running: `php artisan queue:work`
- Check `MAIL_*` settings in `.env`
- Verify user has email address

---

## ✅ Implementation Checklist

- [x] Database migrations created and run
- [x] Models with relationships
- [x] Enums for roles and permissions
- [x] Service layer (Permissions, Invitations)
- [x] Policy for authorization
- [x] Form requests for validation
- [x] API resources for data transformation
- [x] Controller with all actions
- [x] Notifications (email + database)
- [x] Routes defined
- [x] TypeScript interfaces
- [x] Vue pages (Discover, Create, Show, Edit)
- [x] Navigation updated
- [x] Components updated (CreatePost, CreatePostModal)
- [x] Documentation created

---

**Status:** ✅ **COMPLETE** - Ready for testing and deployment!
