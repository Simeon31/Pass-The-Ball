# Infinite Scroll Feature Implementation

## Overview

This document describes the implementation of infinite scroll (lazy loading) for posts on the main timeline using the **IntersectionObserver API**. Posts are loaded automatically as the user scrolls down, providing a seamless browsing experience.

## Features

- ✅ **Automatic Loading**: Posts load automatically when scrolling near the bottom
- ✅ **IntersectionObserver**: Modern, performant API for scroll detection
- ✅ **Loading States**: Visual feedback with spinner during data fetch
- ✅ **End-of-Feed Message**: Clear indication when all posts are loaded
- ✅ **Reusable Composable**: `useIntersectionObserver` for other components
- ✅ **Type-Safe**: Full TypeScript support with pagination types

## Architecture

### Backend

#### 1. **Route** (`routes/web.php`)
```php
Route::get('/api/posts', [WelcomeController::class, 'getPosts'])
    ->middleware('auth', 'verified')->name('posts.api');
```

New API endpoint for fetching paginated posts (returns JSON, not Inertia render).

#### 2. **Controller** (`app/Http/Controllers/WelcomeController.php`)
```php
public function getPosts(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    $posts = Post::query()
        ->latest()
        ->with([
            'user',
            'reactions',
            'comments.user',
            'comments.reactions',
            'attachments',
        ])
        ->paginate($perPage, ['*'], 'page', $page);

    return PostResource::collection($posts);
}
```

**Features:**
- Accepts `page` and `per_page` query parameters
- Eager loads relationships for performance
- Returns paginated `PostResource` collection
- Default 10 posts per page

**Response Structure:**
```json
{
  "data": [...], // Array of Post resources
  "links": {
    "first": "http://...",
    "last": "http://...",
    "prev": null,
    "next": "http://..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://...",
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}
```

### Frontend

#### 1. **TypeScript Types** (`resources/js/types/index.d.ts`)

**Added Interfaces:**
```typescript
export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}
```

These interfaces match **Laravel's pagination response structure**.

#### 2. **Composable** (`resources/js/composables/useIntersectionObserver.ts`)

Reusable composable for tracking element visibility:

```typescript
export function useIntersectionObserver(
    elementRef: Ref<HTMLElement | null>,
    callback: () => void,
    options: IntersectionObserverInit = {}
)
```

**Parameters:**
- `elementRef`: Vue ref to the element to observe
- `callback`: Function to call when element becomes visible
- `options`: IntersectionObserver configuration

**Features:**
- Auto-cleanup on component unmount
- Configurable threshold and root margin
- Returns `isIntersecting` reactive state

**Usage Example:**
```typescript
const sentinelRef = ref<HTMLElement | null>(null);

useIntersectionObserver(sentinelRef, loadMorePosts, {
    rootMargin: '100px', // Start loading 100px before visible
});
```

#### 3. **PostList Component** (`resources/js/components/app/PostList.vue`)

**Updated Props:**
```typescript
defineProps<{ 
    initialPosts: PaginatedData<Post>;
}>();
```

**State Management:**
```typescript
const posts = ref<Post[]>([...props.initialPosts.data]);
const currentPage = ref(props.initialPosts.meta.current_page);
const lastPage = ref(props.initialPosts.meta.last_page);
const isLoading = ref(false);
const hasMorePosts = ref(currentPage.value < lastPage.value);
```

**Load More Logic:**
```typescript
const loadMorePosts = async () => {
    if (isLoading.value || !hasMorePosts.value) return;

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    try {
        const response = await axios.get<PaginatedData<Post>>('/api/posts', {
            params: { page: nextPage, per_page: 10 },
        });

        posts.value.push(...response.data.data);
        currentPage.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
        hasMorePosts.value = currentPage.value < lastPage.value;
    } catch (error) {
        console.error('Error loading more posts:', error);
    } finally {
        isLoading.value = false;
    }
};
```

**Sentinel Element:**
```vue
<div v-if="hasMorePosts" ref="sentinelRef" class="...">
    <div v-if="isLoading">Loading spinner...</div>
</div>
```

The invisible "sentinel" element at the bottom triggers loading when it becomes visible.

#### 4. **Welcome Page** (`resources/js/pages/Welcome.vue`)

**Updated Props:**
```typescript
defineProps<{ posts: PaginatedData<Post> }>();
```

**Template:**
```vue
<PostList :initial-posts="posts" class="flex-1" />
```

Now passes full pagination object instead of just the data array.

## User Experience

### Loading States

1. **Initial Load**: 10 posts displayed immediately
2. **Scroll Near Bottom**: When sentinel becomes visible (100px before viewport)
3. **Loading Indicator**: Spinner with "Loading more posts..." text
4. **New Posts Added**: Smoothly appended to the list
5. **End of Feed**: "You've reached the end of the feed" message

### Performance Considerations

- **Lazy Loading**: Only loads posts when needed
- **Eager Loading**: Backend preloads relationships (N+1 prevention)
- **Root Margin**: Starts loading before user reaches bottom (smoother UX)
- **Debouncing**: Built-in via `isLoading` flag prevents multiple simultaneous requests

## Testing

### Manual Testing Steps

1. **Navigate to Home Page**
   - Verify initial 10 posts load
   - Check that post data (user, reactions, comments) displays correctly

2. **Scroll Down**
   - Scroll towards bottom
   - Verify loading spinner appears
   - Confirm new posts load automatically

3. **Reach End of Feed**
   - Continue scrolling until all posts loaded
   - Verify "end of feed" message appears
   - Confirm no more API requests are made

4. **Edge Cases**
   - Test with < 10 total posts (no pagination needed)
   - Test with slow network (loading states)
   - Test rapid scrolling (no duplicate requests)

### Backend Testing

```bash
php artisan test --filter=PostPaginationTest
```

Example test:
```php
public function test_posts_api_returns_paginated_data()
{
    Post::factory()->count(25)->create();

    $response = $this->getJson('/api/posts?page=1&per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'body', 'user', 'reactions', 'comments']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'total']
        ])
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 25);
}
```

## Future Enhancements

- **Scroll to Top Button**: When user scrolls far down
- **Virtual Scrolling**: For very large feeds (thousands of posts)
- **Cache Management**: Store loaded posts in Pinia/localStorage
- **Pull to Refresh**: Mobile-style refresh gesture
- **Skeleton Loaders**: Instead of spinner during initial load
- **Error Handling**: Retry mechanism for failed requests

## Related Files

**Backend:**
- `routes/web.php` - API route definition
- `app/Http/Controllers/WelcomeController.php` - Pagination logic
- `app/Http/Resources/PostResource.php` - Data transformation
- `app/Models/Post.php` - Model with relationships

**Frontend:**
- `resources/js/types/index.d.ts` - TypeScript interfaces
- `resources/js/composables/useIntersectionObserver.ts` - Reusable composable
- `resources/js/components/app/PostList.vue` - Infinite scroll component
- `resources/js/pages/Welcome.vue` - Main timeline page

## References

- [IntersectionObserver API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API)
- [Laravel Pagination](https://laravel.com/docs/12.x/pagination)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
