# Follow/Unfollow Feature Implementation

## Overview

Implemented a complete follow/unfollow system with real-time broadcasting and email + in-app notifications. This is a **one-way follow system** (like Twitter/Instagram) where users can follow others without requiring mutual acceptance.

## Architecture

### Backend Components

#### 1. Models

**Follower Model** (`app/Models/Follower.php`)
- Pivot table model managing follower relationships
- Relations: `user()` (being followed), `follower()` (following)
- Fillable: `user_id`, `follower_id`
- Only tracks `created_at` (no updates needed)

**User Model Updates** (`app/Models/User.php`)
- `followers()`: Many-to-many relationship to users following this user
- `following()`: Many-to-many relationship to users this user follows
- `isFollowing(User $user)`: Check if this user follows another user
- `isFollowedBy(User $user)`: Check if this user is followed by another user

**Post Model Updates** (`app/Models/Post.php`)
- Added `visibility` field with options: `public`, `private`, `followers_only`
- Allows posts to be visible only to followers

#### 2. Database

**Migration** (`2025_10_25_000000_add_visibility_to_posts_table.php`)
- Adds `visibility` ENUM column to posts table
- Default value: `public`
- Options: `public`, `private`, `followers_only`

**Existing Table**: `followers` (created by migration `2025_09_30_182356_create_followers_table.php`)
- `id`: Primary key
- `user_id`: User being followed
- `follower_id`: User doing the following
- `created_at`: When follow occurred

#### 3. Controller

**FollowerController** (`app/Http/Controllers/FollowerController.php`)

**Method**: `toggle(Request $request, User $user): JsonResponse`
- Toggles follow/unfollow status
- Prevents self-following
- Returns updated counts and follow status
- Sends notification on follow
- Broadcasts real-time events

**Method**: `followers(User $user): Response`
- Returns Inertia page with list of users following the specified user
- Orders by most recent first (using pivot created_at)
- Returns UserResource collection

**Method**: `following(User $user): Response`
- Returns Inertia page with list of users the specified user follows
- Orders by most recent first (using pivot created_at)
- Returns UserResource collection

**Response Format** (toggle):
```json
{
    "action": "followed|unfollowed",
    "is_following": true|false,
    "followers_count": 10,
    "following_count": 5
}
```

#### 4. Notification

**UserFollowedNotification** (`app/Notifications/UserFollowedNotification.php`)
- Channels: `mail`, `database`
- Email subject: "{Name} started following you"
- Contains follower info and profile link
- Stored in database for in-app notifications

**Notification Data**:
```php
[
    'type' => 'user_followed',
    'follower_id' => int,
    'follower_name' => string,
    'follower_username' => string,
    'follower_profile_picture_url' => string|null,
    'action_url' => '/profile/{username}',
    'message' => '{Name} started following you',
]
```

#### 5. Broadcasting

**UserFollowed Event** (`app/Events/UserFollowed.php`)
- Implements `ShouldBroadcast`
- Channels: `users.{followed_user_id}` and `users.{follower_id}` (broadcasts to BOTH users)
- Event name: `UserFollowed`
- Broadcasts follower info and updated counts
- Real-time updates to both followed user and follower

**Broadcast Data**:
```php
[
    'followed_user_id' => int,
    'follower_id' => int,
    'is_following' => bool,
    'followers_count' => int,  // Followed user's followers count
    'following_count' => int,  // Follower's following count
]
```

#### 6. Resources

**UserResource Updates** (`app/Http/Resources/UserResource.php`)
- Added `followers_count`: Count of followers (when relation loaded)
- Added `following_count`: Count of users being followed (when relation loaded)
- Added `is_followed_by_auth`: Whether authenticated user follows this user

**NotificationResource Updates** (`app/Http/Resources/NotificationResource.php`)
- Added `UserFollowedNotification` to category mapping
- Category: `follow`

#### 7. Routes

**Route** (`routes/web.php`):
```php
Route::post('/users/{user}/follow', [FollowerController::class, 'toggle'])
    ->middleware(['auth', 'verified'])
    ->name('users.follow.toggle');
```

### Frontend Components

#### 1. FollowButton Component

**File**: `resources/js/components/app/FollowButton.vue`

**Props**:
- `userId: number` - User to follow/unfollow
- `isFollowing: boolean` - Current follow status
- `size?: 'default' | 'sm' | 'lg' | 'icon'` - Button size
- `variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'` - Button variant
- `showIcon?: boolean` - Show UserPlus/UserMinus icon (default: true)
- `showText?: boolean` - Show Follow/Unfollow text (default: true)

**Emits**:
- `update:isFollowing` - Emits new follow status
- `update:followersCount` - Emits updated follower count

**Features**:
- Loading state with spinner
- Optimistic UI updates
- Error handling
- Uses Lucide icons (UserPlus, UserMinus, Loader2)
- Async fetch to follow endpoint

**Usage**:
```vue
<FollowButton
    :user-id="user.id"
    :is-following="user.is_followed_by_auth || false"
    @update:followers-count="(count: number) => user.followers_count = count"
/>
```

#### 2. Profile Page Updates

**File**: `resources/js/pages/Profile.vue`

**Updates**:
- Display follower/following counts below username as clickable links
- Links navigate to followers/following list pages
- Follow button appears for other users' profiles
- Unfollow button appears only on profile page (not in posts)
- Real-time count updates via Echo broadcasting
- Echo listener mounted on component mount, cleaned up on unmount

**UI Structure**:
```vue
<div>
    <h1>{{ user.name }}</h1>
    <div class="followers-stats">
        <Link :href="`/users/${user.id}/followers`">
            {{ followersCount }} {{ followersCount === 1 ? 'Follower' : 'Followers' }}
        </Link>
        <Link :href="`/users/${user.id}/following`">
            {{ followingCount }} Following
        </Link>
    </div>
</div>
<FollowButton v-if="!isOwnProfile" ... />
```

**Echo Integration**:
```vue
<script setup lang="ts">
import Echo from '@/echo';
import { onMounted, onUnmounted } from 'vue';

const followersCount = ref(props.user.followers_count || 0);
const followingCount = ref(props.user.following_count || 0);

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

onUnmounted(() => {
    if (page.props.auth.user) {
        Echo.leave(`users.${page.props.auth.user.id}`);
    }
});
</script>
```

#### 3. Followers/Following List Page

**File**: `resources/js/pages/Followers.vue`

**Purpose**: Displays list of followers or following for a user

**Props**:
```typescript
interface Props {
    user: User;              // The profile user
    followers: User[];       // List of followers or following
    type: 'followers' | 'following';  // Page type
}
```

**Features**:
- Shows user avatars, names, and usernames
- Follow/unfollow buttons for each user (except self)
- Links to user profiles on click
- Back button to return to profile page
- Empty state when no followers/following
- Responsive design with hover effects

**UI Structure**:
```vue
<template>
    <AppSidebarLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Header with back button and title -->
            <Link :href="`/profile/${user.username}`">Back to Profile</Link>
            <h1>{{ type === 'followers' ? 'Followers' : 'Following' }}</h1>
            
            <!-- List of users -->
            <div v-for="follower in followers">
                <Link :href="`/profile/${follower.username}`">
                    <img :src="follower.profile_picture_url" />
                    <div>
                        <p>{{ follower.name }}</p>
                        <p>@{{ follower.username }}</p>
                    </div>
                </Link>
                <FollowButton 
                    v-if="follower.id !== currentUser.id"
                    :userId="follower.id"
                    :isFollowing="follower.is_followed_by_auth"
                />
            </div>
            
            <!-- Empty state -->
            <div v-if="followers.length === 0">
                <p>{{ type === 'followers' ? 'No followers yet' : 'Not following anyone' }}</p>
            </div>
        </div>
    </AppSidebarLayout>
</template>
```

#### 4. PostItem Component Updates

**File**: `resources/js/components/app/PostItem.vue`

**Updates**:
- ✅ Removed follow button from post cards
- ✅ Username and avatar link to user profile
- ✅ Hover effects on username (underline) and avatar (opacity)

**UI Structure**:
```vue
<div class="post-header">
    <Link :href="`/profile/${post.user.username}`">
        <img :src="post.user.profile_picture_url" />
    </Link>
    <Link :href="`/profile/${post.user.username}`">
        {{ post.user.name }}
    </Link>
</div>
```

#### 4. TypeScript Types

**File**: `resources/js/types/index.d.ts`

**User Interface Updates**:
```typescript
export interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    cover_url?: string | null;
    profile_picture_url?: string | null;
    followers_count?: number;          // NEW
    following_count?: number;          // NEW
    is_followed_by_auth?: boolean;     // NEW
}
```

## Feature Specifications

### 1. Follow Relationship
- **Type**: One-way (Twitter/Instagram style)
- **No Approval Required**: Following happens immediately
- **Self-Follow Prevention**: Users cannot follow themselves

### 2. Notifications
- **Channels**: Email + Database (in-app)
- **Trigger**: Every time a user is followed
- **Content**: Follower name, profile link, encouragement message
- **Real-time**: Broadcast event for live updates

### 3. UI/UX Placement

**Follow Button Locations**:
1. **Profile Page**: Full-featured button with follower/following counts
   - Only appears when viewing another user's profile
   - Shows "Follow" or "Unfollow" based on current status
   - Updates follower count in real-time

**Unfollow Button Locations**:
1. **Profile Page Only**: Users can unfollow from profile

**Removed from Posts**: 
- Follow button has been intentionally removed from post cards to reduce UI clutter
- Users should visit the profile page to follow/unfollow

### 4. Visibility & Privacy
- **Public Data**: Follower/following counts visible to all
- **Post Visibility**: New `followers_only` option for posts
- **No Remove Follower**: Users cannot remove their followers (Twitter-style)

### 5. Real-time Updates
- **Broadcasting**: Follow/unfollow events broadcast via Laravel Echo
- **Channel**: `users.{userId}`
- **Event**: `user.followed`
- **Updates**: Follower counts, follow status

## Usage Examples

### Backend - Check Follow Status
```php
$user = User::find(1);
$otherUser = User::find(2);

// Check if user follows otherUser
if ($user->isFollowing($otherUser)) {
    // User follows otherUser
}

// Check if user is followed by otherUser
if ($user->isFollowedBy($otherUser)) {
    // User is followed by otherUser
}

// Get followers
$followers = $user->followers; // Collection of User models

// Get following
$following = $user->following; // Collection of User models

// Counts
$followersCount = $user->followers()->count();
$followingCount = $user->following()->count();
```

### Backend - Manual Follow/Unfollow
```php
use App\Models\Follower;
use App\Notifications\UserFollowedNotification;
use App\Events\UserFollowed;

// Follow
Follower::create([
    'user_id' => $userToFollow->id,
    'follower_id' => auth()->id(),
]);

$userToFollow->notify(new UserFollowedNotification(auth()->user()));
broadcast(new UserFollowed($userToFollow, auth()->user(), true));

// Unfollow
Follower::where('user_id', $userToFollow->id)
    ->where('follower_id', auth()->id())
    ->delete();

broadcast(new UserFollowed($userToFollow, auth()->user(), false));
```

### Frontend - Using FollowButton
```vue
<script setup lang="ts">
import FollowButton from '@/components/app/FollowButton.vue';
import { ref } from 'vue';

const user = ref({
    id: 1,
    name: 'John Doe',
    followers_count: 100,
    is_followed_by_auth: false,
});

const handleFollowersUpdate = (count: number) => {
    user.value.followers_count = count;
};
</script>

<template>
    <div>
        <h1>{{ user.name }}</h1>
        <p>{{ user.followers_count }} followers</p>
        
        <!-- Default button -->
        <FollowButton
            :user-id="user.id"
            :is-following="user.is_followed_by_auth"
            @update:followers-count="handleFollowersUpdate"
        />
        
        <!-- Compact button (for posts) -->
        <FollowButton
            :user-id="user.id"
            :is-following="user.is_followed_by_auth"
            size="sm"
            variant="outline"
            :show-icon="false"
        />
        
        <!-- Icon only button -->
        <FollowButton
            :user-id="user.id"
            :is-following="user.is_followed_by_auth"
            size="icon"
            :show-text="false"
        />
    </div>
</template>
```

## Testing Checklist

- [x] Run migration successfully
- [ ] Test follow action from profile page
- [ ] Test unfollow action from profile page
- [ ] Test follow button in post cards
- [ ] Verify follower counts update correctly
- [ ] Verify following counts update correctly
- [ ] Check notification sent to followed user (database)
- [ ] Check notification email sent to followed user
- [ ] Verify self-follow prevention
- [ ] Test broadcasting (real-time updates)
- [ ] Verify notification appears in notifications page
- [ ] Test post visibility with `followers_only` option

## Next Steps / Future Enhancements

1. **Followers/Following Lists Pages**
   - Create dedicated pages to view followers and following
   - Add pagination for large lists
   - Add search/filter functionality

2. **Post Visibility Enforcement**
   - Update post queries to respect visibility settings
   - Filter `followers_only` posts in feeds
   - Show visibility indicator on posts

3. **Notification Preferences**
   - Allow users to disable follow notifications
   - Separate email vs in-app notification settings

4. **Block/Mute Features**
   - Allow users to block other users
   - Implement mute functionality
   - Respect blocks in follow actions

5. **Analytics**
   - Track follower growth over time
   - Show follower demographics
   - Activity insights for followed users

## Files Modified/Created

### Created Files
- `app/Http/Controllers/FollowerController.php` - Follow/unfollow toggle, followers/following lists
- `app/Notifications/UserFollowedNotification.php` - Email and database notifications
- `app/Events/UserFollowed.php` - Real-time broadcasting event
- `resources/js/components/app/FollowButton.vue` - Reusable follow button component
- `resources/js/pages/Followers.vue` - Followers/following list page
- `database/migrations/2025_10_25_000000_add_visibility_to_posts_table.php` - Post visibility feature
- `docs/FOLLOW_UNFOLLOW_FEATURE.md` (this file)

### Modified Files
- `app/Models/Follower.php` - Added relationships and fillable
- `app/Models/User.php` - Added follower relationships and helper methods
- `app/Models/Post.php` - Added visibility field and casts
- `app/Http/Resources/UserResource.php` - Added follower fields (followers_count, following_count, is_followed_by_auth)
- `app/Http/Resources/NotificationResource.php` - Added follow notification category
- `app/Http/Controllers/ProfileController.php` - Load follower counts
- `routes/web.php` - Added follow routes (toggle, followers list, following list)
- `resources/js/pages/Profile.vue` - Added follow button, clickable counts, Echo listener for real-time updates
- `resources/js/components/app/PostItem.vue` - Removed follow button, added profile links
- `resources/js/components/app/CommentItem.vue` - Added profile links on usernames/avatars
- `resources/js/types/index.d.ts` - Updated User interface with follower fields

## Dependencies

### Backend
- Laravel 12
- Laravel Notifications (built-in)
- Laravel Broadcasting (built-in)
- Existing database schema (followers table already exists)

### Frontend
- Vue 3 (Composition API)
- Inertia.js 2
- TypeScript
- Lucide Vue (icons)
- Existing UI components (Button)

## API Reference

### POST /users/{user}/follow

**Description**: Toggle follow/unfollow status for a user

**Authentication**: Required

**Parameters**:
- `{user}` - User ID (route parameter)

**Response**:
```json
{
    "action": "followed",
    "is_following": true,
    "followers_count": 101,
    "following_count": 50
}
```

**Error Responses**:
- `400` - Attempting to follow yourself
- `401` - Unauthenticated
- `404` - User not found

### GET /users/{user}/followers

**Description**: Get list of users following the specified user

**Authentication**: Required

**Parameters**:
- `{user}` - User ID (route parameter)

**Returns**: Inertia page `Followers` with:
- `user` - The profile user (UserResource)
- `followers` - Array of followers (UserResource collection)
- `type` - String 'followers'

### GET /users/{user}/following

**Description**: Get list of users the specified user is following

**Authentication**: Required

**Parameters**:
- `{user}` - User ID (route parameter)

**Returns**: Inertia page `Followers` with:
- `user` - The profile user (UserResource)
- `followers` - Array of following users (UserResource collection)
- `type` - String 'following'

## Real-Time Broadcasting

### Event: UserFollowed

**Channels**: 
- `users.{followed_user_id}` - Broadcasts to the user being followed
- `users.{follower_id}` - Broadcasts to the user doing the following

**Event Name**: `UserFollowed`

**Payload**:
```json
{
    "followed_user_id": 123,
    "follower_id": 456,
    "is_following": true,
    "followers_count": 101,
    "following_count": 50
}
```

**Frontend Listener** (Profile.vue):
```typescript
onMounted(() => {
    if (page.props.auth.user) {
        Echo.channel(`users.${page.props.auth.user.id}`)
            .listen('UserFollowed', (event: any) => {
                // Update followers count if someone followed/unfollowed us
                if (event.followed_user_id === page.props.auth.user.id) {
                    followersCount.value = event.followers_count;
                }
                // Update following count if we followed/unfollowed someone
                if (event.follower_id === page.props.auth.user.id) {
                    followingCount.value = event.following_count;
                }
            });
    }
});

onUnmounted(() => {
    if (page.props.auth.user) {
        Echo.leave(`users.${page.props.auth.user.id}`);
    }
});
```

## Troubleshooting

### Follow Button Not Appearing
- Check if `is_followed_by_auth` is loaded in UserResource
- Verify user is authenticated
- Ensure not viewing own profile

### Notifications Not Sending
- Check queue is running (`php artisan queue:listen`)
- Verify mail configuration in `.env`
- Check `notifications` table for database entries

### Follower Counts Not Updating
- Ensure `followers` and `following` relationships are loaded
- Check `loadCount(['followers', 'following'])` in controller
- Verify UserResource is returning counts

### Broadcasting Not Working
- Verify Laravel Echo is configured in `resources/js/echo.ts`
- Check broadcasting driver in `.env` (`BROADCAST_CONNECTION=pusher`)
- Verify Pusher credentials are correct
- Run `php artisan config:cache` after changing .env
- Ensure broadcast events are being dispatched with `broadcast(new UserFollowed(...))`
- Check browser console for Echo connection errors
- Verify queue worker is running for broadcasting

### Real-Time Counts Not Updating
- Check that Echo listener is mounted in Profile.vue
- Verify both users' channels are being broadcast to
- Check browser console for JavaScript errors
- Test with two different browsers/sessions
- Verify `UserFollowed` event implements `ShouldBroadcast`

### Followers/Following Pages Not Loading
- Check routes are registered: `php artisan route:list | grep follow`
- Verify Followers.vue page exists
- Check UserResource includes all necessary fields
- Verify `followers()` and `following()` methods exist in FollowerController

## Related Documentation
- `docs/NOTIFICATIONS_SYSTEM.md` - Notification system architecture
- `docs/FLASH_MESSAGES_FLOW.md` - Flash message patterns
- `docs/UI_UX_GUIDE.md` - UI/UX guidelines
