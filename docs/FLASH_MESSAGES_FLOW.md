# Flash Messages Flow - Complete Guide

**Application:** Pass The Ball (Laravel + Inertia.js + Vue 3)  
**Date:** October 2025  
**Pattern:** Laravel Session Flash → Inertia Middleware → Vue Composable

---

## Overview

This document explains the complete flow of flash messages in the application, from the moment they're created on the backend to when they're displayed and dismissed on the frontend.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         BACKEND (Laravel)                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Controller Action                                           │
│     ┌─────────────────────────────────────────┐                │
│     │ PostController::store()                  │                │
│     │ ProfileController::updateImage()         │                │
│     │                                          │                │
│     │ Post::create($data);                    │                │
│     │                                          │                │
│     │ return back()->with('status', 'Success!');│               │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  2. Session Storage                                             │
│     ┌─────────────────────────────────────────┐                │
│     │ Session::flash('status', 'Success!')    │                │
│     │                                          │                │
│     │ Stored in: storage/framework/sessions/  │                │
│     │ (or Redis/Database based on config)     │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  3. Redirect Response                                           │
│     ┌─────────────────────────────────────────┐                │
│     │ HTTP 302 Redirect                        │                │
│     │ Location: /welcome                       │                │
│     │ Set-Cookie: laravel_session=...         │                │
│     └─────────────────┬───────────────────────┘                │
└─────────────────────────┼───────────────────────────────────────┘
                          │
                          │ HTTP Response
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MIDDLEWARE (Inertia)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  4. HandleInertiaRequests::share()                              │
│     ┌─────────────────────────────────────────┐                │
│     │ public function share(Request $request) │                │
│     │ {                                        │                │
│     │     return [                             │                │
│     │         'flash' => [                     │                │
│     │             'status' => $request         │                │
│     │                 ->session()              │                │
│     │                 ->get('status'),         │                │
│     │         ],                               │                │
│     │     ];                                   │                │
│     │ }                                        │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│     ⚠️  IMPORTANT: Session flash is consumed here!             │
│     The flash message is read and removed from session.         │
│                       │                                         │
│                       ▼                                         │
│  5. Inertia Response                                            │
│     ┌─────────────────────────────────────────┐                │
│     │ {                                        │                │
│     │   "component": "Welcome",                │                │
│     │   "props": {                             │                │
│     │     "flash": {                           │                │
│     │       "status": "Success!"               │                │
│     │     },                                   │                │
│     │     "auth": { ... },                     │                │
│     │     "posts": [ ... ]                     │                │
│     │   }                                      │                │
│     │ }                                        │                │
│     └─────────────────┬───────────────────────┘                │
└─────────────────────────┼───────────────────────────────────────┘
                          │
                          │ JSON Response
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                     FRONTEND (Vue 3)                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  6. Inertia Client                                              │
│     ┌─────────────────────────────────────────┐                │
│     │ Inertia.js receives response             │                │
│     │ Updates $page.props globally             │                │
│     │                                          │                │
│     │ $page.props.flash.status = "Success!"   │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  7. Component Setup (Welcome.vue)                               │
│     ┌─────────────────────────────────────────┐                │
│     │ <script setup lang="ts">                │                │
│     │ import { useFlashMessage }              │                │
│     │   from '@/composables/useFlashMessage'; │                │
│     │                                          │                │
│     │ const { showMessage, message, dismiss } │                │
│     │   = useFlashMessage('status', 5000);    │                │
│     │ </script>                                │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  8. Composable Initialization                                   │
│     ┌─────────────────────────────────────────┐                │
│     │ useFlashMessage('status', 5000)         │                │
│     │                                          │                │
│     │ const showMessage = ref(false)          │                │
│     │ let messageTimeout = null               │                │
│     │                                          │                │
│     │ watch(                                   │                │
│     │   () => usePage().props.flash.status,   │                │
│     │   (newMessage) => { ... },              │                │
│     │   { immediate: true } ← Runs on mount!  │                │
│     │ )                                        │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  9. Watcher Triggered (immediate: true)                         │
│     ┌─────────────────────────────────────────┐                │
│     │ Detects: "Success!" in flash.status     │                │
│     │                                          │                │
│     │ if (newMessage) {                       │                │
│     │   showMessage.value = true              │                │
│     │   setTimeout(() => {                    │                │
│     │     showMessage.value = false           │                │
│     │   }, 5000)                               │                │
│     │ }                                        │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  10. Template Rendering                                         │
│     ┌─────────────────────────────────────────┐                │
│     │ <Transition>                             │                │
│     │   <div v-if="message() && showMessage"> │                │
│     │     <CheckCircleIcon />                 │                │
│     │     <p>{{ message() }}</p>  ← "Success!"│                │
│     │     <button @click="dismiss">×</button> │                │
│     │   </div>                                │                │
│     │ </Transition>                            │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  11. User Sees Message                                          │
│     ┌─────────────────────────────────────────┐                │
│     │  ┌───────────────────────────────────┐  │                │
│     │  │ ✓ Success!                      × │  │                │
│     │  └───────────────────────────────────┘  │                │
│     │                                          │                │
│     │  Green banner with success message      │                │
│     │  Auto-fades after 5 seconds             │                │
│     └─────────────────┬───────────────────────┘                │
│                       │                                         │
│                       ▼                                         │
│  12. Auto-Hide (After 5 seconds)                                │
│     ┌─────────────────────────────────────────┐                │
│     │ setTimeout callback fires                │                │
│     │ showMessage.value = false               │                │
│     │                                          │                │
│     │ Vue reactivity system triggers           │                │
│     │ Transition leave animation               │                │
│     │ Message fades out                        │                │
│     └─────────────────────────────────────────┘                │
│                                                                 │
│  OR: Manual Dismiss                                             │
│     ┌─────────────────────────────────────────┐                │
│     │ User clicks × button                     │                │
│     │ dismiss() called                         │                │
│     │ clearTimeout(messageTimeout)            │                │
│     │ showMessage.value = false               │                │
│     └─────────────────────────────────────────┘                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Detailed Step-by-Step Flow

### Backend: Setting the Flash Message

#### Step 1: Controller Action
```php
// app/Http/Controllers/PostController.php
public function store(StorePostRequest $request)
{
    $data = $request->validated();
    Post::create($data);

    // Flash message set here
    return back()->with('status', 'Post created successfully.');
}
```

**What happens:**
- User submits a form (create post, upload image, etc.)
- Controller processes the request
- Data is saved to database
- Flash message is set using `->with('status', 'Message')`

#### Step 2: Laravel Session Storage
```php
// Laravel internally does:
Session::flash('status', 'Post created successfully.');
```

**What happens:**
- Message is stored in the session (temporary storage)
- Session can be stored in:
  - Files: `storage/framework/sessions/`
  - Database: `sessions` table
  - Redis: In-memory cache
  - Memcached: In-memory cache
- Session is tied to user via cookie (`laravel_session`)

**Key Point:** Flash messages are **temporary** and only available for the **next request**. After being read, they're automatically deleted.

#### Step 3: Redirect Response
```php
// HTTP Response
HTTP/1.1 302 Found
Location: /welcome
Set-Cookie: laravel_session=eyJpdiI6...
```

**What happens:**
- User's browser receives redirect
- Session cookie is sent with response
- Browser navigates to new URL

---

### Middleware: Sharing Flash with Inertia

#### Step 4: HandleInertiaRequests Middleware
```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'flash' => [
            'status' => $request->session()->get('status'),
        ],
    ];
}
```

**What happens:**
- Inertia middleware intercepts the request
- `share()` method is called on **every request**
- Flash message is retrieved from session via `session()->get('status')`
- ⚠️ **CRITICAL:** Session flash is **consumed** here (removed from session)
- Flash data is added to Inertia's response props

**Why this matters:**
- Without this sharing, Vue components can't access flash messages
- Flash is consumed on first read, so subsequent requests won't have it
- This is why we had to move from controller-level props to middleware sharing

#### Step 5: Inertia JSON Response
```json
{
  "component": "Welcome",
  "props": {
    "flash": {
      "status": "Post created successfully."
    },
    "auth": {
      "user": { ... }
    },
    "posts": [ ... ]
  },
  "url": "/welcome",
  "version": "abc123"
}
```

**What happens:**
- Inertia serializes the shared data to JSON
- Response is sent to browser
- Inertia.js client intercepts the response

---

### Frontend: Displaying the Flash Message

#### Step 6: Inertia Client Updates Props
```javascript
// Inertia.js (automatic)
$page.props = {
  flash: {
    status: "Post created successfully."
  },
  auth: { ... },
  posts: [ ... ]
}
```

**What happens:**
- Inertia.js client receives JSON response
- Global `$page.props` is updated
- All components using `usePage()` have access to new props
- Vue's reactivity system detects the change

#### Step 7: Component Setup
```vue
<!-- resources/js/pages/Welcome.vue -->
<script setup lang="ts">
import { useFlashMessage } from '@/composables/useFlashMessage';

// Initialize flash message handling
const { showMessage, message, dismiss } = useFlashMessage('status', 5000);
</script>
```

**What happens:**
- Component is mounted/rendered
- `useFlashMessage` composable is called
- Composable returns reactive refs and functions

#### Step 8: Composable Initialization
```typescript
// resources/js/composables/useFlashMessage.ts
export function useFlashMessage(flashKey: string = 'status', autoHideDuration: number = 5000) {
    const showMessage = ref(false);
    let messageTimeout: ReturnType<typeof setTimeout> | null = null;

    // Watch for flash messages
    watch(
        () => (usePage().props.flash as any)?.[flashKey],
        (newMessage) => {
            // Clear existing timeout
            if (messageTimeout) {
                clearTimeout(messageTimeout);
                messageTimeout = null;
            }

            if (newMessage) {
                showMessage.value = true;
                
                // Auto-hide after duration
                messageTimeout = setTimeout(() => {
                    showMessage.value = false;
                    messageTimeout = null;
                }, autoHideDuration);
            } else {
                showMessage.value = false;
            }
        },
        { immediate: true } // ← Runs immediately on mount!
    );

    return { showMessage, message: () => (usePage().props.flash as any)?.[flashKey], dismiss };
}
```

**What happens:**
- Creates reactive `showMessage` ref (controls visibility)
- Sets up watcher on `$page.props.flash.status`
- `{ immediate: true }` causes watcher to run immediately on mount
- If flash message exists, `showMessage` is set to `true`
- Auto-hide timer is started (5000ms by default)

#### Step 9: Watcher Triggered
```typescript
// Watcher detects: "Post created successfully."
(newMessage) => {
    if (newMessage) {
        showMessage.value = true;           // Make visible
        
        messageTimeout = setTimeout(() => {
            showMessage.value = false;      // Auto-hide after 5s
            messageTimeout = null;
        }, 5000);
    }
}
```

**What happens:**
- Watcher fires because `flash.status` has a value
- `showMessage.value = true` triggers Vue reactivity
- Template re-renders to show the message
- Timer is set to auto-hide after 5 seconds

#### Step 10: Template Rendering
```vue
<template>
    <Transition 
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform translate-y-2"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        
        <div v-if="message() && showMessage"
             class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <CheckCircleIcon class="w-5 h-5 text-green-600 mr-2" />
                    <p class="text-sm font-medium text-green-800">
                        {{ message() }}
                    </p>
                </div>
                <button @click="dismiss" class="text-green-600 hover:text-green-800">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>
        </div>
    </Transition>
</template>
```

**What happens:**
- `v-if="message() && showMessage"` evaluates to `true`
- Transition `enter` animation plays (fade in + slide down)
- Green success banner appears with message
- Close button is rendered with `@click="dismiss"` handler

#### Step 11: User Interaction

**Scenario A: Auto-Hide (Default)**
```
Time: 0ms    → Message appears with enter animation
Time: 300ms  → Animation complete, message fully visible
Time: 5000ms → setTimeout callback fires
             → showMessage.value = false
             → Transition leave animation plays
Time: 5200ms → Message completely hidden
```

**Scenario B: Manual Dismiss**
```javascript
// User clicks × button
const dismiss = () => {
    showMessage.value = false;      // Hide immediately
    if (messageTimeout) {
        clearTimeout(messageTimeout); // Cancel auto-hide timer
        messageTimeout = null;
    }
};
```

---

## Key Concepts Explained

### 1. Session Flash Lifecycle

**Flash messages are ONE-TIME only:**

```php
// Request 1: Set flash
Session::flash('status', 'Success!');

// Request 2: Read flash
$message = Session::get('status'); // "Success!"
// Flash is now CONSUMED (deleted)

// Request 3: Try to read again
$message = Session::get('status'); // null (already consumed)
```

**Visual Timeline:**
```
POST /post (create post)
  ↓
Flash set: status="Success!"
  ↓
302 Redirect → GET /welcome
  ↓
Middleware reads flash: "Success!"  ← Flash consumed here
  ↓
Inertia renders page with flash in props
  ↓
User sees message
  ↓
--- Next Request ---
  ↓
GET /profile
  ↓
Middleware reads flash: null  ← Flash already consumed
```

### 2. Why Middleware Sharing Is Necessary

**Without Middleware Sharing:**
```php
// ❌ Bad: Controller-level sharing
public function index() {
    return Inertia::render('Welcome', [
        'status' => session('status'), // Consumed here
    ]);
}
```

**Problems:**
- Flash consumed at controller level
- Subsequent Inertia requests won't have the flash
- Message won't appear on navigation without full page reload

**With Middleware Sharing:**
```php
// ✅ Good: Middleware-level sharing
public function share(Request $request): array {
    return [
        'flash' => [
            'status' => $request->session()->get('status'),
        ],
    ];
}
```

**Benefits:**
- Flash shared globally on every Inertia request
- Available to all components via `$page.props.flash`
- Consistent behavior across all pages

### 3. The Role of `{ immediate: true }`

```typescript
watch(
    () => usePage().props.flash.status,
    (newMessage) => { ... },
    { immediate: true } // ← CRITICAL!
);
```

**Without `immediate: true`:**
```
Component mounts
  ↓
Watcher created (but doesn't run yet)
  ↓
User waits... nothing happens
  ↓
Flash message expires (5s later)
  ↓
User never saw the message! ❌
```

**With `immediate: true`:**
```
Component mounts
  ↓
Watcher created AND runs immediately
  ↓
Detects flash message
  ↓
Shows message instantly ✅
  ↓
Auto-hide timer starts
```

### 4. Reactivity Chain

```
Backend sets flash
  ↓
Middleware shares flash
  ↓
Inertia updates $page.props
  ↓
usePage() reactive getter triggers
  ↓
Watch callback fires
  ↓
showMessage.value changes
  ↓
Vue reactivity updates DOM
  ↓
User sees message
```

---

## Complete Code Flow Example

### Scenario: User Creates a Post

**1. User fills form and clicks "Post"**
```vue
<!-- CreatePost.vue -->
<button @click="submit">Post</button>

<script>
function submit() {
    newPostForm.post(createPost.url(), {
        onSuccess: () => {
            newPostForm.reset();
            resetComposer();
        }
    });
}
</script>
```

**2. Inertia sends POST request**
```http
POST /post HTTP/1.1
Content-Type: application/json
Cookie: laravel_session=...

{
  "body": "Hello, world!",
  "attachments": null
}
```

**3. Controller handles request**
```php
// PostController::store()
$data = $request->validated();
Post::create($data); // Save to database

return back()->with('status', 'Post created successfully.');
```

**4. Laravel session stores flash**
```php
// Internally
Session::flash('status', 'Post created successfully.');
```

**5. Redirect response sent**
```http
HTTP/1.1 302 Found
Location: /welcome
Set-Cookie: laravel_session=...
X-Inertia: true
```

**6. Inertia intercepts redirect**
```javascript
// Inertia.js makes GET request to /welcome
GET /welcome
X-Inertia: true
X-Inertia-Version: abc123
```

**7. Middleware shares flash**
```php
// HandleInertiaRequests::share()
return [
    'flash' => [
        'status' => 'Post created successfully.', // ← Consumed here
    ],
];
```

**8. Inertia response**
```json
{
  "component": "Welcome",
  "props": {
    "flash": {
      "status": "Post created successfully."
    }
  }
}
```

**9. Composable detects flash**
```typescript
// useFlashMessage watcher fires
(newMessage = "Post created successfully.") => {
    showMessage.value = true; // Show banner
    setTimeout(() => {
        showMessage.value = false; // Hide after 5s
    }, 5000);
}
```

**10. User sees message**
```
┌─────────────────────────────────────────┐
│ ✓ Post created successfully.          × │
└─────────────────────────────────────────┘
```

**11. After 5 seconds**
```
Message fades out
Flash is gone from session
Ready for next flash message
```

---

## Troubleshooting

### Problem 1: Flash message doesn't appear

**Possible causes:**

1. **Flash not set in controller**
   ```php
   // ❌ Missing flash
   return back();
   
   // ✅ Correct
   return back()->with('status', 'Success!');
   ```

2. **Flash key mismatch**
   ```php
   // Backend
   return back()->with('success', 'Done!');
   
   // Frontend ❌
   useFlashMessage('status') // Looking for 'status', not 'success'
   
   // Fix: Use consistent key
   useFlashMessage('success')
   ```

3. **Middleware not sharing flash**
   ```php
   // Check HandleInertiaRequests::share()
   'flash' => [
       'status' => $request->session()->get('status'), // ← Must be here
   ],
   ```

4. **Missing `{ immediate: true }`**
   ```typescript
   // ❌ Watcher won't run on mount
   watch(() => flash.status, callback);
   
   // ✅ Runs on mount
   watch(() => flash.status, callback, { immediate: true });
   ```

### Problem 2: Flash appears on first submission, but not subsequent ones

**Cause:** Flash consumed in controller instead of middleware

**Solution:**
```php
// ❌ Don't do this
public function index() {
    return Inertia::render('Welcome', [
        'status' => session('status'), // Consumed here!
    ]);
}

// ✅ Do this instead
// Share in HandleInertiaRequests::share()
'flash' => [
    'status' => $request->session()->get('status'),
],
```

### Problem 3: Flash persists across pages

**Cause:** Not using session flash, using regular session

**Solution:**
```php
// ❌ Regular session (persists)
Session::put('status', 'Success!');

// ✅ Flash (one-time)
Session::flash('status', 'Success!');
// OR
return back()->with('status', 'Success!');
```

### Problem 4: Flash appears too quickly or doesn't auto-hide

**Cause:** Incorrect duration or missing timeout cleanup

**Solution:**
```typescript
// Adjust duration (in milliseconds)
useFlashMessage('status', 10000); // 10 seconds

// Ensure timeout is cleared
if (messageTimeout) {
    clearTimeout(messageTimeout);
    messageTimeout = null;
}
```

---

## Best Practices

### 1. Consistent Flash Keys

**✅ Recommended:**
```php
// Use 'status' for success messages
return back()->with('status', 'Success!');

// Use 'error' for error messages
return back()->with('error', 'Failed!');

// Use 'warning' for warnings
return back()->with('warning', 'Be careful!');
```

### 2. Share All Flash Keys in Middleware

```php
// app/Http/Middleware/HandleInertiaRequests.php
'flash' => [
    'status' => $request->session()->get('status'),
    'error' => $request->session()->get('error'),
    'warning' => $request->session()->get('warning'),
],
```

### 3. Use Composable Everywhere

```typescript
// ✅ Good: DRY
const { showMessage, message, dismiss } = useFlashMessage('status');

// ❌ Bad: Duplicate logic
const showStatus = ref(false);
watch(() => $page.props.flash.status, ...);
```

### 4. Always Include Dismiss Button

```vue
<button @click="dismiss">×</button>
```

Allows users to close message before auto-hide.

### 5. Use Appropriate Durations

```typescript
// Success messages: 5 seconds (default)
useFlashMessage('status', 5000);

// Error messages: 7 seconds (more time to read)
useFlashMessage('error', 7000);

// Important notices: No auto-hide
useFlashMessage('important', 0);
```

---

## Summary

**Flash Message Flow:**
1. ✅ Controller sets flash → `with('status', 'Message')`
2. ✅ Session stores flash temporarily
3. ✅ Redirect sent to browser
4. ✅ Middleware shares flash → `$request->session()->get('status')`
5. ✅ Flash consumed (one-time read)
6. ✅ Inertia sends props to frontend
7. ✅ Composable watches `$page.props.flash.status`
8. ✅ Watcher triggers (immediate: true)
9. ✅ Message displayed with animation
10. ✅ Auto-hide after duration OR manual dismiss
11. ✅ Flash lifecycle complete

**Key Points:**
- Flash messages are **one-time** (consumed after first read)
- Share flash in **middleware**, not controllers
- Use **composable** for DRY code
- Always use **`{ immediate: true }`** in watcher
- Provide **manual dismiss** option
- Use **consistent flash keys** across the app

---

## Related Documentation

- [Laravel Session Flash](https://laravel.com/docs/session#flash-data)
- [Inertia Shared Data](https://inertiajs.com/shared-data)
- [useFlashMessage Composable](./FLASH_MESSAGE_COMPOSABLE.md)
- [Vue Watchers](https://vuejs.org/guide/essentials/watchers.html)

---

**Last Updated:** October 15, 2025  
**Author:** Development Team  
**Files Involved:**
- `app/Http/Controllers/PostController.php`
- `app/Http/Controllers/Settings/ProfileController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/composables/useFlashMessage.ts`
- `resources/js/pages/Welcome.vue`
- `resources/js/pages/settings/View.vue`
