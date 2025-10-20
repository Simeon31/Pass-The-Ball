# Notifications System Feature

## Overview

A comprehensive Facebook-style notifications system that allows users to view and manage all their notifications in one centralized page with categorization and filtering.

## Architecture

### Backend Components

#### 1. NotificationController (`app/Http/Controllers/NotificationController.php`)
Handles all notification-related operations:

**Methods:**
- `index()`: Display paginated notifications with filtering
- `markAsRead()`: Mark a single notification as read
- `markAllAsRead()`: Mark all notifications as read
- `destroy()`: Delete a single notification
- `deleteRead()`: Delete all read notifications

**Filtering Options:**
- `all`: All notifications
- `unread`: Only unread notifications
- `invitations`: Group invitations only
- `posts`: Post-related notifications (comments, reactions)
- `groups`: Group-related notifications (invitations, join requests)

#### 2. NotificationResource (`app/Http/Resources/NotificationResource.php`)
Transforms Laravel notifications for frontend consumption:

**Fields:**
- `id`: Notification UUID
- `type`: Full class name of notification
- `category`: Simplified category (invitation, join_request, comment, reaction, follow, general)
- `data`: Notification data payload
- `read_at`: ISO timestamp when read (null if unread)
- `created_at`: ISO timestamp of creation
- `time_ago`: Human-readable time difference (e.g., "2 hours ago")

**Category Mapping:**
```php
[
    'App\\Notifications\\GroupInvitationNotification' => 'invitation',
    'App\\Notifications\\GroupJoinRequestNotification' => 'join_request',
    'App\\Notifications\\PostCommentNotification' => 'comment',
    'App\\Notifications\\PostReactionNotification' => 'reaction',
    'App\\Notifications\\UserFollowNotification' => 'follow',
]
```

### Frontend Components

#### 1. Notifications Page (`resources/js/pages/Notifications.vue`)
Main notifications hub with filtering, pagination, and bulk actions.

**Features:**
- Filter tabs: All, Unread, Invitations, Posts, Groups
- Badge counts for each filter
- Mark all as read button
- Clear read notifications button
- Load more pagination
- Empty states per filter
- Flash message support

**Props:**
```typescript
{
    notifications: PaginatedData<Notification>,
    counts: NotificationCounts,
    currentFilter: 'all' | 'unread' | 'invitations' | 'posts' | 'groups'
}
```

#### 2. NotificationItem Component (`resources/js/components/app/NotificationItem.vue`)
Individual notification card display.

**Features:**
- Category-specific icons (Bell, Users, MessageCircle, Heart, UserPlus)
- Color-coded categories
- Unread indicator with "New" badge
- Visual distinction (highlighted background for unread)
- Action buttons: View, Mark as Read, Delete
- Click-to-navigate to action URL

**Category Icons & Colors:**
- `invitation`: Users icon, blue
- `join_request`: UserPlus icon, green
- `comment`: MessageCircle icon, purple
- `reaction`: Heart icon, pink
- `follow`: UserPlus icon, indigo
- `general`: Bell icon, gray

#### 3. Sidebar Navigation (`resources/js/components/AppSidebar.vue`)
Added Notifications link with Bell icon to main navigation menu.

### TypeScript Types

#### Notification Interface (`resources/js/types/index.d.ts`)
```typescript
export type NotificationCategory = 'invitation' | 'join_request' | 'comment' | 'reaction' | 'follow' | 'general';

export interface Notification {
    id: string;
    type: string;
    category: NotificationCategory;
    data: {
        message?: string;
        action_url?: string;
        [key: string]: any;
    };
    read_at: string | null;
    created_at: string;
    time_ago: string;
}

export interface NotificationCounts {
    all: number;
    unread: number;
    invitations: number;
    posts: number;
    groups: number;
}
```

### Routes

```php
// Notifications
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/delete-read', [NotificationController::class, 'deleteRead'])->name('notifications.deleteRead');
});
```

## Notification Data Structure

All notifications sent via `database` channel should include these fields in their `toArray()` method:

```php
[
    'type' => 'notification_type',           // String identifier
    'message' => 'User-friendly message',    // Display text (required)
    'action_url' => '/path/to/action',       // RELATIVE path for Inertia navigation (required)
    // ... other notification-specific data
]
```

**⚠️ IMPORTANT:** Always use **relative paths** (starting with `/`) for `action_url`, NOT full URLs:

```php
// ✅ CORRECT:
'action_url' => '/groups/invitations',
'action_url' => '/groups/' . $group->slug . '/admin/requests',

// ❌ WRONG (causes CORS errors):
'action_url' => url('/groups/invitations'),
'action_url' => route('groups.invitations'),  // Generates full URL
```

**Why Relative Paths?**
- Inertia.js `router.visit()` expects relative paths for same-origin navigation
- Full URLs cause CORS preflight requests when domain differs (localhost vs 127.0.0.1)
- Relative paths work across all environments (local, staging, production)


### Updated Notifications

#### GroupInvitationNotification
Already includes:
- ✅ `message`: "{inviter_name} invited you to join {group_name}"
- ✅ `action_url`: "/groups/invitations"

#### GroupJoinRequestNotification
Updated to include:
- ✅ `message`: "{requester_name} requested to join {group_name}"
- ✅ `action_url`: "/groups/{slug}/admin/requests"

## User Interactions

### Viewing Notifications
1. User clicks Bell icon in sidebar
2. Redirected to `/notifications`
3. Sees all notifications by default
4. Can filter by category using tabs
5. Unread notifications highlighted with blue background and "New" badge

### Marking as Read
**Individual:**
1. Click "Mark as read" button on notification
2. POST to `/notifications/{id}/read`
3. Notification background returns to normal
4. "New" badge removed

**Bulk:**
1. Click "Mark all as read" button in header
2. POST to `/notifications/mark-all-read`
3. All unread notifications marked as read
4. Unread count resets to 0

### Deleting Notifications
**Individual:**
1. Click trash icon on notification
2. Confirmation dialog appears
3. DELETE to `/notifications/{id}`
4. Notification removed from list

**Bulk (Read Only):**
1. Click "Clear read" button in header
2. Confirmation dialog appears
3. DELETE to `/notifications/delete-read`
4. All read notifications removed

### Navigating to Actions
1. Click "View" button or anywhere on notification card
2. If unread, automatically marked as read
3. Redirected to `action_url` from notification data

## Pagination

- Default: 20 notifications per page
- "Load more" button appears when next page exists
- Pagination info displayed at bottom: "Showing X to Y of Z notifications"
- Preserves scroll position on page change

## Empty States

Contextual empty states per filter:
- **All**: "When you get notifications, they will appear here."
- **Unread**: "You're all caught up! No unread notifications."
- **Invitations**: "No group invitations at the moment."
- **Posts**: "No notifications about posts yet."
- **Groups**: "No group-related notifications."

## Flash Messages

Success messages shown for:
- ✅ Notification marked as read
- ✅ All notifications marked as read
- ✅ Notification deleted
- ✅ All read notifications deleted

Uses `useFlashMessage` composable with 5-second auto-dismiss.

## Styling

### Unread Notifications
```css
bg-primary/5 border-primary/20  /* Light blue background */
font-semibold                    /* Bold text */
bg-primary/10                    /* Icon background */
```

### Read Notifications
```css
bg-background                    /* Normal background */
font-normal                      /* Normal weight */
bg-muted                         /* Gray icon background */
```

### Category Colors
- Blue: Invitations
- Green: Join Requests
- Purple: Comments
- Pink: Reactions
- Indigo: Follows
- Gray: General

## Future Enhancements

1. **Real-time Updates**: WebSocket/Pusher integration for instant notifications
2. **Badge Count**: Show unread count badge on sidebar Bell icon
3. **More Categories**: Support for follows, mentions, group posts, etc.
4. **Preferences**: User settings to control notification types
5. **Sound/Desktop Notifications**: Browser notifications for new items
6. **Search**: Search within notifications
7. **Archive**: Archive instead of delete for important notifications

## Testing

### Manual Testing Checklist

1. **Filtering:**
   - [ ] All filter shows all notifications
   - [ ] Unread filter shows only unread
   - [ ] Invitations filter shows only group invitations
   - [ ] Posts filter shows comments and reactions
   - [ ] Groups filter shows invitations and join requests

2. **Marking as Read:**
   - [ ] Individual "Mark as read" works
   - [ ] "Mark all as read" marks all unread
   - [ ] Visual styling updates correctly
   - [ ] Unread count decreases

3. **Deleting:**
   - [ ] Individual delete removes notification
   - [ ] "Clear read" removes only read notifications
   - [ ] Confirmation dialogs appear

4. **Navigation:**
   - [ ] "View" button navigates to action_url
   - [ ] Auto-marks unread as read on navigation
   - [ ] Sidebar Bell icon links to /notifications

5. **Pagination:**
   - [ ] Load more button appears when needed
   - [ ] Pagination info displays correctly
   - [ ] Scroll position preserved

6. **Empty States:**
   - [ ] Correct message shown for each filter
   - [ ] Icon and text displayed properly

## Integration with Existing Features

### Groups
- Group invitations appear in Invitations filter
- Join requests appear in Groups filter
- Action URLs link to:
  - `/groups/invitations` (for invitees)
  - `/groups/{slug}/admin/requests` (for admins)

### Posts (Future)
- Comments will appear in Posts filter
- Reactions will appear in Posts filter
- Action URLs will link to specific posts

### Follows (Future)
- Follow notifications will appear in separate category
- Action URLs will link to user profiles

## Database

Uses Laravel's built-in `notifications` table with proper structure:
- `id`: UUID primary key (char(36))
- `type`: Notification class name (varchar)
- `notifiable_type`: User model class (varchar)
- `notifiable_id`: User ID (bigint)
- `data`: JSON payload (text)
- `read_at`: Timestamp or null
- `created_at`: Timestamp
- `updated_at`: Timestamp

### Migration Required

The notifications table was fixed with migration `2025_10_20_201941_fix_notifications_table_structure.php` which drops and recreates the table with the proper Laravel notification structure.

**If you encounter "Column not found: notifiable_type" error:**
1. Run `php artisan migrate` to apply the fix migration
2. The table will be recreated with correct structure
3. Any existing notifications will be lost (table is dropped and recreated)

**Original migration:** `2025_09_30_182409_create_notifications_table.php` has also been updated with the correct structure for reference.
