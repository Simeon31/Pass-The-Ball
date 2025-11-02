# Group Profile Images Bug Fix

## Issue
Group profile images (cover and thumbnail) were showing as previews only but not persisting to the database. After refreshing the page, the images would disappear.

## Root Cause
The issue had two main problems:

1. **Backend Response**: The controller was using `redirect()->route()` instead of `back()`, which prevented Inertia from properly refreshing the page props with updated data after the upload.

2. **Frontend State Management**: The Vue component wasn't properly handling the response after successful upload. While the images were being saved to the database, the component's local state wasn't being synchronized with the server response.

3. **Inconsistent UX**: The thumbnail upload didn't have save/cancel buttons, creating confusion about whether changes were saved.

## Changes Made

### Backend (PHP)
**File**: `app/Http/Controllers/GroupController.php`

```php
// BEFORE
return redirect()->route('groups.show', $group->slug)
    ->with('status', 'Group image updated successfully!');

// AFTER
return back()->with('status', 'Group images updated successfully!');
```

**Why**: Using `back()` instead of `redirect()->route()` is the correct pattern for Inertia.js applications because:
- It preserves the current Inertia page state
- It automatically refreshes page props with updated data from the server
- It's simpler and more idiomatic for Inertia apps

### Frontend (Vue)
**File**: `resources/js/pages/Groups/Show.vue`

#### 1. Added flash message support
```typescript
import { useFlashMessage } from '@/composables/useFlashMessage';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);
```

Added success message display in the template with auto-dismiss after 5 seconds.

#### 2. Added `hasUnsavedImages` computed property
```typescript
const hasUnsavedImages = computed(() => {
    return coverImageSrc.value !== null || thumbnailImageSrc.value !== null;
});
```

This tracks whether ANY image (cover or thumbnail) has unsaved changes, providing better state management.

#### 3. Fixed `submitImages` function
```typescript
// BEFORE
router.post(`/groups/${props.group.slug}/images`, formData, {
    preserveScroll: true,
    onSuccess: () => {
        // Clear state
    },
});

// AFTER
router.post(`/groups/${props.group.slug}/images`, formData, {
    forceFormData: true,
    onSuccess: () => {
        coverImageSrc.value = null;
        thumbnailImageSrc.value = null;
        coverFile.value = null;
        thumbnailFile.value = null;
    },
});
```

**Changes**:
- Removed `preserveScroll: true` which was preventing proper redirect handling
- Added `forceFormData: true` to ensure FormData is sent correctly
- The `back()` response from the backend now properly refreshes the component props

#### 4. Added Save/Cancel buttons to thumbnail upload
Previously, only the cover image had save/cancel buttons. Now both cover and thumbnail show consistent save/cancel UI when ANY image is changed.

```vue
<!-- Both cover and thumbnail now check hasUnsavedImages -->
<div v-if="canEditImages" class="...">
    <button v-if="!hasUnsavedImages">
        Update Cover/Thumbnail
    </button>
    <div v-else class="flex gap-2">
        <button @click="cancelImages">Cancel</button>
        <button @click="submitImages">Save</button>
    </div>
</div>
```

## How It Works Now

1. **User selects cover or thumbnail image** → Preview shown immediately
2. **Save/cancel buttons appear** → On both cover AND thumbnail areas
3. **User clicks Save** → FormData with both images (if changed) sent to backend
4. **Backend processes images** → Intervention Image resizes and optimizes
5. **Backend saves to database** → Paths stored as `/storage/groups/{id}/cover_xxx.jpg`
4. **Backend returns `back()`** → Inertia automatically refreshes page props
5. **Frontend receives updated props** → Component re-renders with new `group.cover_url` and `group.thumbnail_url`
6. **Local preview state cleared** → Falls back to server URLs from props
7. **Success flash message displays** → "Group images updated successfully!" shown for 5 seconds

## Technical Details

### Image Processing (Backend)
- **Cover**: Resized to 1200x400px, JPEG quality 85%
- **Thumbnail**: Resized to 300x300px, JPEG quality 85%
- Storage: `storage/app/public/groups/{group_id}/`
- Old images are deleted when new ones are uploaded

### Validation (FormRequest)
```php
'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
```

### Authorization
- Uses `GroupPolicy` to check `updateImages` permission
- Only users with `edit_group_images` permission can upload

## Testing Checklist

- [ ] Upload cover image only → Saves correctly
- [ ] Upload thumbnail image only → Saves correctly  
- [ ] Upload both images together → Both save correctly
- [ ] Cancel after selecting image → Preview disappears
- [ ] Refresh page after save → Images persist
- [ ] Old images are deleted → No orphaned files
- [ ] Non-members cannot see upload buttons → Permission check works
- [ ] Flash message appears on success → "Group images updated successfully!" shows for 5 seconds
- [ ] Flash message can be manually dismissed → X button works
- [ ] Flash message auto-dismisses → Disappears after 5 seconds

## Files Modified

1. `app/Http/Controllers/GroupController.php` - Changed redirect to back()
2. `resources/js/pages/Groups/Show.vue` - Fixed state management and UX

## Related Files (No Changes Needed)

- `app/Http/Requests/Group/UpdateGroupImagesRequest.php` - Validation rules (working)
- `app/Http/Resources/GroupResource.php` - Returns cover_url and thumbnail_url (working)
- `app/Models/Group.php` - Has cover_path and thumbnail_path fields (working)
- `database/migrations/2025_09_25_182306_create_groups_table.php` - Schema (working)
- `routes/web.php` - Route defined as POST (working)

## Lessons Learned

1. **Inertia Pattern**: Always use `back()` instead of `redirect()->route()` after POST/PUT/DELETE operations in Inertia apps
2. **FormData Handling**: Set `forceFormData: true` when sending files via Inertia router
3. **State Sync**: Inertia automatically refreshes props after redirects, don't manually reload unless necessary
4. **Consistent UX**: If one upload has save/cancel, all uploads should have it
