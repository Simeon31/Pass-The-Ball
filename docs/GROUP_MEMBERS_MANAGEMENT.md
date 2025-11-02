# Group Members Management - Approved & Pending Users Feature

## Overview
This document describes the implementation of the group members management system that separates approved members from pending join requests, with inline approval/rejection functionality and notification system.

## Features Implemented

### 1. **Approved Members Display**
- **Location**: Groups/Show.vue - "Members" tab
- **Functionality**: Displays only approved group members
- **Data Source**: `Group::members()` relationship (filters by `status = 'approved'`)
- **Display**: Grid layout showing member avatars, names, and roles

### 2. **Pending Requests Management Tab**
- **Location**: Groups/Show.vue - "Requests" tab (admin-only)
- **Visibility**: Only shown to group admins/moderators with `approve_join_requests` permission
- **Functionality**: 
  - Lists all pending join requests
  - Displays user avatar, name, username, and request date
  - Inline approve/reject buttons for each request
  - Empty state when no pending requests exist

### 3. **Notification System**
Two new notification types were created to inform users about their join request status:

#### a. GroupJoinApprovedNotification
- **Trigger**: When admin approves a join request
- **Channels**: Email + Database
- **Message**: "Your request to join {group_name} has been approved"
- **Action**: Links to the group page
- **Category**: `join_approved`

#### b. GroupJoinRejectedNotification
- **Trigger**: When admin rejects a join request
- **Channels**: Email + Database
- **Message**: "Your request to join {group_name} was not approved"
- **Action**: Links to groups discovery page
- **Category**: `join_rejected`

## Technical Implementation

### Backend Changes

#### 1. New Notification Classes
**Files Created:**
- `app/Notifications/GroupJoinApprovedNotification.php`
- `app/Notifications/GroupJoinRejectedNotification.php`

**Key Features:**
- Extends Laravel's `Notification` class
- Uses `Queueable` trait for async processing
- Implements both `toMail()` and `toDatabase()` methods
- Does NOT implement `ShouldQueue` to send immediately (based on project pattern)

#### 2. GroupController Updates
**File**: `app/Http/Controllers/GroupController.php`

**Changes in `show()` method:**
```php
// Added pending requests data for admins
$pendingRequests = collect();
if ($user && $this->permissionService->canApproveRequests($user, $group)) {
    $pendingRequestsData = $group->pendingRequests()
        ->withPivot(['created_at'])
        ->get();
    $pendingRequestsCount = $pendingRequestsData->count();
    $pendingRequests = GroupMemberResource::collection($pendingRequestsData);
}
```

**Changes in `approveRequest()` method:**
```php
if ($data['action'] === 'approve') {
    // Update status to approved
    $group->members()->updateExistingPivot($userId, [
        'status' => 'approved',
        'role' => $data['role'] ?? GroupRole::MEMBER->value,
    ]);

    // Notify user of approval
    $pendingMember->notify(new GroupJoinApprovedNotification($group));

    return back()->with('status', 'Join request approved!');
} else {
    // Reject and remove the request
    $group->members()->detach($userId);

    // Notify user of rejection
    $pendingMember->notify(new GroupJoinRejectedNotification($group));

    return back()->with('status', 'Join request rejected.');
}
```

#### 3. NotificationResource Updates
**File**: `app/Http/Resources/NotificationResource.php`

Added new notification type mappings:
```php
private function getCategory(): string
{
    $typeMap = [
        // ... existing mappings
        'App\\Notifications\\GroupJoinApprovedNotification' => 'join_approved',
        'App\\Notifications\\GroupJoinRejectedNotification' => 'join_rejected',
    ];

    return $typeMap[$this->type] ?? 'general';
}
```

### Frontend Changes

#### 1. Groups/Show.vue Component
**File**: `resources/js/pages/Groups/Show.vue`

**New Props:**
```typescript
interface Props {
    // ... existing props
    pendingRequests?: GroupMember[];
}
```

**New Imports:**
```typescript
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { UserCheck, UserX } from 'lucide-vue-next';
```

**New Methods:**
```typescript
// Approve a join request
const handleApprove = (userId: number) => {
    if (confirm('Are you sure you want to approve this join request?')) {
        router.post(`/groups/${props.group.slug}/admin/approve`, {
            user_id: userId,
            action: 'approve',
            role: 'member',
        }, { preserveScroll: true });
    }
};

// Reject a join request
const handleReject = (userId: number) => {
    if (confirm('Are you sure you want to reject this join request?')) {
        router.post(`/groups/${props.group.slug}/admin/approve`, {
            user_id: userId,
            action: 'reject',
        }, { preserveScroll: true });
    }
};

// Helper functions
const getUserInitials = (name: string) => { /* ... */ };
const formatDate = (dateString: string) => { /* ... */ };
```

**Pending Requests Tab UI:**
- Uses `Card` and `CardContent` components for consistent styling
- Displays empty state with `Clock` icon when no requests
- Shows each pending request with:
  - User avatar (with fallback to initials)
  - Name, username, and "Pending" badge
  - Request timestamp (human-readable format)
  - Approve and Reject action buttons

#### 2. TypeScript Type Definitions
**File**: `resources/js/types/index.d.ts`

**Updated NotificationCategory:**
```typescript
export type NotificationCategory = 
    | 'invitation' 
    | 'join_request' 
    | 'join_approved'    // NEW
    | 'join_rejected'    // NEW
    | 'comment' 
    | 'reaction' 
    | 'follow' 
    | 'general';
```

## Data Flow

### Join Request Approval Flow
```
1. Admin clicks "Approve" button
   ↓
2. Frontend sends POST to /groups/{slug}/admin/approve
   ↓
3. GroupController::approveRequest() validates and processes
   ↓
4. Updates group_users pivot: status = 'approved'
   ↓
5. Sends GroupJoinApprovedNotification to user
   ↓
6. User receives email + in-app notification
   ↓
7. Flash message displayed to admin
   ↓
8. Page refreshes, request removed from pending tab
```

### Join Request Rejection Flow
```
1. Admin clicks "Reject" button
   ↓
2. Frontend sends POST to /groups/{slug}/admin/approve
   ↓
3. GroupController::approveRequest() validates and processes
   ↓
4. Removes user from group_users pivot table
   ↓
5. Sends GroupJoinRejectedNotification to user
   ↓
6. User receives email + in-app notification
   ↓
7. Flash message displayed to admin
   ↓
8. Page refreshes, request removed from pending tab
```

## Database Schema

The `group_users` pivot table structure:
```php
Schema::create('group_users', function (Blueprint $table) {
    $table->id();
    $table->string('status', 30);  // 'pending', 'approved', 'rejected'
    $table->string('role', 30);    // 'admin', 'moderator', 'member'
    $table->string('token', 1024)->nullable();
    $table->timestamp('token_expired_at')->nullable();
    $table->timestamp('token_used')->nullable();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('group_id')->constrained('groups');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamp('created_at')->nullable();
});
```

## Key Relationships

### Group Model
```php
// Returns only approved members
public function members(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'group_users')
        ->withPivot(['status', 'role', 'created_at'])
        ->withTimestamps()
        ->wherePivot('status', 'approved');
}

// Returns only pending requests
public function pendingRequests(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'group_users')
        ->withPivot(['status', 'role', 'created_at'])
        ->wherePivot('status', 'pending');
}
```

## UI/UX Considerations

### Permissions
- **Members Tab**: Visible to all (shows public member list)
- **Requests Tab**: Only visible to admins/moderators
- **Approve/Reject Actions**: Only admins/moderators with `approve_join_requests` permission

### Visual Design
- **Pending Badge**: Secondary variant with clock icon
- **Approve Button**: Default variant with UserCheck icon
- **Reject Button**: Destructive variant with UserX icon
- **Empty State**: Centered with icon and descriptive text
- **Confirmation Dialogs**: Native browser confirm() for safety

### Responsiveness
- Grid layout for members (adjusts columns on mobile)
- Stacked layout for pending requests on small screens
- Buttons remain accessible on all screen sizes

## Best Practices Followed

1. **DRY Principle**: 
   - Reused `GroupMemberResource` for both members and pending requests
   - Shared helper functions (`getUserInitials`, `formatDate`)
   - Consistent notification structure

2. **Type Safety**: 
   - TypeScript interfaces for all props
   - Strict typing for notification categories
   - Proper Resource classes on backend

3. **Security**:
   - Authorization via `GroupPermissionService`
   - Validation through `ApproveJoinRequestRequest`
   - CSRF protection on all POST requests

4. **User Experience**:
   - Confirmation dialogs before destructive actions
   - Flash messages for feedback
   - Preserve scroll position on updates
   - Human-readable timestamps

5. **Code Organization**:
   - Notifications in dedicated directory
   - Separate tabs for different user states
   - Clear method naming and documentation

## Testing Checklist

- [ ] Admin can see pending requests tab
- [ ] Non-admin cannot see pending requests tab
- [ ] Approved members appear in Members tab
- [ ] Pending users appear in Requests tab
- [ ] Approve button updates status and sends notification
- [ ] Reject button removes user and sends notification
- [ ] Notifications are stored in database
- [ ] Email notifications are sent
- [ ] Flash messages appear after actions
- [ ] Empty state displays when no pending requests
- [ ] Badge shows correct pending count
- [ ] Timestamps display in human-readable format

## Routes

All group-related routes:
```
GET    /groups                               - List all groups
POST   /groups                               - Create group
GET    /groups/create                        - Show create form
GET    /groups/{group}                       - Show group page (with members & pending requests)
POST   /groups/{group}/join                  - Join group
POST   /groups/{group}/leave                 - Leave group
POST   /groups/{group}/admin/approve         - Approve/reject join request (NEW USAGE)
GET    /groups/{group}/admin/requests        - View pending requests page
```

## Future Enhancements

Potential improvements:
1. Bulk approve/reject functionality
2. Role selection dropdown when approving
3. Reason field for rejection
4. Request history/audit log
5. Auto-expiry of old pending requests
6. Member search and filtering
7. Export member list functionality
8. Customizable notification templates
