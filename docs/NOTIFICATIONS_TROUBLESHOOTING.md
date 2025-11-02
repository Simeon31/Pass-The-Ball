# Notifications System - Troubleshooting Guide

## Common Issues and Solutions

### 1. Column not found: 'notifiable_type'

**Error:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'notifications.notifiable_type' in 'where clause'
```

**Cause:** The notifications table was created with incomplete structure (only `id` and `timestamps`).

**Solution:**
```bash
php artisan migrate
```

This will run the fix migration `2025_10_20_201941_fix_notifications_table_structure.php` which:
1. Drops the existing incomplete table
2. Recreates it with proper Laravel notification structure

**Note:** Any existing notifications will be lost when the table is dropped.

---

### 2. Notifications Page Shows 404

**Possible Causes:**
- Route not registered
- Middleware blocking access
- TypeScript compilation error

**Solutions:**

1. **Check routes are registered:**
```bash
php artisan route:list --name=notifications
```

Should show:
- `GET /notifications`
- `POST /notifications/{id}/read`
- `POST /notifications/mark-all-read`
- `DELETE /notifications/{id}`
- `DELETE /notifications/delete-read`

2. **Clear route cache:**
```bash
php artisan route:clear
php artisan optimize:clear
```

3. **Verify middleware:**
Ensure user is authenticated and email is verified.

---

### 3. TypeScript Compilation Errors

**Error:**
```
Cannot find module '@/components/app/NotificationItem.vue'
```

**Solutions:**

1. **Restart dev server:**
```bash
npm run dev
```

2. **Clear node cache:**
```bash
rm -rf node_modules/.vite
npm run dev
```

3. **Verify file exists:**
Check `resources/js/components/app/NotificationItem.vue` exists

---

### 4. Notifications Not Being Received ⚠️ COMMON ISSUE

**Symptoms:**
- User invites someone to a group
- Invitee doesn't receive notification
- No entry in notifications table

**Root Cause:**
Notifications were queued (`implements ShouldQueue`) but queue worker wasn't running or jobs were failing.

**Solution Applied:** ✅
1. Removed `implements ShouldQueue` from notification classes
2. Notifications now send immediately (synchronously)
3. Cleared failed jobs: `php artisan queue:flush`

**Files Updated:**
- `app/Notifications/GroupInvitationNotification.php`
- `app/Notifications/GroupJoinRequestNotification.php`

**If issue persists:**

1. **Check failed jobs:**
```bash
php artisan queue:failed
```

If you see failed jobs, flush them:
```bash
php artisan queue:flush
```

2. **Verify notification was created:**
```bash
php artisan tinker
```
```php
// Check if notification exists
DB::table('notifications')->orderBy('created_at', 'desc')->first();
```

3. **Test notification manually:**
```bash
php artisan tinker
```
```php
$user = App\Models\User::find(2); // Invitee
$invitation = App\Models\GroupInvitation::latest()->first();
$user->notify(new App\Notifications\GroupInvitationNotification($invitation));

// Check if it appears
DB::table('notifications')->where('notifiable_id', 2)->count();
```

---

### 5. Notifications Not Appearing (Other Causes)

**Possible Causes:**
- Database channel not configured
- Wrong notification data structure

**Solutions:**

1. **Check notification was sent:**
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

2. **Verify notification includes required fields:**
```php
public function toArray(object $notifiable): array
{
    return [
        'message' => 'Your notification message',  // Required
        'action_url' => '/path/to/action',         // Required
        // ... other data
    ];
}
```

3. **Check notification channels:**
```php
public function via(object $notifiable): array
{
    return ['mail', 'database']; // Ensure 'database' is included
}
```

---

### 5. Unread Count Not Updating

**Cause:** Notification not being marked as read properly.

**Solutions:**

1. **Check `read_at` column updates:**
```sql
SELECT id, type, read_at FROM notifications WHERE notifiable_id = [user_id];
```

2. **Verify route is being called:**
Check browser DevTools Network tab for POST to `/notifications/{id}/read`

3. **Clear cache:**
```bash
php artisan optimize:clear
```

---

### 6. "View" Button Does Nothing ⚠️ FIXED

**Symptom:**
- Click "View" on a notification
- Nothing happens, page doesn't navigate
- No errors in console

**Root Cause:**
The `handleClick` function was calling `handleMarkAsRead()` and then immediately calling `router.visit()`. The POST request to mark as read wasn't completing before the navigation, causing a race condition.

**Solution Applied:** ✅
Updated `NotificationItem.vue` to properly wait for mark-as-read to complete before navigating:

```typescript
const handleClick = () => {
    const actionUrl = props.notification.data.action_url;
    if (!actionUrl) return;

    // Mark as read if unread, then navigate
    if (isUnread.value) {
        router.post(
            `/notifications/${props.notification.id}/read`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Navigate after marking as read
                    router.visit(actionUrl);
                },
            },
        );
    } else {
        // Already read, just navigate
        router.visit(actionUrl);
    }
};
```

**How It Works Now:**
1. Click "View" on unread notification
2. POST request marks it as read
3. **After** success, navigation happens
4. User lands on the correct page

**If Still Not Working:**

1. **Check action_url exists:**
```bash
php artisan tinker
```
```php
DB::table('notifications')->latest()->first()->data;
// Should have 'action_url' field
```

2. **Check browser console for errors**

3. **Verify route exists:**
```bash
php artisan route:list | grep invitations
```

---

---

### 7. CORS Error When Clicking "View" ⚠️ FIXED

**Error:**
```
Access to XMLHttpRequest at 'http://localhost:8000/groups/invitations' from origin 'http://127.0.0.1:8000' 
has been blocked by CORS policy
```

**Root Cause:**
Notification `action_url` was using `url()` helper which generates full URLs like `http://localhost:8000/groups/invitations`. When Inertia.js `router.visit()` tries to navigate to a full URL with a different domain (localhost vs 127.0.0.1), it triggers CORS preflight requests which fail.

**Solution Applied:** ✅

**1. Fixed Notification Classes:**
Updated both notification classes to use relative paths instead of full URLs:

```php
// BEFORE (caused CORS error):
'action_url' => url('/groups/invitations'),  // Full URL

// AFTER (works correctly):
'action_url' => '/groups/invitations',  // Relative path
```

**Files Updated:**
- `GroupInvitationNotification.php` - Changed from `url('/groups/invitations')` to `'/groups/invitations'`
- `GroupJoinRequestNotification.php` - Changed from `url('/groups/...')` to `'/groups/...'`

**2. Fixed Existing Notifications:**
Created and ran command to fix existing notifications in database:
```bash
php artisan notifications:fix-urls
```

This command converts all full URLs in existing notifications to relative paths.

**Why This Works:**
- Inertia.js `router.visit()` expects relative paths for same-origin navigation
- Relative paths work regardless of domain (localhost, 127.0.0.1, production domain)
- No CORS issues since it's treated as same-origin navigation

**If You Still See CORS Errors:**

1. **Run the fix command:**
```bash
php artisan notifications:fix-urls
```

2. **Clear browser cache:**
- Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Or clear browser cache completely

3. **Verify notification data:**
```bash
php artisan tinker
```
```php
DB::table('notifications')->latest()->first()->data;
// action_url should be '/groups/invitations', NOT 'http://...'
```

4. **Create new test notification** to verify fix:
Invite a user to a group and check the notification works.

---

### 8. 404 Error on /groups/invitations ⚠️ FIXED

**Error:**
```
GET http://localhost:8000/groups/invitations 404 (Not Found)
```

**Root Cause:**
Route order conflict in `routes/web.php`. Laravel's router matches routes sequentially, and the route `/groups/{group}` was defined **before** `/groups/invitations`. When accessing `/groups/invitations`, Laravel matched it to `/groups/{group}` treating `"invitations"` as a group slug, which then returned 404 because no group with slug "invitations" exists.

**Solution Applied:** ✅

Moved the invitations routes **before** the parameterized `/groups/{group}` route:

```php
// BEFORE (caused 404):
Route::get('/groups/{group}', ...)           // Line 96 - matches first!
// ... many routes later ...
Route::get('/groups/invitations', ...)       // Line 113 - never reached!

// AFTER (works correctly):
Route::get('/groups/invitations', ...)       // Specific route first
Route::get('/groups/{group}', ...)           // Parameterized route after
```

**Why Route Order Matters:**
- Laravel matches routes **top-to-bottom**
- **Specific routes** must come **before** parameterized routes
- `/groups/invitations` is more specific than `/groups/{group}`
- Once a route matches, Laravel stops looking

**Best Practice:**
Always define routes in this order:
1. Exact string routes (e.g., `/groups/invitations`)
2. Routes with specific prefixes (e.g., `/groups/invitations/{token}`)  
3. Parameterized routes (e.g., `/groups/{group}`)

**Cleared route cache:**
```bash
php artisan route:clear
php artisan optimize:clear
```

---

### 9. Icons Not Displaying

**Cause:** Lucide icons not imported properly.

**Solution:**

Verify imports in `NotificationItem.vue`:
```typescript
import {
    Bell,
    Users,
    MessageCircle,
    Heart,
    UserPlus,
    Trash2,
    Check,
} from 'lucide-vue-next';
```

---

### 7. Categories Not Working

**Cause:** Notification type not mapped in `NotificationResource`.

**Solution:**

Add notification type to `getCategory()` method in `NotificationResource.php`:

```php
private function getCategory(): string
{
    $typeMap = [
        'App\\Notifications\\GroupInvitationNotification' => 'invitation',
        'App\\Notifications\\GroupJoinRequestNotification' => 'join_request',
        'App\\Notifications\\YourNewNotification' => 'your_category', // Add here
    ];

    return $typeMap[$this->type] ?? 'general';
}
```

---

### 8. Pagination Not Working

**Possible Causes:**
- Incorrect pagination implementation
- JavaScript error preventing "Load more" click

**Solutions:**

1. **Check browser console for errors:**
Open DevTools Console tab

2. **Verify pagination links:**
```php
// In NotificationController
$notifications = $query->latest()->paginate(20);
```

3. **Check Inertia response:**
Ensure `PaginatedData` structure is correct

---

### 9. Flash Messages Not Showing

**Cause:** Flash message composable not properly initialized.

**Solutions:**

1. **Verify flash prop in HandleInertiaRequests middleware:**
```php
'flash' => [
    'status' => $request->session()->get('status'),
],
```

2. **Check composable usage:**
```typescript
const { showMessage, message, dismiss } = useFlashMessage('status', 5000);
```

---

### 10. Styling Issues (Dark/Light Mode)

**Cause:** Tailwind classes not generating properly.

**Solutions:**

1. **Rebuild CSS:**
```bash
npm run build
```

2. **Check Tailwind config includes notifications files:**
```javascript
// tailwind.config.js
content: [
    './resources/js/pages/**/*.vue',
    './resources/js/components/**/*.vue',
    // ...
]
```

---

## Debugging Commands

### Check Notifications Table Structure
```bash
php artisan db:table notifications
```

### View Recent Notifications (via Tinker)
```bash
php artisan tinker
```
```php
App\Models\User::find(1)->notifications()->latest()->take(5)->get()
```

### Send Test Notification
```bash
php artisan tinker
```
```php
$user = App\Models\User::first();
$user->notify(new App\Notifications\TestNotification());
```

### Clear All Caches
```bash
php artisan optimize:clear
npm run build
```

### Check Routes
```bash
php artisan route:list --name=notifications
```

### View Migration Status
```bash
php artisan migrate:status
```

---

## Prevention Tips

1. **Always include required fields in notifications:**
   - `message` (string)
   - `action_url` (string)

2. **Use database channel for in-app notifications:**
   ```php
   public function via(object $notifiable): array
   {
       return ['database']; // or ['mail', 'database']
   }
   ```

3. **Test notifications after creation:**
   ```bash
   php artisan tinker
   # Send test notification
   ```

4. **Keep NotificationResource category map updated:**
   Add new notification types as they're created

5. **Run migrations after pulling code:**
   ```bash
   php artisan migrate
   ```

---

## Getting Help

If you encounter issues not covered here:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Check Network tab for failed API requests
4. Review `docs/NOTIFICATIONS_SYSTEM.md` for architecture details
5. Review `docs/NOTIFICATIONS_IMPLEMENTATION_SUMMARY.md` for implementation details

---

## Quick Test Checklist

After setup, verify these work:

- [ ] `/notifications` page loads without errors
- [ ] Bell icon appears in sidebar
- [ ] Filter tabs work and show correct counts
- [ ] Individual notifications display properly
- [ ] "Mark as read" button works
- [ ] "Mark all as read" button works
- [ ] "View" button navigates correctly
- [ ] Delete notification works
- [ ] "Clear read" works
- [ ] Pagination works (if > 20 notifications)
- [ ] Empty states show correctly
- [ ] Flash messages appear and dismiss
