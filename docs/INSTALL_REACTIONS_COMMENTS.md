# Reactions & Comments Feature - Installation Guide

## Quick Start

This guide will help you complete the setup for the reactions and comments feature.

## Step 1: Install Required NPM Packages

```bash
npm install --save laravel-echo pusher-js
```

## Step 2: Install Pusher PHP SDK

```bash
composer require pusher/pusher-php-server
```

## Step 3: Configure Environment

Add to your `.env` file:

```bash
# Broadcasting Configuration
BROADCAST_DRIVER=pusher

# For Production (Pusher.com)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1

# Vite Environment Variables
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**OR** for local development with Laravel WebSockets:

```bash
# Install Laravel WebSockets first
composer require beyondcode/laravel-websockets
php artisan websockets:install

# Then configure
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

## Step 4: Create Echo Bootstrap File

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

## Step 5: Import Echo in Main App File

Update `resources/js/app.ts` (or wherever you initialize your app):

```typescript
import './echo'; // Add this line at the top
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
// ... rest of your imports
```

## Step 6: Update Broadcasting Composable

Update `resources/js/composables/usePostBroadcasting.ts`:

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

## Step 7: Run Migrations

```bash
php artisan migrate
```

## Step 8: Start Queue Worker

Broadcasting requires a queue worker to be running:

```bash
# In a separate terminal
php artisan queue:work
```

## Step 9: Start Development Server

```bash
# If using local WebSockets, start the WebSocket server first
php artisan websockets:serve

# Then start your development server (in another terminal)
composer dev
```

This will run both PHP server, queue listener, and Vite dev server.

## Step 10: Test the Feature

1. Open your app in the browser
2. Navigate to a post
3. Try clicking the Like button - you should see the reaction picker
4. Select a reaction
5. Open the same post in another browser/incognito window
6. React or comment from one window
7. Verify it updates in real-time in the other window

## Verification Checklist

- [ ] NPM packages installed (`laravel-echo`, `pusher-js`)
- [ ] Composer package installed (`pusher/pusher-php-server`)
- [ ] `.env` configured with broadcast settings
- [ ] Echo initialized in `resources/js/echo.ts`
- [ ] Echo imported in main app file
- [ ] `usePostBroadcasting.ts` updated with Echo implementation
- [ ] Migrations run successfully
- [ ] Queue worker running
- [ ] Dev server running
- [ ] Reactions work correctly
- [ ] Comments work correctly
- [ ] Real-time updates working in multiple browsers

## Common Issues

### Issue: "window.Echo is not defined"
**Solution**: Make sure you imported `./echo` in your main app file before initializing Vue.

### Issue: Real-time updates not working
**Solution**: 
1. Check queue worker is running: `php artisan queue:work`
2. Check browser console for WebSocket errors
3. Verify broadcast driver is set correctly in `.env`
4. For local WebSockets, ensure the server is running

### Issue: TypeScript errors on window.Echo
**Solution**: The global declaration in `echo.ts` should fix this. If not, create `resources/js/types/echo.d.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}

export {};
```

### Issue: Reactions/Comments not saving
**Solution**:
1. Check browser console for errors
2. Verify routes exist: `php artisan route:list | grep 'post.reaction\|comment'`
3. Check database connection
4. Ensure CSRF token is being sent (axios should handle this automatically with Laravel)

## Next Steps

After completing the installation:

1. Read `docs/REACTIONS_COMMENTS_FEATURE.md` for detailed documentation
2. Test all features thoroughly
3. Consider performance optimizations for production
4. Set up proper error handling and logging
5. Add monitoring for WebSocket connections

## Production Deployment

For production:

1. Use Pusher.com (recommended) or a self-hosted solution like Laravel WebSockets with Supervisor
2. Configure proper SSL certificates
3. Set up queue workers with Supervisor for reliability
4. Monitor WebSocket connection health
5. Implement rate limiting on reaction/comment endpoints
6. Set up proper logging and error tracking

## Support

If you encounter issues:
1. Check the troubleshooting section in `docs/REACTIONS_COMMENTS_FEATURE.md`
2. Review Laravel Broadcasting documentation: https://laravel.com/docs/broadcasting
3. Check Laravel Echo documentation: https://github.com/laravel/echo
