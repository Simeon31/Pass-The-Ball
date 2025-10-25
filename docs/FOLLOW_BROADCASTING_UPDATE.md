# Follow/Unfollow Feature - Real-Time Broadcasting Update

## Date: 2025-01-XX

## Summary
Enhanced the follow/unfollow feature with real-time broadcasting to update follower/following counts automatically for both users, added dedicated pages to view followers and following lists, and implemented user-friendly flash messages with appropriate redirects.

**Key Improvements:**
- **Follow:** Stay on current page with success message
- **Unfollow:** Redirect to your own profile with success message
- **Real-time updates:** Both users see count updates via broadcasting
- **Flash messages:** Clear feedback for all actions

## Changes Made

### 1. Real-Time Broadcasting Enhancement

#### Modified: `app/Events/UserFollowed.php`
**Changes:**
- Updated `broadcastOn()` to return an array of two channels instead of one
- Now broadcasts to both `users.{followed_user_id}` and `users.{follower_id}`
- Added `followers_count` and `following_count` to broadcast payload
- Both users receive real-time updates when follow/unfollow occurs

**Before:**
```php
public function broadcastOn(): Channel
{
    return new Channel("users.{$this->followedUser->id}");
}
```

**After:**
```php
public function broadcastOn(): array
{
    return [
        new Channel("users.{$this->followedUser->id}"),
        new Channel("users.{$this->follower->id}"),
    ];
}
```

### 2. Profile Page Real-Time Updates

#### Modified: `resources/js/pages/Profile.vue`
**Changes:**
- Added Echo import for Laravel Broadcasting
- Added `onMounted` and `onUnmounted` lifecycle hooks
- Created Echo channel listener for `UserFollowed` event
- Made follower/following counts clickable links to list pages
- Reactive refs automatically update when broadcasts are received

**Echo Listener:**
```typescript
onMounted(() => {
    if (page.props.auth.user) {
        Echo.channel(`users.${page.props.auth.user.id}`)
            .listen('UserFollowed', (event: any) => {
                if (event.followed_user_id === page.props.auth.user.id) {
                    followersCount.value = event.followers_count;
                }
                if (event.follower_id === page.props.auth.user.id) {
                    followingCount.value = event.following_count;
                }
            });
    }
});
```

**Clickable Counts:**
```vue
<Link :href="`/users/${user.id}/followers`" class="...">
    {{ followersCount }} {{ followersCount === 1 ? 'Follower' : 'Followers' }}
</Link>
<Link :href="`/users/${user.id}/following`" class="...">
    {{ followingCount }} Following
</Link>
```

### 3. Followers/Following List Pages

#### Created: `resources/js/pages/Followers.vue`
**Purpose:** Display list of followers or following for any user

**Features:**
- Reusable for both followers and following lists
- Shows user avatars, names, and usernames
- Follow/unfollow buttons for each user (except authenticated user)
- Links to user profiles
- Back button to return to profile
- Empty state when no followers/following
- Responsive design with hover effects

**Props:**
```typescript
interface Props {
    user: User;              // The profile user
    followers: User[];       // List of followers or following
    type: 'followers' | 'following';
}
```

### 4. Backend Controller Methods

#### Modified: `app/Http/Controllers/FollowerController.php`
**Added Methods:**

**`followers(User $user): Response`**
- Returns Inertia page with followers list
- Fetches followers with pivot data (created_at)
- Orders by most recent first
- Returns UserResource collection

**`following(User $user): Response`**
- Returns Inertia page with following list
- Fetches following users with pivot data
- Orders by most recent first
- Returns UserResource collection

**Both methods return:**
- `user` - The profile user (UserResource)
- `followers` - Array of users (UserResource collection)
- `type` - Either 'followers' or 'following'

### 5. Routes

#### Modified: `routes/web.php`
**Added Routes:**

```php
// Get followers list
Route::get('/users/{user}/followers', [FollowerController::class, 'followers'])
    ->middleware(['auth', 'verified'])
    ->name('users.followers');

// Get following list
Route::get('/users/{user}/following', [FollowerController::class, 'following'])
    ->middleware(['auth', 'verified'])
    ->name('users.following');
```

### 6. Flash Messages & Redirects

#### Modified: `app/Http/Controllers/FollowerController.php`
**Behavior:**

**On Follow:**
- Stays on current page (`back()`)
- Flash message: `"You are now following {User Name}"`
- Flash key: `status`

**On Unfollow:**
- Redirects to current user's profile (`redirect("/profile/{username}")`)
- Flash message: `"You unfollowed {User Name}"`
- Flash key: `status`

**On Self-Follow Attempt:**
- Stays on current page (`back()`)
- Flash message: `"You cannot follow yourself."`
- Flash key: `error`

**Implementation:**
```php
// Follow - stay on same page
return back()->with('status', "You are now following {$user->name}");

// Unfollow - go to your profile
return redirect("/profile/{$currentUser->username}")
    ->with('status', "You unfollowed {$user->name}");
```

## User Experience Flow

### Scenario 1: User A Follows User B

1. **User A clicks "Follow" on User B's profile**
2. Backend creates follower record
3. Backend sends notification to User B
4. **Backend broadcasts to BOTH channels:**
   - `users.{User B's ID}` - User B receives follower count update
   - `users.{User A's ID}` - User A receives following count update
5. **Backend redirects with flash message:**
   - Page stays on current page (User B's profile)
   - Flash message: "You are now following {User B's name}"
6. **Real-time updates (without page refresh on other tabs):**
   - If User B has profile open in another tab, follower count increments
   - If User A has their profile open in another tab, following count increments

### Scenario 2: User A Unfollows User B

1. **User A clicks "Unfollow" on User B's profile**
2. Backend deletes follower record
3. **Backend broadcasts to BOTH channels:**
   - `users.{User B's ID}` - User B receives follower count update
   - `users.{User A's ID}` - User A receives following count update
4. **Backend redirects to User A's profile with flash message:**
   - Navigates to `/profile/{User A's username}`
   - Flash message: "You unfollowed {User B's name}"
5. **Real-time updates (without page refresh on other tabs):**
   - If User B has profile open in another tab, follower count decrements

### Scenario 3: Viewing Followers List

1. User clicks on "X Followers" on any profile
2. Navigates to `/users/{user_id}/followers`
3. Sees list of all followers with:
   - Avatar and name
   - Username
   - Follow button (if not already following)
4. Can click on any user to view their profile
5. Can follow/unfollow directly from list (triggers redirect with flash message)

### Scenario 4: Viewing Following List

1. User clicks on "Y Following" on any profile
2. Navigates to `/users/{user_id}/following`
3. Sees list of users they follow
4. Same features as followers list

## Technical Details

### Broadcasting Payload
```json
{
    "followed_user_id": 123,
    "follower_id": 456,
    "is_following": true,
    "followers_count": 101,
    "following_count": 50
}
```

### Database Queries
- Uses `withPivot('created_at')` to access pivot table timestamp
- Orders by `orderByPivot('created_at', 'desc')`
- Efficient queries with eager loading via UserResource

## Testing Checklist

### Real-Time Broadcasting
- [ ] Open User A's profile in Browser 1 (logged in as User A)
- [ ] Open User A's profile in Browser 2 (logged in as User B)
- [ ] User B clicks "Follow"
- [ ] Verify User A sees follower count update without refresh
- [ ] Verify User B sees following count update without refresh
- [ ] User B clicks "Unfollow"
- [ ] Verify both counts decrement in real-time

### Followers/Following Lists
- [ ] Click on "X Followers" on profile
- [ ] Verify followers list displays correctly
- [ ] Click on a follower to visit their profile
- [ ] Return and click "Y Following"
- [ ] Verify following list displays correctly
- [ ] Follow/unfollow users from list
- [ ] Verify counts update in real-time
- [ ] Test empty state (user with no followers/following)

## Configuration Required

### Environment Variables
Ensure these are set in `.env`:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Queue Worker
Broadcasting requires queue worker to be running:

```bash
php artisan queue:work
```

Or in development (auto-restart on code changes):
```bash
php artisan queue:listen
```

## Files Modified/Created

### Created
- `resources/js/pages/Followers.vue` - Followers/following list page

### Modified
- `app/Events/UserFollowed.php` - Broadcast to both users, include counts
- `app/Http/Controllers/FollowerController.php` - Added followers() and following() methods
- `routes/web.php` - Added followers and following routes
- `resources/js/pages/Profile.vue` - Added Echo listener, clickable counts
- `docs/FOLLOW_UNFOLLOW_FEATURE.md` - Updated documentation

## Known Issues/Limitations

1. **Node.js Version:** Dev server requires Node.js 20.19+ or 22.12+
2. **Pagination:** Followers/following lists don't have pagination yet (future enhancement)
3. **Search:** No search/filter functionality in lists (future enhancement)
4. **Private Channels:** Currently using public channels (should upgrade to private/presence channels)

## Next Steps/Future Enhancements

1. Add pagination for followers/following lists (infinite scroll)
2. Add search/filter functionality
3. Upgrade to private channels for better security
4. Add mutual followers badge
5. Add "Remove Follower" functionality
6. Add follow suggestions
7. Export followers/following lists

## Related Documentation

- [FOLLOW_UNFOLLOW_FEATURE.md](./FOLLOW_UNFOLLOW_FEATURE.md) - Complete feature documentation
- [NOTIFICATIONS_SYSTEM.md](./NOTIFICATIONS_SYSTEM.md) - Notification system architecture
- [FLASH_MESSAGES_FLOW.md](./FLASH_MESSAGES_FLOW.md) - Flash message patterns

## Notes

- Real-time updates work best with multiple browser sessions for testing
- Echo must be properly configured in `resources/js/echo.ts`
- Broadcasting requires queue worker to be running
- TypeScript routes regenerate automatically when dev server runs
