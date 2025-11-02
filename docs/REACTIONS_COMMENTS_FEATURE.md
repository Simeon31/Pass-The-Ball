# Post Reactions and Comments Feature

## Overview

This document describes the implementation of a comprehensive reactions and comments system for posts in the Pass The Ball social media platform. The implementation follows Facebook/Instagram patterns with real-time updates via WebSockets.

## Features

### Reactions System

- **6 Reaction Types**: Like 👍, Love ❤️, Haha 😂, Wow 😮, Sad 😢, Angry 😠
- **Reaction Picker**: HeadlessUI Popover with animated emoji selector
- **Toggle Behavior**: Click same reaction to remove, click different to change
- **Visual Feedback**: Shows current user's reaction with colored icon
- **Reaction Count**: Displays total reactions and breakdown by type
- **Real-time Updates**: All users see reactions update live via WebSockets

### Comments System

- **Threaded Comments**: Top-level comments only (easily extensible for nested replies)
- **Character Limit**: Max 2000 characters per comment
- **See More**: Comments over 100 characters show truncated with "See More" button
- **Lazy Loading**: Shows latest 5 comments initially with "Load More" button
- **Real-time Updates**: New comments appear instantly for all users
- **Delete Permission**: Users can delete their own comments, post authors can delete any comment on their posts
- **Comment Count**: Shows total number of comments

## Architecture

### Backend

#### Models

**PostReaction** (`app/Models/PostReaction.php`)
- Fields: `post_id`, `user_id`, `type`, `created_at`
- Constants for reaction types
- Relationships: `post()`, `user()`

**Comment** (`app/Models/Comment.php`)
- Fields: `post_id`, `user_id`, `comment`, `created_at`, `updated_at`
- Relationships: `post()`, `user()`

**Post** (updated `app/Models/Post.php`)
- Added relationships: `reactions()`, `comments()`

#### Controllers

**PostReactionController** (`app/Http/Controllers/PostReactionController.php`)
- `toggle()`: Add/update/remove reaction with broadcast

**CommentController** (`app/Http/Controllers/CommentController.php`)
- `store()`: Create new comment with broadcast
- `index()`: Get paginated comments (5 per page)
- `destroy()`: Delete comment with permission check

#### Resources

**PostResource** (updated)
- Includes reactions summary, current user's reaction, latest 5 comments, total counts

**CommentResource**
- Returns comment data with user information

#### Routes

```php
// Reactions
POST /post/{post}/reaction

// Comments
POST /post/{post}/comment
GET /post/{post}/comments?page=1&per_page=5
DELETE /comment/{comment}
```

#### Broadcasting Events

**PostReacted** (`app/Events/PostReacted.php`)
- Channel: `posts.{postId}`
- Event: `post.reacted`
- Payload: `{ post_id, reactions }`

**CommentCreated** (`app/Events/CommentCreated.php`)
- Channel: `posts.{postId}`
- Event: `comment.created`
- Payload: `{ post_id, comment }`

### Frontend

#### Components

**ReactionPicker.vue** (`resources/js/components/app/ReactionPicker.vue`)
- HeadlessUI Popover for reaction selection
- Shows current reaction with colored icon
- Animated hover effects on emoji buttons
- Tooltips for reaction labels

**CommentSection.vue** (`resources/js/components/app/CommentSection.vue`)
- Comment input with submit button
- Comment list with lazy loading
- "Load More" button for pagination
- Handles WebSocket updates via exposed method

**CommentItem.vue** (`resources/js/components/app/CommentItem.vue`)
- Individual comment display
- "See More" for long comments (>100 chars)
- Delete button (with permissions)
- User avatar and timestamp

**PostItem.vue** (updated)
- Integrated ReactionPicker and CommentSection
- Shows reaction summary with emojis
- Toggle comments visibility
- WebSocket listener setup in `onMounted`

#### TypeScript Types

```typescript
type ReactionType = 'like' | 'love' | 'haha' | 'wow' | 'sad' | 'angry';

interface ReactionSummary {
    [key: string]: number;
}

interface PostReactions {
    summary: ReactionSummary;
    total: number;
    current_user_reaction: ReactionType | null;
}

interface Comment {
    id: number;
    post_id: number;
    comment: string;
    user: User;
    created_at: string;
    updated_at: string;
}

interface PostComments {
    data: Comment[];
    total: number;
}
```

#### Composable

**usePostBroadcasting.ts** (`resources/js/composables/usePostBroadcasting.ts`)
- Manages WebSocket connections per post
- Listeners for reactions and comments
- Auto-cleanup on component unmount

## Database Schema

### post_reactions Table
```sql
id: bigint (primary key)
post_id: bigint (foreign key to posts)
user_id: bigint (foreign key to users)
type: string (like, love, haha, wow, sad, angry)
created_at: timestamp
```

**Indexes**: Composite on `(post_id, user_id)` for performance

### comments Table
```sql
id: bigint (primary key)
post_id: bigint (foreign key to posts)
user_id: bigint (foreign key to users)
comment: text
created_at: timestamp
updated_at: timestamp
```

**Indexes**: On `post_id` for efficient querying

## Setup Instructions

### 1. Run Migrations

```bash
php artisan migrate
```

The following migrations should already exist:
- `2025_09_30_182052_create_post_reactions_table.php`
- `2025_09_30_182244_create_comments_table.php`

### 2. Configure Broadcasting

#### Option A: Using Pusher (Recommended for Production)

1. Create Pusher account at https://pusher.com
2. Update `.env`:
```bash
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

3. Install Pusher PHP SDK:
```bash
composer require pusher/pusher-php-server
```

4. Install Laravel Echo and Pusher JS:
```bash
npm install --save laravel-echo pusher-js
```

5. Configure Laravel Echo (`resources/js/bootstrap.ts` or `app.ts`):
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

6. Add to `.env`:
```bash
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

7. Update `usePostBroadcasting.ts` to use Echo:
```typescript
export function usePostBroadcasting(postId: number) {
    const isConnected = ref(false);

    const listenForReactions = (callback: (reactions: PostReactions) => void) => {
        window.Echo.channel(`posts.${postId}`)
            .listen('.post.reacted', (event: any) => {
                callback(event.reactions);
            });
        isConnected.value = true;
    };

    const listenForComments = (callback: (comment: Comment) => void) => {
        window.Echo.channel(`posts.${postId}`)
            .listen('.comment.created', (event: any) => {
                callback(event.comment);
            });
    };

    const disconnect = () => {
        window.Echo.leave(`posts.${postId}`);
        isConnected.value = false;
    };

    return {
        isConnected,
        listenForReactions,
        listenForComments,
        disconnect,
    };
}
```

#### Option B: Local Development (Laravel WebSockets)

For local development without Pusher:

1. Install Laravel WebSockets:
```bash
composer require beyondcode/laravel-websockets
php artisan websockets:install
```

2. Update `.env`:
```bash
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
```

3. Configure for local:
```bash
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
```

4. Start WebSocket server:
```bash
php artisan websockets:serve
```

### 3. Queue Configuration

Broadcasting requires queue workers:

```bash
# Update .env
QUEUE_CONNECTION=database

# Run queue worker
php artisan queue:work
```

### 4. Generate TypeScript Routes

After adding new routes, regenerate:

```bash
npm run dev
```

This runs Laravel Wayfinder to generate route helpers.

## Usage

### React to a Post

```typescript
import { create as toggleReaction } from '@/routes/post.reaction';

// In component
const handleReaction = async (type: ReactionType) => {
    await axios.post(`/post/${postId}/reaction`, { type });
};
```

### Add a Comment

```typescript
import { create as createComment } from '@/routes/post.comment';

const submitComment = async () => {
    await axios.post(`/post/${postId}/comment`, {
        comment: commentText.value,
    });
};
```

### Load More Comments

```typescript
const loadMore = async () => {
    const response = await axios.get(`/post/${postId}/comments`, {
        params: { page: nextPage, per_page: 5 },
    });
};
```

## Performance Considerations

1. **Database Indexes**: Ensure indexes on `post_id` and `user_id` in both tables
2. **Eager Loading**: PostResource uses `with('user')` to prevent N+1 queries
3. **Pagination**: Comments load 5 at a time to reduce payload
4. **Broadcasting Queue**: All broadcasts go through queue to avoid blocking
5. **Channel Isolation**: Each post has its own channel for targeted updates

## Future Enhancements

1. **Nested Replies**: Add `parent_id` to comments table for threading
2. **Reaction Animations**: Add CSS animations when reactions change
3. **Comment Editing**: Add edit functionality with update timestamp
4. **Mention System**: @username mentions with notifications
5. **Media Comments**: Allow images/GIFs in comments
6. **Reaction Details**: Modal showing who reacted with what
7. **Comment Likes**: Add reactions to comments themselves
8. **Real-time Typing Indicators**: Show "User is typing..." status

## Testing

### Manual Testing Checklist

- [ ] React to post with each reaction type
- [ ] Change reaction from one type to another
- [ ] Remove reaction by clicking same type
- [ ] Verify reaction count updates
- [ ] Post comment and verify it appears
- [ ] Post long comment (>100 chars) and verify "See More"
- [ ] Click "See More" to expand long comment
- [ ] Load more comments with pagination
- [ ] Delete own comment
- [ ] Verify post owner can delete any comment
- [ ] Open in two browsers and verify real-time updates
- [ ] Verify reactions update in real-time
- [ ] Verify new comments appear in real-time

### Unit Tests

Create tests in `tests/Feature/`:

```php
// tests/Feature/PostReactionTest.php
// tests/Feature/CommentTest.php
```

## Troubleshooting

### Reactions not saving
- Check database connection
- Verify CSRF token is included in axios requests
- Check browser console for errors

### Comments not loading
- Verify route exists: `php artisan route:list | grep comment`
- Check PostResource includes comments data
- Verify pagination parameters

### Real-time updates not working
- Ensure queue worker is running: `php artisan queue:work`
- Check broadcast driver is configured correctly
- For Pusher: verify credentials in `.env`
- For local WebSockets: ensure server is running on port 6001
- Check browser console for WebSocket connection errors

### "See More" not appearing
- Verify comment is over 100 characters
- Check `isLongComment` computed property in CommentItem.vue

## Credits

Implementation follows patterns from:
- Facebook reactions system
- Instagram comments UI
- Telegram's smooth animations
- Laravel best practices for broadcasting
- Inertia.js SPA patterns
