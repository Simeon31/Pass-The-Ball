# Polymorphic Reactions Feature

## Overview
This document describes the implementation of polymorphic reactions for both **Posts** and **Comments** in the Pass The Ball application. Users can react to both posts and comments with various emoji reactions (like, love, haha, wow, sad, angry).

## Date Implemented
October 17, 2025

## Architecture

### Database Schema
The `reactions` table (previously `post_reactions`) now uses polymorphic relationships:

```php
Schema::table('reactions', function (Blueprint $table) {
    $table->id();
    $table->string('reactable_type');      // Model class (Post or Comment)
    $table->unsignedBigInteger('reactable_id'); // Model ID
    $table->string('type');                // Reaction type (like, love, etc.)
    $table->foreignId('user_id')->constrained('users');
    $table->timestamp('created_at')->nullable();
    
    $table->index(['reactable_type', 'reactable_id']);
});
```

### Backend Components

#### 1. Models

**`app/Models/Reaction.php`** (Polymorphic Model)
- Defines all reaction types: `like`, `love`, `haha`, `wow`, `sad`, `angry`
- Uses `morphTo()` relationship for polymorphic connection
- Replaces the old `PostReaction` model

**`app/Models/Post.php`** and **`app/Models/Comment.php`**
- Both use `morphMany(Reaction::class, 'reactable')` relationship

#### 2. Controllers

**`app/Http/Controllers/ReactionController.php`** (New Generic Controller)
- Single `toggle()` method handles reactions for both posts and comments
- Route: `POST /{type}/{id}/reaction` where type is `post` or `comment`
- Automatically broadcasts reaction updates via appropriate events

**`app/Http/Controllers/PostReactionController.php`** (Updated for backward compatibility)
- Updated to use the new `Reaction` model with polymorphic queries
- Maintains existing route `POST /post/{post}/reaction`

#### 3. Resources

**`app/Http/Resources/CommentResource.php`** (Updated)
- Now includes reactions data structure matching PostResource:
```php
'reactions' => [
    'summary' => ['like' => 5, 'love' => 2, ...],
    'total' => 7,
    'current_user_reaction' => 'like' // or null
]
```

#### 4. Events

**`app/Events/CommentReacted.php`** (New)
- Broadcasts on channel: `comments.{commentId}`
- Event name: `comment.reacted`
- Payload includes comment ID and reactions summary

**`app/Events/PostReacted.php`** (Existing)
- Continues to work with new Reaction model

### Frontend Components

#### 1. TypeScript Types (`resources/js/types/index.d.ts`)

Updated `Comment` interface to include reactions:
```typescript
export interface Comment {
    id: number;
    post_id: number;
    comment: string;
    user: User;
    created_at: string;
    updated_at: string;
    reactions: PostReactions; // Reused from Post
}
```

#### 2. Vue Components

**`resources/js/components/app/CommentItem.vue`** (Updated)
- Added `ReactionPicker` component below comment text
- Implements reaction toggle logic via Axios POST to `/comment/{id}/reaction`
- Uses `useCommentBroadcasting` composable for real-time updates
- Emits `reactions-updated` event to parent components

**`resources/js/components/app/ReactionPicker.vue`** (Reused)
- Uses HeadlessUI `Popover` component for dropdown
- Displays current reaction and total count
- Shows all 6 reaction options with emoji and labels
- Fully reusable for both posts and comments

#### 3. Composables

**`resources/js/composables/useCommentBroadcasting.ts`** (New)
- Listens to `comments.{commentId}` channel
- Subscribes to `comment.reacted` events
- Automatically updates local reaction state
- Cleans up connections on component unmount

### Routes

```php
// Generic polymorphic reactions route
Route::post('/{type}/{id}/reaction', [ReactionController::class, 'toggle'])
    ->middleware(['auth', 'verified'])
    ->where(['type' => 'post|comment', 'id' => '[0-9]+'])
    ->name('reaction.toggle');

// Backward compatible post reactions route
Route::post('/post/{post}/reaction', [PostReactionController::class, 'toggle'])
    ->middleware(['auth', 'verified'])
    ->name('post.reaction.toggle');
```

## Migrations

**`2025_10_17_124008_cleanup_reactions_table.php`**
- Removes `post_id` column and foreign key
- Keeps polymorphic columns (`reactable_type`, `reactable_id`)
- Existing post reactions automatically migrated to polymorphic format

## Features

### 1. Reaction Types
- 👍 Like (Blue)
- ❤️ Love (Red)
- 😂 Haha (Yellow)
- 😮 Wow (Purple)
- 😢 Sad (Light Blue)
- 😠 Angry (Orange)

### 2. Reaction Behavior
- **Toggle On:** Click a reaction to add it
- **Toggle Off:** Click the same reaction again to remove it
- **Change Reaction:** Click a different reaction to update
- **Real-time Updates:** All users see reactions update instantly via WebSockets

### 3. UI/UX Features
- Reaction count displayed on picker button
- Current user's reaction highlighted with color
- Hover tooltips on reaction emojis
- Smooth animations on hover and click
- Consistent design between posts and comments

## Best Practices Followed

### 1. DRY (Don't Repeat Yourself)
- `ReactionPicker.vue` component reused for both posts and comments
- `PostReactions` TypeScript interface reused for comments
- Generic `ReactionController` handles both resource types
- Broadcasting composables follow same pattern

### 2. Type Safety
- Full TypeScript coverage for frontend
- Strongly typed props and emits in Vue components
- Laravel type hints in controllers and models

### 3. Separation of Concerns
- Models handle data relationships
- Controllers handle business logic
- Resources format API responses
- Components handle UI rendering
- Composables handle side effects (broadcasting)

### 4. Scalability
- Polymorphic design allows easy addition of new reactable types (e.g., replies, messages)
- Event broadcasting architecture supports real-time features
- Indexed database columns for performance

### 5. HeadlessUI Integration
- Uses `Popover` component from HeadlessUI as specified
- Accessible keyboard navigation
- Screen reader compatible
- Mobile-friendly touch interactions

## Testing Checklist

- [x] Migrations run successfully
- [x] Post reactions continue to work with new model
- [x] Comment reactions display and function correctly
- [x] Reaction picker shows correct emoji and counts
- [x] Real-time updates work for both posts and comments
- [x] TypeScript compilation has no errors
- [x] User can add/remove/change reactions on comments
- [x] Reaction counts update correctly
- [x] Database queries use proper polymorphic relationships

## Future Enhancements

1. **Reaction Notifications:** Notify users when someone reacts to their content
2. **Reaction Details Modal:** Show who reacted with what emoji
3. **Custom Reactions:** Allow admins to add custom reaction types
4. **Reaction Analytics:** Track most popular reactions over time
5. **Reaction Limits:** Optionally limit reactions per user/per content

## Related Files

### Backend
- `app/Models/Reaction.php`
- `app/Models/Post.php`
- `app/Models/Comment.php`
- `app/Http/Controllers/ReactionController.php`
- `app/Http/Controllers/PostReactionController.php`
- `app/Http/Resources/CommentResource.php`
- `app/Events/CommentReacted.php`
- `database/migrations/2025_10_17_124008_cleanup_reactions_table.php`

### Frontend
- `resources/js/components/app/CommentItem.vue`
- `resources/js/components/app/ReactionPicker.vue`
- `resources/js/composables/useCommentBroadcasting.ts`
- `resources/js/types/index.d.ts`

### Routes
- `routes/web.php`

## Breaking Changes
None - the implementation maintains backward compatibility with existing post reactions.
