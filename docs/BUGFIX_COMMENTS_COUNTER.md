# Bug Fix: Comments Counter Double Increment

## Issue
When a user posted a comment, the comment counter would increment by 2 instead of 1, showing one extra comment that didn't exist.

## Root Cause
The comment counter was being incremented twice:

1. **Local increment** - When user posts their own comment:
   - `CommentSection.submitComment()` adds comment to local list
   - Emits `comment-added` event
   - `PostItem.handleCommentAdded()` increments `totalComments++`

2. **Broadcast increment** - From WebSocket event:
   - Even though backend uses `.toOthers()`, there were edge cases where the user might receive their own broadcast (multiple tabs, connection issues, etc.)
   - `PostItem.addCommentFromBroadcast()` was unconditionally incrementing `totalComments++`
   - This happened even if the comment already existed in the local list (duplicate)

## Solution

Updated `CommentSection.addCommentFromBroadcast()` to return a boolean:
- Returns `true` if comment was actually added (new comment)
- Returns `false` if comment already existed (duplicate)

Updated `PostItem.addCommentFromBroadcast()` to:
- Only increment counter if `addCommentFromBroadcast()` returns `true`
- This prevents double counting when the same comment is received via broadcast

## Files Modified

1. **`resources/js/components/app/CommentSection.vue`**
   - Changed `addCommentFromBroadcast()` return type from `void` to `boolean`
   - Returns `true` when comment is added, `false` when duplicate detected

2. **`resources/js/components/app/PostItem.vue`**
   - Updated `addCommentFromBroadcast()` to check return value
   - Only increments `totalComments` if comment was actually added

## Testing

To verify the fix:

1. **Single User Test**:
   - Post a comment
   - Verify counter increments by exactly 1
   - Post another comment
   - Verify counter increments by exactly 1 again

2. **Multi-Tab Test** (Edge case):
   - Open same post in two tabs with same user
   - Post comment in Tab 1
   - Verify counter in Tab 1 increments by 1 only
   - Tab 2 should see the comment appear with correct count

3. **Multi-User Test** (Real-time):
   - User A and User B viewing same post
   - User A posts comment
   - User A's counter: +1
   - User B's counter: +1 (via broadcast)
   - Both should show same total

## Code Changes

### Before:
```typescript
// CommentSection.vue
const addCommentFromBroadcast = (comment: Comment) => {
    if (!localComments.value.find(c => c.id === comment.id)) {
        localComments.value.unshift(comment);
    }
};

// PostItem.vue
const addCommentFromBroadcast = (comment: Comment) => {
    if (commentSectionRef.value) {
        commentSectionRef.value.addCommentFromBroadcast(comment);
        totalComments.value++; // Always incremented!
    }
};
```

### After:
```typescript
// CommentSection.vue
const addCommentFromBroadcast = (comment: Comment): boolean => {
    if (!localComments.value.find(c => c.id === comment.id)) {
        localComments.value.unshift(comment);
        return true; // Comment was added
    }
    return false; // Duplicate
};

// PostItem.vue
const addCommentFromBroadcast = (comment: Comment) => {
    if (commentSectionRef.value) {
        const wasAdded = commentSectionRef.value.addCommentFromBroadcast(comment);
        if (wasAdded) {
            totalComments.value++; // Only increment if actually added
        }
    }
};
```

## Status
✅ **Fixed** - Comment counter now accurately reflects the actual number of comments.
