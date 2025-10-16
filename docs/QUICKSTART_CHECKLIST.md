# 🚀 Quick Start Checklist - Reactions & Comments

Use this checklist to get the reactions and comments feature up and running quickly.

## ✅ Pre-Installation Checklist

- [ ] Running Laravel 12+
- [ ] Running Vue 3 with TypeScript
- [ ] Have Inertia.js configured
- [ ] Database connection working
- [ ] Node.js and npm installed
- [ ] Composer installed

## 📦 Installation Steps

### Step 1: Install Dependencies
```bash
# Install NPM packages
npm install --save laravel-echo pusher-js

# Install PHP package
composer require pusher/pusher-php-server
```
- [ ] NPM packages installed
- [ ] PHP package installed

### Step 2: Run Database Migrations
```bash
php artisan migrate
```
- [ ] Migrations run successfully
- [ ] Tables created: `post_reactions`, `comments`

### Step 3: Configure Broadcasting

**Choose ONE option:**

#### Option A: Pusher (Recommended for Production)
1. [ ] Created Pusher account at https://pusher.com
2. [ ] Created new app in Pusher dashboard
3. [ ] Copied credentials to `.env`:
```bash
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### Option B: Laravel WebSockets (Local Development)
1. [ ] Installed Laravel WebSockets: `composer require beyondcode/laravel-websockets`
2. [ ] Published config: `php artisan websockets:install`
3. [ ] Updated `.env`:
```bash
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="local"
VITE_PUSHER_APP_CLUSTER="mt1"
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
```

### Step 4: Create Echo Bootstrap File

Create `resources/js/echo.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}

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

export default window.Echo;
```

- [ ] Created `resources/js/echo.ts`

### Step 5: Import Echo in Main App

Update `resources/js/app.ts` (or your main entry file):

```typescript
import './echo'; // Add this at the top
// ... rest of imports
```

- [ ] Added import to main app file

### Step 6: Update Broadcasting Composable

Replace the content of `resources/js/composables/usePostBroadcasting.ts`:

```typescript
import type { Comment, PostReactions } from '@/types';
import { ref } from 'vue';

export function usePostBroadcasting(postId: number) {
    const isConnected = ref(false);

    const listenForReactions = (callback: (reactions: PostReactions) => void) => {
        if (!window.Echo) {
            console.warn('Laravel Echo is not initialized');
            return;
        }

        window.Echo.channel(`posts.${postId}`)
            .listen('.post.reacted', (event: any) => {
                callback(event.reactions);
            });
        isConnected.value = true;
    };

    const listenForComments = (callback: (comment: Comment) => void) => {
        if (!window.Echo) {
            console.warn('Laravel Echo is not initialized');
            return;
        }

        window.Echo.channel(`posts.${postId}`)
            .listen('.comment.created', (event: any) => {
                callback(event.comment);
            });
    };

    const disconnect = () => {
        if (!window.Echo) return;
        
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

- [ ] Updated `usePostBroadcasting.ts`

## 🏃 Running the Application

### Terminal 1: Queue Worker (Required!)
```bash
php artisan queue:work
```
- [ ] Queue worker running

### Terminal 2: WebSocket Server (if using Laravel WebSockets)
```bash
php artisan websockets:serve
```
- [ ] WebSocket server running (skip if using Pusher)

### Terminal 3: Development Server
```bash
composer dev
# OR separately:
# php artisan serve
# npm run dev
```
- [ ] Development server running

## 🧪 Testing

### Basic Functionality Tests

1. **Test Reactions**
   - [ ] Open app in browser
   - [ ] Navigate to a post
   - [ ] Click "Like" button
   - [ ] Reaction picker appears
   - [ ] Click an emoji (e.g., Love ❤️)
   - [ ] Button updates to show "Love"
   - [ ] Reaction count increases
   - [ ] Click same reaction again
   - [ ] Reaction is removed

2. **Test Comments**
   - [ ] Click "Comment" button
   - [ ] Comment section expands
   - [ ] Type a comment
   - [ ] Click "Post" or press Enter
   - [ ] Comment appears in list
   - [ ] Comment count updates

3. **Test Long Comments**
   - [ ] Post a comment over 100 characters
   - [ ] Verify "See More" button appears
   - [ ] Click "See More"
   - [ ] Full comment expands
   - [ ] "Show Less" button appears

4. **Test Pagination**
   - [ ] Post at least 6 comments
   - [ ] Only 5 latest appear initially
   - [ ] "Load More" button visible
   - [ ] Click "Load More"
   - [ ] Older comments load

5. **Test Permissions**
   - [ ] Post a comment
   - [ ] Delete button appears on hover
   - [ ] Click delete
   - [ ] Comment is removed

### Real-time Tests

**You'll need TWO browsers/windows for this:**

6. **Real-time Reactions**
   - [ ] Open same post in two browsers
   - [ ] React in browser 1
   - [ ] Reaction updates instantly in browser 2
   - [ ] Counts match in both browsers

7. **Real-time Comments**
   - [ ] Same post in two browsers
   - [ ] Post comment in browser 1
   - [ ] Comment appears instantly in browser 2
   - [ ] Comment count updates in both

## 🐛 Troubleshooting

If something doesn't work, check:

### Reactions/Comments Not Saving
- [ ] Check browser console for errors
- [ ] Verify CSRF token (should be automatic with Laravel)
- [ ] Check database connection
- [ ] Look at `storage/logs/laravel.log`

### Real-time Not Working
- [ ] Queue worker is running (`php artisan queue:work`)
- [ ] For Pusher: Check credentials in `.env`
- [ ] For WebSockets: Server running on port 6001
- [ ] Check browser console for WebSocket errors
- [ ] Look for connection messages in console

### TypeScript Errors
- [ ] Run `npm run dev` to rebuild
- [ ] Check all imports are correct
- [ ] Verify `echo.ts` has global declarations

### "window.Echo is not defined"
- [ ] Verify `./echo` is imported in main app file
- [ ] Import should be at the TOP before Vue initialization
- [ ] Rebuild with `npm run dev`

## 📊 Verification

All features working when:

- [ ] ✅ Can select all 6 reaction types
- [ ] ✅ Reaction count displays correctly
- [ ] ✅ Current user's reaction is highlighted
- [ ] ✅ Can toggle reactions on/off
- [ ] ✅ Comments can be posted
- [ ] ✅ Long comments show "See More"
- [ ] ✅ Can load more comments
- [ ] ✅ Can delete own comments
- [ ] ✅ Reactions update in real-time
- [ ] ✅ Comments appear in real-time
- [ ] ✅ No console errors
- [ ] ✅ No TypeScript errors
- [ ] ✅ Mobile responsive

## 🎉 Success!

If all items are checked, you have successfully implemented the reactions and comments feature!

## 📚 Next Steps

Now that it's working:

1. [ ] Read `docs/REACTIONS_COMMENTS_FEATURE.md` for full documentation
2. [ ] Review `docs/UI_UX_GUIDE.md` for UI details
3. [ ] Test thoroughly with real users
4. [ ] Monitor performance and WebSocket connections
5. [ ] Consider future enhancements (nested replies, editing, etc.)

## 💡 Pro Tips

1. **Development**: Use Laravel WebSockets for easier local testing
2. **Production**: Use Pusher for reliability and scaling
3. **Monitoring**: Watch Pusher dashboard for connection issues
4. **Queue**: Use supervisor in production for queue workers
5. **Caching**: Consider caching reaction counts for high-traffic posts

## 🆘 Need Help?

Refer to:
- `docs/INSTALL_REACTIONS_COMMENTS.md` - Detailed installation
- `docs/REACTIONS_COMMENTS_FEATURE.md` - Complete documentation
- `docs/UI_UX_GUIDE.md` - UI/UX specifications
- Laravel Broadcasting Docs: https://laravel.com/docs/broadcasting
- Laravel Echo Docs: https://github.com/laravel/echo
