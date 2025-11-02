# Quick Reference: Group Invitations & Join Requests

**Quick lookup for developers working with the invitation system**

---

## 🚀 Quick Start

### Send an Invitation (Backend)
```php
// In your controller
$invitation = app(GroupInvitationService::class)->createInvitation(
    $group,
    $user,
    auth()->user()
);

$user->notify(new GroupInvitationNotification($invitation));
```

### Accept an Invitation (Backend)
```php
$invitation = GroupInvitation::where('token', $token)->firstOrFail();

if ($invitation->isValid()) {
    app(GroupInvitationService::class)->acceptInvitation($invitation);
}
```

### Check User Permissions (Frontend)
```vue
<Button v-if="group.permissions?.includes('invite_members')">
    Invite
</Button>

<Button v-if="group.permissions?.includes('approve_join_requests')">
    Manage Requests
</Button>
```

---

## 📁 Key Files

### Backend
| File | Purpose |
|------|---------|
| `app/Services/GroupInvitationService.php` | All invitation business logic |
| `app/Http/Controllers/GroupController.php` | Invitation & join request endpoints |
| `app/Http/Controllers/ProfileController.php` | User search API |
| `app/Models/GroupInvitation.php` | Invitation model with scopes |
| `app/Http/Requests/Group/InviteMemberRequest.php` | Invitation validation |
| `app/Http/Requests/Group/ApproveJoinRequestRequest.php` | Approval validation |
| `app/Notifications/GroupInvitationNotification.php` | Invitation email/notification |
| `app/Notifications/GroupJoinRequestNotification.php` | Join request notification |

### Frontend
| File | Purpose |
|------|---------|
| `resources/js/components/app/InviteMembersModal.vue` | Invite modal with search |
| `resources/js/pages/Groups/Invitations.vue` | User invitations page |
| `resources/js/pages/Groups/PendingRequests.vue` | Admin requests page |
| `resources/js/pages/Groups/Show.vue` | Group page (updated) |

---

## 🛣️ API Endpoints

### User-Facing
```
GET  /groups/invitations                      - View my invitations
GET  /groups/invitations/{token}/accept       - Email link (redirects to invitations page)
POST /groups/invitations/{token}/respond      - Accept/reject invitation
POST /groups/{group}/join                     - Join or request to join
```

### Admin-Facing
```
POST /groups/{group}/invite                   - Send invitation
GET  /groups/{group}/admin/requests           - View pending requests
POST /groups/{group}/admin/approve            - Approve/reject request
```

### API
```
GET  /api/users/search?q={query}&limit={limit} - Search users
```

---

## 🔧 Common Tasks

### Add Invitation Button
```vue
<script setup lang="ts">
import InviteMembersModal from '@/components/app/InviteMembersModal.vue';
const showInviteModal = ref(false);
</script>

<template>
    <Button @click="showInviteModal = true">Invite</Button>
    <InviteMembersModal v-model:isOpen="showInviteModal" :group="group" />
</template>
```

### Display Pending Requests Badge
```vue
<Link :href="`/groups/${group.slug}/admin/requests`">
    Requests
    <span v-if="pendingRequestsCount > 0" class="badge">
        {{ pendingRequestsCount }}
    </span>
</Link>
```

### Search Users
```typescript
const response = await fetch(`/api/users/search?q=${query}&limit=10`);
const data = await response.json();
const users = data.users;
```

### Check Invitation Status
```php
if ($invitation->isValid()) {
    // Can be accepted
}

if ($invitation->isExpired()) {
    // Show expired message
}
```

---

## 📝 Request/Response Examples

### Search Users API
**Request:**
```
GET /api/users/search?q=john&limit=10
```

**Response:**
```json
{
    "users": [
        {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe",
            "email": "john@example.com",
            "profile_picture_url": "/storage/avatars/1.jpg"
        }
    ]
}
```

### Send Invitation
**Request:**
```
POST /groups/{slug}/invite
Content-Type: application/json

{
    "user_id": 5
}
```

**Response:**
```
302 Redirect
Flash: "Invitation sent successfully!"
```

### Accept Invitation
**Request:**
```
POST /groups/invitations/{token}/respond
Content-Type: application/json

{
    "action": "accept"
}
```

**Response:**
```
302 Redirect to /groups/{slug}
Flash: "You have successfully joined the group!"
```

### Approve Join Request
**Request:**
```
POST /groups/{slug}/admin/approve
Content-Type: application/json

{
    "user_id": 5,
    "action": "approve",
    "role": "member"
}
```

**Response:**
```
302 Redirect
Flash: "Join request approved!"
```

---

## 🎨 UI Components

### Status Badges
```vue
<Badge variant="default">Pending</Badge>
<Badge variant="destructive">Expired</Badge>
<Badge variant="secondary">Approved</Badge>
```

### Avatar with Fallback
```vue
<Avatar>
    <AvatarImage v-if="user.profile_picture_url" :src="user.profile_picture_url" />
    <AvatarFallback>{{ getUserInitials(user.name) }}</AvatarFallback>
</Avatar>
```

### Flash Messages
```vue
<script setup lang="ts">
import { useFlashMessage } from '@/composables/useFlashMessage';

const { showMessage, message, dismiss } = useFlashMessage('status', 5000);
</script>

<template>
    <div v-if="showMessage && message()" class="alert alert-success">
        {{ message() }}
        <button @click="dismiss">×</button>
    </div>
</template>
```

---

## 🔍 Debugging Tips

### Check Invitation Status
```php
// In tinker or controller
$invitation = GroupInvitation::find(1);
dd([
    'status' => $invitation->status,
    'is_valid' => $invitation->isValid(),
    'is_expired' => $invitation->isExpired(),
    'expires_at' => $invitation->token_expires_at,
]);
```

### Check User Permissions
```php
// In tinker or controller
$user = User::find(1);
$group = Group::find(1);
$service = app(GroupPermissionService::class);

dd([
    'can_invite' => $service->hasPermission($user, $group, GroupPermission::INVITE_MEMBERS),
    'can_approve' => $service->hasPermission($user, $group, GroupPermission::APPROVE_JOIN_REQUESTS),
]);
```

### Check Group Membership
```php
$group = Group::find(1);
$user = User::find(1);

dd([
    'is_member' => $group->isMember($user),
    'is_owner' => $group->isOwner($user),
    'role' => $group->getUserRole($user),
]);
```

### Debug Frontend Permissions
```vue
<template>
    <pre>{{ group.permissions }}</pre>
    <pre>Can Invite: {{ group.permissions?.includes('invite_members') }}</pre>
</template>
```

---

## ⚡ Performance Tips

1. **Eager Load Relationships**
```php
$invitations = GroupInvitation::with(['group', 'user', 'inviter'])->get();
```

2. **Use Scopes**
```php
$validInvitations = GroupInvitation::valid()->get();
$pendingRequests = $group->pendingRequests()->get();
```

3. **Debounce Search**
```typescript
const searchUsers = debounce(async () => {
    // Search logic
}, 300);
```

4. **Pagination**
```php
$requests = $group->pendingRequests()->paginate(20);
```

---

## 🔒 Security Checklist

- ✅ Only admins/moderators can invite
- ✅ Only admins/moderators can approve requests
- ✅ Users can only see their own invitations
- ✅ Token expiration enforced
- ✅ CSRF protection enabled
- ✅ Form request validation
- ✅ Policy authorization checks
- ✅ Secure token generation (64 chars)

---

## 📊 Database Queries

### Get User's Pending Invitations
```php
$invitations = GroupInvitation::where('user_id', $userId)
    ->valid()
    ->with(['group', 'inviter'])
    ->get();
```

### Get Group's Pending Join Requests
```php
$requests = $group->pendingRequests()
    ->withPivot(['created_at'])
    ->get();
```

### Get All Group Invitations (Admin)
```php
$invitations = GroupInvitation::where('group_id', $groupId)
    ->with(['user', 'inviter'])
    ->latest()
    ->paginate(20);
```

---

## 🧪 Testing Examples

### Test Invitation Creation
```php
$response = $this->actingAs($admin)
    ->post("/groups/{$group->slug}/invite", [
        'user_id' => $user->id,
    ]);

$response->assertRedirect();
$response->assertSessionHas('status');
$this->assertDatabaseHas('group_invitations', [
    'group_id' => $group->id,
    'user_id' => $user->id,
]);
```

### Test Join Request
```php
$response = $this->actingAs($user)
    ->post("/groups/{$group->slug}/join");

$this->assertDatabaseHas('group_users', [
    'group_id' => $group->id,
    'user_id' => $user->id,
    'status' => 'pending',
]);
```

---

## 🐛 Common Issues

### Issue: Invitation not found
**Solution:** Check token is correct and invitation hasn't expired

### Issue: User can't accept invitation
**Solution:** Check invitation status is 'pending' and hasn't been used

### Issue: Search returns no results
**Solution:** Ensure query is at least 2 characters and user exists

### Issue: Invite button not showing
**Solution:** Check user has 'invite_members' permission

### Issue: TypeScript route not found
**Solution:** Run `npm run dev` to regenerate routes

---

## 📚 Related Documentation

- `docs/GROUP_INVITATIONS_JOIN_REQUESTS.md` - Complete documentation
- `docs/GROUPS_FEATURE_GUIDE.md` - Main groups feature guide
- `docs/IMPLEMENTATION_SUMMARY.md` - Implementation summary

---

## 🤝 Need Help?

1. Check this quick reference
2. Review the full documentation
3. Look at existing implementation patterns
4. Test in browser dev tools
5. Check Laravel logs (`storage/logs/laravel.log`)

---

**Quick Reference v1.0** | Last Updated: October 20, 2025
