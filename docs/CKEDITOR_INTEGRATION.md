# CKEditor Rich Text Editor Integration

## Overview

A CKEditor 5 rich text editor has been integrated into the post update modal dialog to provide users with rich text formatting capabilities when editing posts.

## Implementation Details

### Component Location
- **CKEditor Component**: `resources/js/components/ui/CKEditor.vue`
- **Usage**: `resources/js/components/app/EditPostModal.vue`

### Features Enabled

The CKEditor implementation includes the following features:
- **Text Formatting**: Bold, Italic
- **Headings**: Paragraph, Heading 1, 2, 3
- **Lists**: Bulleted and Numbered lists
- **Block Quote**: For quoted text
- **Links**: Insert and edit hyperlinks
- **Undo/Redo**: History navigation

### Technical Stack

- **Package**: `ckeditor5` (v47.0.0)
- **Editor Type**: ClassicEditor
- **License Key**: GPL (for open-source projects)
- **Plugins Used**:
  - Essentials
  - Paragraph
  - Bold
  - Italic
  - Undo
  - Link
  - List
  - BlockQuote
  - Heading

### License Configuration

The editor is configured with `licenseKey: 'GPL'` which is appropriate for:
- Open-source projects distributed under GPL
- Non-commercial projects
- Development and testing

**Note**: If you plan to use this in a commercial project, you'll need to:
1. Purchase a commercial license from CKSource
2. Replace `'GPL'` with your commercial license key
3. See [CKEditor Pricing](https://ckeditor.com/pricing/) for details

### Component API

#### Props

```typescript
{
  modelValue: string;      // Two-way binding for editor content
  placeholder?: string;    // Placeholder text (default: "What's on your mind?")
  disabled?: boolean;      // Enable/disable read-only mode
}
```

#### Events

```typescript
{
  'update:modelValue': (value: string) => void;  // Emits when content changes
}
```

#### Usage Example

```vue
<CKEditor 
  v-model="form.body"
  placeholder="What's on your mind?"
  :disabled="form.processing"
/>
```

### Styling

The CKEditor has been styled to match the application's design system:
- **Border Radius**: 0.5rem (rounded-lg)
- **Border Color**: #d1d5db (gray-300)
- **Focus Ring**: Indigo color (#6366f1) with shadow
- **Toolbar Background**: #f9fafb (gray-50)
- **Minimum Height**: 200px

### Integration with EditPostModal

The EditPostModal.vue component has been updated to:
1. Replace the plain textarea with the CKEditor component
2. Maintain two-way binding with `form.body` via `v-model`
3. Disable the editor when the form is processing
4. Preserve all existing functionality (validation, error display, form submission)
5. **Properly cleanup the editor** - Uses `v-if="isOpen"` and a unique `:key` to ensure the editor is destroyed when the modal closes and recreated when it opens
6. **Prevent memory leaks** - The CKEditor component uses `onBeforeUnmount` to properly destroy the editor instance

#### Key Implementation Details

**Component Key for Proper Lifecycle:**
```vue
<CKEditor
    v-if="isOpen"
    :key="editorKey"
    v-model="form.body"
    placeholder="What's on your mind?"
    :disabled="form.processing"
/>
```

**Critical Design Decisions:**

1. **No Reactive Editor Instance**: The editor instance is stored as a plain variable (`let editorInstance`) instead of a Vue ref to avoid proxy-related conflicts with CKEditor's internal event system

2. **Incremental Key**: Uses a counter that increments on each modal open to force Vue to completely destroy and recreate the component

3. **Destruction Guard**: The `isDestroying` flag prevents any editor operations during cleanup to avoid race conditions

4. **Defensive Programming**: All editor API calls are wrapped in try-catch blocks with null checks

### Data Flow

1. **Initial Load**: Post body content is loaded from `props.post.body`
2. **Editing**: User edits content in CKEditor, which emits `update:modelValue`
3. **Form Binding**: The emitted value updates `form.body`
4. **Submission**: The form submits the HTML content via Inertia's `form.put()`
5. **Sanitization**: Backend sanitizes HTML using HTML Purifier (see [HTML Sanitization](./HTML_SANITIZATION.md))
6. **Success**: Modal closes and form resets
7. **Display**: Post content is rendered with `v-html` in `PostItem.vue` with link handling

### Link Handling in Post Content

When displaying posts with rich text content (including links), the `PostItem.vue` component automatically:

1. **Makes Links Clickable**: Adds click handlers to all `<a>` tags in the rendered HTML
2. **Opens in New Tab**: Sets `target="_blank"` and `rel="noopener noreferrer"` for security
3. **Prevents Event Bubbling**: Stops click events from propagating to parent elements
4. **Styles Links**: Applies indigo color with underline and hover effects

**Implementation**:
```typescript
const makeLinksClickable = (element: HTMLElement | null) => {
    if (!element) return;
    
    const links = element.querySelectorAll('a');
    links.forEach((link) => {
        if (!link.hasAttribute('target')) {
            link.setAttribute('target', '_blank');
        }
        if (!link.hasAttribute('rel')) {
            link.setAttribute('rel', 'noopener noreferrer');
        }
        link.addEventListener('click', (e) => {
            e.stopPropagation();
            const href = link.getAttribute('href');
            if (href && href !== '#') {
                window.open(href, '_blank', 'noopener,noreferrer');
            }
        });
    });
};
```

### Benefits

- **Rich Text**: Users can format their posts with headings, lists, quotes, and links
- **User-Friendly**: Familiar WYSIWYG interface
- **Accessible**: Proper keyboard navigation and screen reader support
- **Responsive**: Works well on different screen sizes
- **Consistent**: Matches the application's design system

## Troubleshooting

### Editor Not Closing Properly / Proxy Errors

**Issue**: Console shows errors like `'get' on proxy: property '_events' is a read-only and non-configurable data property` and the modal won't close.

**Root Cause**: CKEditor instances don't work well with Vue 3's reactivity system (Proxy objects). Vue watchers trying to access CKEditor's internal properties cause conflicts.

**Solution Implemented**:

1. **Removed Vue Watchers**: The component no longer uses `watch()` on props that interact with the editor instance
2. **Plain Variable Instead of Ref**: Uses `let editorInstance` instead of `ref(editorInstance)` to avoid Vue's reactivity
3. **Destruction Flag**: Uses `isDestroying` flag to prevent operations during cleanup
4. **Error Handling**: All editor operations wrapped in try-catch blocks
5. **Key-based Re-rendering**: EditPostModal uses a counter (`editorKey`) that increments on each modal open

**EditPostModal Implementation**:
```typescript
const editorKey = ref(0);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            form.body = props.post.body || '';
            form.clearErrors();
            editorKey.value++; // Force component recreation
        }
    },
);
```

**CKEditor Component Changes**:
- No reactive refs for editor instance
- Flag-based destruction to prevent race conditions
- All editor API calls protected with null checks and error handling

### License Key Warnings

**Issue**: Console shows license key warnings.

**Solution**: The component is configured with `licenseKey: 'GPL'` for open-source use. For commercial projects, replace with your commercial license key.

### Editor Content Not Updating

**Issue**: Changes to `v-model` prop don't reflect in the editor.

**Solution**: The component watches `props.modelValue` and updates the editor content when it changes externally (see the `watch` implementation).

### Links Not Clickable in Post Content

**Issue**: Links created with CKEditor don't navigate when clicked.

**Root Cause**: When using `v-html` to render HTML content, links don't automatically get click handlers in Vue.

**Solution Implemented**:
1. **Link Handler Function**: `makeLinksClickable()` function in `PostItem.vue` adds click handlers to all links
2. **Security Attributes**: Sets `target="_blank"` and `rel="noopener noreferrer"` on all links
3. **Event Handling**: Prevents event bubbling and explicitly opens links in new tabs
4. **Styling**: CSS deep selectors style links with indigo color and underline

**Usage**: The function is automatically called in `onMounted()` lifecycle hook after the component renders.

### Future Enhancements

Potential features that could be added:
- Image upload and embedding
- Code block formatting
- Tables
- Text alignment
- Text color and highlighting
- Media embed (YouTube, etc.)
- Custom plugins for mentions (@user)
- Emoji picker

## Related Documentation

- [HTML Sanitization](./HTML_SANITIZATION.md) - Detailed security implementation for user-generated content
- [CKEditor Documentation](https://ckeditor.com/docs/ckeditor5/latest/) - Official CKEditor 5 docs
- [HTMLPurifier Documentation](http://htmlpurifier.org/docs) - HTML sanitization library

## Files Modified

### Frontend
1. **Created**: `resources/js/components/ui/CKEditor.vue` - Reusable CKEditor component
2. **Modified**: `resources/js/components/app/EditPostModal.vue` - Replaced textarea with CKEditor
3. **Modified**: `resources/js/components/app/PostItem.vue` - Added link handling for rendered post content

### Backend
1. **Created**: `config/purifier.php` - HTML Purifier configuration
2. **Modified**: `app/Http/Requests/StorePostRequest.php` - Added HTML sanitization
3. **Modified**: `app/Http/Requests/UpdatePostRequest.php` - Added HTML sanitization

## Dependencies

### Frontend
- `ckeditor5: ^47.0.0` (already installed in package.json)
- `ckeditor5/ckeditor5.css` (imported in the component)

### Backend
- `mews/purifier: ^3.4` - HTML Purifier for Laravel
- `ezyang/htmlpurifier: ^4.18` - Core HTML sanitization library

## License Key Setup

The CKEditor component is configured with the GPL license key (`licenseKey: 'GPL'`), which is suitable for:
- ✅ Open-source projects under GPL license
- ✅ Non-commercial projects  
- ✅ Development and testing environments

### For Commercial Use

If deploying this in a commercial/proprietary project:

1. **Get a License**: Visit [CKEditor Pricing](https://ckeditor.com/pricing/)
2. **Update the Config**: In `CKEditor.vue`, replace:
   ```typescript
   licenseKey: 'GPL'
   ```
   with:
   ```typescript
   licenseKey: 'your-commercial-license-key-here'
   ```

3. **Environment Variable (Recommended)**: For better security, use an environment variable:
   ```typescript
   licenseKey: import.meta.env.VITE_CKEDITOR_LICENSE_KEY || 'GPL'
   ```
   Then add to your `.env` file:
   ```
   VITE_CKEDITOR_LICENSE_KEY=your-commercial-license-key-here
   ```

## Notes

- The CKEditor instance is properly cleaned up when the component unmounts to prevent memory leaks
- The editor handles disabled state properly via read-only mode
- Content is synchronized bidirectionally between the editor and the form
- **HTML content is automatically sanitized** - See [HTML Sanitization](./HTML_SANITIZATION.md) for complete security details
- Sanitization removes malicious scripts, event handlers, and dangerous elements
- Only safe HTML tags and attributes are allowed through the whitelist approach
