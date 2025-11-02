# Group Posts Bug Fix

## Issue
Posts created inside a group were appearing on the welcome page (general feed) instead of staying within the specific group. All posts were being displayed together regardless of whether they belonged to a group or not.

## Root Causes

### 1. Backend Not Saving Group ID
The `PostController::store()` method was not saving the `group_id` field from the request, even though:
- The frontend was sending it
- The `Post` model had `group_id` in its fillable array
- The database migration included the `group_id` column

### 2. Missing Validation Rule
The `StorePostRequest` validation rules didn't include `group_id`, so the field was being ignored during validation.

### 3. No Authorization Check
There was no check to ensure users could only post in groups they're members of.

### 4. Welcome Page Query Not Filtering
The `WelcomeController` was querying ALL posts without filtering out group posts. Both `index()` and `getPosts()` methods were showing posts regardless of `group_id`.

## Solution

### Backend Changes

#### 1. Added Validation Rule
**File**: `app/Http/Requests/StorePostRequest.php`

```php
public function rules(): array
{
    return [
        'body' => ['nullable', 'string'],
        'user_id' => ['numeric', 'exists:users,id'],
        'group_id' => ['nullable', 'numeric', 'exists:groups,id'], // Added
        'attachments' => ['nullable', 'array', 'max:10'],
        // ...
    ];
}
```

#### 2. Added Authorization Check
**File**: `app/Http/Requests/StorePostRequest.php`

```php
use App\Models\Group;

public function authorize(): bool
{
    // If posting to a group, verify user is a member
    if ($this->has('group_id') && $this->group_id) {
        $group = Group::find($this->group_id);
        
        if (!$group) {
            return false;
        }
        
        // Check if user is a member of the group
        return $group->isMember(auth()->user());
    }
    
    // General posts are allowed for authenticated users
    return auth()->check();
}
```

**Why this is important**:
- Prevents users from posting to groups they're not members of
- Returns 403 Forbidden if unauthorized
- Reuses existing `Group::isMember()` method

#### 3. Save Group ID in Controller
**File**: `app/Http/Controllers/PostController.php`

```php
public function store(StorePostRequest $request)
{
    $data = $request->validated();

    // Create the post
    $post = Post::create([
        'user_id' => $data['user_id'],
        'body' => $data['body'] ?? null,
        'group_id' => $data['group_id'] ?? null, // Added
    ]);
    
    // ... rest of the method
}
```

#### 4. Filter Group Posts from Welcome Page
**File**: `app/Http/Controllers/WelcomeController.php`

```php
public function index(Request $request)
{
    $posts = Post::query()
        ->whereNull('group_id') // Only show posts that are not in groups
        ->latest()
        ->paginate(10);

    return Inertia::render("Welcome", [
        'posts' => PostResource::collection($posts),
    ]);
}

public function getPosts(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    $posts = Post::query()
        ->whereNull('group_id') // Only show posts that are not in groups
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

**Why this is important**:
- Keeps general feed clean (only non-group posts)
- Group posts only visible to group members
- Proper separation of concerns

### Frontend (No Changes Needed)

The frontend was already correctly implemented:

**File**: `resources/js/components/app/CreatePostModal.vue`

```typescript
const newPostForm = useForm({
    body: '',
    group_id: props.groupId, // Already sending group_id
    attachments: [] as File[],
});
```

The `CreatePost` component accepts `groupId` as a prop and passes it through to the modal.

## How It Works Now

### Posting to General Feed
1. User opens create post modal from Welcome page
2. `groupId` prop is `undefined`
3. `group_id` in form is `null`
4. Post created with `group_id = null`
5. Post appears on Welcome page ✓

### Posting to Group
1. User opens create post modal from Group page
2. `groupId` prop is set (e.g., `5`)
3. `group_id` in form is `5`
4. **Authorization check**: Verify user is member of group 5
5. Post created with `group_id = 5`
6. Post appears ONLY in group page ✓
7. Post does NOT appear on Welcome page ✓

### Authorization Flow
```
User submits post
    ↓
StorePostRequest::authorize()
    ↓
Has group_id? → No → Check auth()->check()
    ↓
Yes → Find Group
    ↓
Group exists? → No → Return false (403)
    ↓
Yes → Check isMember()
    ↓
Is member? → No → Return false (403)
    ↓
Yes → Allow (200)
```

## Database Schema

The `posts` table already had the correct schema:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->longText('body')->nullable();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('group_id')->nullable()->constrained('groups'); // ✓
    $table->foreignId('deleted_by')->nullable()->constrained('users');
    $table->timestamp('deleted_at')->nullable();
    $table->timestamps();
});
```

The `group_id` column:
- Is nullable (posts can be general or group-specific)
- Has foreign key constraint to `groups` table
- Indexed for query performance

## Post Visibility Rules

### General Feed (Welcome Page)
- Shows posts where `group_id IS NULL`
- Visible to all authenticated users
- No group membership required

### Group Feed (Group Show Page)
- Shows posts where `group_id = {specific_group_id}`
- Only visible to group members (checked by `GroupController::show()`)
- Requires group membership

## Testing Checklist

### General Posts
- [ ] Create post from Welcome page → Appears on Welcome page
- [ ] Create post from Welcome page → Does NOT appear in any group
- [ ] Non-group post visible to all users

### Group Posts
- [ ] Create post in Group A → Appears in Group A feed
- [ ] Create post in Group A → Does NOT appear in Group B feed
- [ ] Create post in Group A → Does NOT appear on Welcome page
- [ ] Group post visible only to group members

### Authorization
- [ ] Non-member tries to post to group → 403 Forbidden
- [ ] Member posts to group → Success
- [ ] Post to non-existent group → 403 Forbidden
- [ ] Post without authentication → 401 Unauthorized

### Edge Cases
- [ ] User leaves group after posting → Post remains in group
- [ ] Group is deleted → Posts remain (soft delete)
- [ ] User is kicked from group → Can't see their old posts in group

## Files Modified

1. `app/Http/Requests/StorePostRequest.php`
   - Added `group_id` validation rule
   - Added authorization check for group membership

2. `app/Http/Controllers/PostController.php`
   - Added `group_id` to post creation

3. `app/Http/Controllers/WelcomeController.php`
   - Added `whereNull('group_id')` filter to both methods

## Related Features

### Group Permissions
The authorization uses `Group::isMember()` which checks the `group_users` pivot table for approved members. This integrates with the existing group permission system.

### Post Resources
The `PostResource` already includes the group relationship:
```php
'group' => new GroupResource($this->whenLoaded('group')),
```

### Infinite Scroll
The fix works with infinite scroll on both Welcome and Group pages because the filtering happens at the query level.

## Benefits

### User Experience
- Clear separation between general and group posts
- Group posts stay private to group members
- General feed stays uncluttered

### Security
- Users can't post to groups they're not members of
- Proper authorization checks
- Validation prevents invalid data

### Data Integrity
- Proper foreign key relationships
- Nullable group_id allows both post types
- Database constraints enforced

## Potential Enhancements

### Future Improvements
1. **Group Post Permissions**: Fine-grained control (e.g., only admins can post)
2. **Post Visibility Settings**: Public vs. members-only group posts
3. **Cross-posting**: Allow posting to multiple groups
4. **Group Feed Filters**: Sort by popular, recent, etc.

## Lessons Learned

1. **Always validate all inputs**: Missing validation rules can lead to data loss
2. **Test authorization paths**: Check both member and non-member scenarios
3. **Query filtering is crucial**: Separate feeds require explicit WHERE clauses
4. **Frontend ≠ Backend**: Even if frontend sends data, backend must handle it
