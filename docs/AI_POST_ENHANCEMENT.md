# AI Post Content Enhancement Feature

**Date:** October 26, 2025 (Updated: October 29, 2025)  
**Feature:** AI-powered post content enhancement with tone selection  
**Provider:** Groq (free)  
**Status:** ✅ Complete

---

## Overview

This feature integrates AI models (via Groq or OpenAI) to enhance post content before submission. Users can write a draft post, select a tone, and receive an AI-enhanced version that improves clarity, engagement, and tone while maintaining the original message.

**Default Provider:** Groq (100% free with generous limits - 14,400 requests/day)

**Architecture:** Inline preview within create post modal (no modal blocking or z-index conflicts)

---

## Features

### Core Functionality
- ✅ **AI-powered content enhancement** using Groq (recommended) or OpenAI
- ✅ **5 tone options**: Professional, Casual, Enthusiastic, Inspiring, Humorous
- ✅ **200-character limit** for enhanced content
- ✅ **Inline preview** within create post modal (no modal blocking)
- ✅ **Seamless editing workflow** - content applied to CKEditor for further editing
- ✅ **One-time generation** per post (prevents abuse)
- ✅ **Loading states** with visual feedback and spinner
- ✅ **Comprehensive error handling** with specific error messages
- ✅ **CSRF token protection** via axios bootstrap
- ✅ **Rate limit detection** with user-friendly messages

### User Experience Flow
1. User opens Create Post modal
2. User types post content (minimum 5 characters)
3. User selects desired tone from dropdown
4. User clicks "AI Enhance" button
5. System shows loading indicator while processing
6. **Inline preview appears** below the editor with original vs enhanced content
7. User can either:
   - **Use This Content**: Apply enhanced content to editor for further editing
   - **Reject**: Dismiss suggestion and optionally generate again

**Key UX Improvement:** Preview is inline (not a blocking modal), allowing seamless editing workflow.

---

## Implementation Details

### Backend Components

#### 1. **OpenAIService** (`app/Services/OpenAIService.php`)
**Purpose:** Encapsulates AI API interaction logic (supports Groq, OpenAI, and compatible providers)

**Key Methods:**
- `enhancePostContent(string $content, string $tone): array`
  - Takes original content and tone
  - Calls configured AI provider (Groq by default)
  - Returns enhanced content (max 200 chars)
  - Handles errors gracefully with specific messages

**Available Tones:**
```php
public const TONES = [
    'professional' => 'Professional and formal',
    'casual' => 'Casual and friendly',
    'enthusiastic' => 'Enthusiastic and energetic',
    'inspiring' => 'Inspiring and motivational',
    'humorous' => 'Humorous and lighthearted',
];
```

**API Configuration** (from `config/openai.php`):
- Model: Configurable via `OPENAI_MODEL` env variable
  - Default: `gpt-4o-mini` (OpenAI)
  - Recommended: `llama-3.1-8b-instant` (Groq - free, 14.4K requests/day)
  - Alternatives: `llama-3.3-70b-versatile`, `qwen/qwen3-32b`, etc.
- Max tokens: 100 (roughly 200 characters)
- Temperature: 0.7 (balanced creativity)
- Base URI: Configurable via `OPENAI_BASE_URI`
  - Groq: `https://api.groq.com/openai/v1`
  - OpenAI: (empty/default)
  - Together AI: `https://api.together.xyz/v1`
  - OpenRouter: `https://openrouter.ai/api/v1`

**Error Handling Improvements:**
- Specific error messages for rate limits, quota exceeded, authentication failures
- Prevents showing empty preview if API call fails
- Logs errors to Laravel log for debugging

#### 2. **SuggestPostContentRequest** (`app/Http/Requests/SuggestPostContentRequest.php`)
**Purpose:** Validates AI suggestion requests

**Validation Rules:**
```php
[
    'content' => ['required', 'string', 'min:5', 'max:500'],
    'tone' => ['required', 'string', 'in:professional,casual,enthusiastic,inspiring,humorous'],
]
```

#### 3. **PostController::suggestContent** (`app/Http/Controllers/PostController.php`)
**Purpose:** API endpoint handler for AI suggestions

**Route:** `POST /api/post/suggest-content`  
**Middleware:** `auth`, `verified`

**Response Format:**
```json
// Success
{
    "success": true,
    "enhanced_content": "Your enhanced post content here..."
}

// Error
{
    "success": false,
    "error": "Error message here"
}
```

#### 4. **Route Definition** (`routes/web.php`)
```php
Route::post('/api/post/suggest-content', [PostController::class, 'suggestContent'])
    ->middleware(['auth', 'verified'])->name('post.suggest-content');
```

---

### Frontend Components

#### 1. **CreatePostModal.vue** (`resources/js/components/app/CreatePostModal.vue`)

**New State Variables:**
```typescript
const selectedTone = ref<string>('professional');
const isLoadingSuggestion = ref(false);
const aiSuggestion = ref<string | null>(null);
const showAIPreview = ref(false); // Inline preview toggle
const suggestionError = ref<string | null>(null);
const hasGeneratedSuggestion = ref(false);
```

**New Computed Properties:**
- `plainTextContent`: Strips HTML from CKEditor content
- `canGenerateSuggestion`: Validates if AI enhancement is available (min 5 chars, not yet generated)

**New Functions:**
- `generateAISuggestion()`: Calls API to get enhanced content, shows inline preview
- `confirmSuggestion()`: Applies AI suggestion to editor, hides preview, allows editing
- `rejectSuggestion()`: Dismisses suggestion and allows retry
- `stripHtml(html)`: Removes HTML tags for plain text processing

**UI Components Added:**

1. **AI Enhancement Control Section** (in modal footer)
   - Tone selector dropdown
   - "AI Enhance" button with loading states
   - Error message display
   - Visual distinction with purple theme
   - SparklesIcon for AI branding

2. **Inline AI Preview Section** (appears below CKEditor when suggestion ready)
   - Shows within the same create post modal (v-if conditional rendering)
   - Purple-bordered card with white background
   - Side-by-side comparison: Original vs Enhanced
   - Character count indicator (X / 200 characters)
   - "Reject" and "Use This Content" buttons
   - Purple-themed card design (`border-2 border-purple-300 bg-purple-50`)
   - **No modal blocking** - user stays in create post flow
   - **Key improvement:** No z-index conflicts or Dialog nesting issues

**Critical Architecture Decision:**
The AI preview was redesigned from a blocking modal to an inline component to solve:
- Modal-over-modal z-index conflicts
- State management complexity when closing nested modals
- Poor UX of blocking the main create post modal
- State loss when accidentally closing the preview modal

---

## Technical Architecture

### Data Flow

```
┌─────────────────────────────────────────────────────────┐
│ 1. User Input                                           │
│    - Types content in CKEditor                          │
│    - Selects tone from dropdown                         │
│    - Clicks "AI Enhance"                                │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Frontend Processing (CreatePostModal.vue)           │
│    - Strip HTML tags to get plain text                 │
│    - Validate minimum 5 characters                      │
│    - Set loading state                                  │
│    - POST to /api/post/suggest-content                  │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 3. Backend Validation (SuggestPostContentRequest)      │
│    - Validate content (5-500 chars)                     │
│    - Validate tone (one of 5 options)                   │
│    - Authorize user (auth required)                     │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Controller (PostController::suggestContent)         │
│    - Inject OpenAIService                               │
│    - Call enhancePostContent()                          │
│    - Return JSON response                               │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 5. AI Processing (OpenAIService)                       │
│    - Build prompt with tone context                     │
│    - Call configured AI provider (Groq/OpenAI)         │
│    - Enforce 200-character limit                        │
│    - Handle errors and log issues                       │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ 6. Response Handling (Frontend)                        │
│    - Display inline preview within create post modal   │
│    - Show original vs enhanced comparison               │
│    - Set hasGeneratedSuggestion = true                  │
│    - User accepts → content updates in editor           │
│    - User rejects → preview hides, can try again        │
└─────────────────────────────────────────────────────────┘
```

---

## Configuration

### AI Provider Setup

The feature supports multiple AI providers via OpenAI-compatible APIs. **Groq is strongly recommended** (free with generous limits).

#### Option 1: Groq (Free - Recommended) 🆓

**Environment Variables (`.env`):**
```env
OPENAI_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_BASE_URI=https://api.groq.com/openai/v1
OPENAI_MODEL=llama-3.1-8b-instant
```

**Free Tier Limits (llama-3.1-8b-instant):**
- 30 requests/minute
- 14,400 requests/day
- 6,000 tokens/minute
- 500,000 tokens/day
- **Cost: $0 forever**

**Why Groq?**
✅ Completely free (no credit card)
✅ 14,400 daily requests (vs OpenAI's 200 on free tier)
✅ Fastest inference speeds available
✅ No expiring credits
✅ OpenAI-compatible API (drop-in replacement)

**Setup Guide:** See `docs/GROQ_SETUP.md` for complete instructions

#### Option 2: OpenAI (Paid)

**Environment Variables (`.env`):**
```env
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxx
OPENAI_ORGANIZATION=org-xxxxxxxxxxxxxxxx
# OPENAI_BASE_URI= (leave empty or unset)
OPENAI_MODEL=gpt-4o-mini
```

**Costs:**
- ~$0.00005 per enhancement (less than 0.01¢)
- ~$0.15/month for heavy usage

**Setup Guide:** See `docs/OPENAI_SETUP.md`

#### Configuration File

All settings are in `config/openai.php`:

```php
return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
    
    // Base URI determines which provider to use
    // Groq: https://api.groq.com/openai/v1
    // Together AI: https://api.together.xyz/v1
    // OpenRouter: https://openrouter.ai/api/v1
    // OpenAI: (leave empty)
    'base_uri' => env('OPENAI_BASE_URI'),
    
    // Model name (provider-specific)
    // Groq: llama-3.1-8b-instant, llama-3.3-70b-versatile
    // OpenAI: gpt-4o-mini, gpt-3.5-turbo
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
];
```

**Frontend Bootstrap:**
CSRF token protection is configured in `resources/js/bootstrap.ts`:
```typescript
import axios from 'axios';

// Set CSRF token for all axios requests
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content;
}
```

This file is imported in `app.ts` before any components load.

---

## Usage Examples

### Example 1: Professional Tone
**Original:**
```
excited to announce our new product launch
```

**Enhanced (Professional):**
```
I'm pleased to announce the launch of our innovative new product, marking a significant milestone for our company.
```

### Example 2: Casual Tone
**Original:**
```
check out this cool feature we built
```

**Enhanced (Casual):**
```
Hey everyone! Just finished building this awesome feature – can't wait for you to try it out! 🚀
```

### Example 3: Enthusiastic Tone
**Original:**
```
we won the award
```

**Enhanced (Enthusiastic):**
```
WE DID IT! 🎉 Thrilled to share that we've won the prestigious award – couldn't be more excited! This is HUGE for our team!
```

---

## Configuration Requirements

### Environment Variables
Ensure these are set in `.env`:
```env
OPENAI_API_KEY=sk-proj-...
OPENAI_ORGANIZATION=org-...
```

### Composer Dependencies
Already installed:
```json
"openai-php/laravel": "*"
```

---

## Error Handling

### Frontend Errors
- **Network failures**: Display user-friendly error message
- **API errors**: Show error from backend response
- **Validation errors**: Prevent submission if content too short

### Backend Errors
- **Invalid tone**: Return 422 with validation error
- **OpenAI API failure**: Log error, return generic message
- **Authentication**: Middleware enforces auth requirement

### Edge Cases Handled
✅ Empty content → Button disabled  
✅ Content < 5 chars → Button disabled  
✅ Already generated → Button shows "Generated" (disabled)  
✅ HTML content → Stripped to plain text for processing  
✅ Enhanced content > 200 chars → Truncated with "..."

---

## Testing Checklist

### Manual Testing
- [ ] Open Create Post modal
- [ ] Enter short text (< 5 chars) → Button should be disabled
- [ ] Enter valid text (≥ 5 chars) → Button should be enabled
- [ ] Select different tones → Dropdown should work
- [ ] Click "AI Enhance" → Loading indicator appears
- [ ] Wait for response → Preview modal appears
- [ ] Verify original vs enhanced content displayed
- [ ] Click "Reject" → Modal closes, can retry
- [ ] Click "Use This Content" → Content applied to editor
- [ ] Try to generate again → Button disabled (shows "Generated")
- [ ] Submit post with enhanced content → Post created successfully
- [ ] Test with invalid API key → Error message displayed
- [ ] Test with network offline → Error message displayed

### Automated Testing (Future)
```php
// Example Pest test structure
test('AI suggestion requires authenticated user')
    ->post('/api/post/suggest-content')
    ->assertStatus(401);

test('AI suggestion validates minimum content length')
    ->actingAs($user)
    ->post('/api/post/suggest-content', [
        'content' => 'Hi',
        'tone' => 'professional'
    ])
    ->assertStatus(422);

test('AI suggestion returns enhanced content')
    ->actingAs($user)
    ->post('/api/post/suggest-content', [
        'content' => 'This is a test post',
        'tone' => 'professional'
    ])
    ->assertSuccessful()
    ->assertJsonStructure(['success', 'enhanced_content']);
```

---

## UI/UX Design

### Color Scheme
- **Primary**: Purple (`purple-600`, `purple-700`) for AI branding
- **Backgrounds**: `purple-50` for highlights, `purple-100` for icons
- **Borders**: `purple-200` for distinction

### Icons
- **SparklesIcon** (`@heroicons/vue`) for AI features
- **Loading**: Animated emoji (⏳) during generation

### Accessibility
- ✅ Keyboard navigation support (via HeadlessUI Dialog)
- ✅ Disabled states clearly indicated
- ✅ Loading states announced visually
- ✅ Error messages in red with sufficient contrast

---

## Performance Considerations

### Optimization
- **API Call**: Async/await pattern prevents UI blocking
- **Token Limit**: Max 100 tokens (~200 chars) keeps responses fast
- **Debouncing**: Not needed (one-click generation)
- **Caching**: No caching (each suggestion is unique)

### Limitations
- **One generation per session**: Prevents API abuse
- **Character limit**: 200 chars enforces conciseness
- **No rate limiting**: Consider adding in production

---

## Security Considerations

### Implemented
✅ **Authentication required**: Middleware enforces `auth` + `verified`  
✅ **Input validation**: FormRequest validates all inputs  
✅ **HTML sanitization**: Existing Purifier handles post content  
✅ **Error logging**: OpenAI errors logged for debugging  
✅ **API key security**: Stored in `.env`, never exposed to frontend

### Best Practices
- API calls go through Laravel backend (never expose key to client)
- CSRF protection via Laravel (Inertia handles tokens)
- Content length limits prevent abuse

---

## Future Enhancements

### Potential Improvements
1. **Multiple suggestions**: Generate 2-3 variations for user choice
2. **Custom instructions**: Allow users to specify custom enhancement requests
3. **Usage analytics**: Track which tones are most popular
4. **Rate limiting**: Implement per-user API call limits
5. **A/B testing**: Measure engagement on AI-enhanced vs regular posts
6. **Language support**: Detect and enhance non-English content
7. **Regeneration with feedback**: Allow "make it shorter/longer/different"
8. **Token usage tracking**: Monitor OpenAI costs per user/organization

---

## Files Created/Modified

### New Files
1. `app/Services/OpenAIService.php` - AI service logic
2. `app/Http/Requests/SuggestPostContentRequest.php` - Validation
3. `docs/AI_POST_ENHANCEMENT.md` - This documentation

### Modified Files
1. `app/Http/Controllers/PostController.php` - Added `suggestContent()` method
2. `routes/web.php` - Added API route
3. `resources/js/components/app/CreatePostModal.vue` - Inline preview UI implementation
4. `config/openai.php` - Added `model` configuration option
5. `resources/js/bootstrap.ts` - Added CSRF token configuration for axios
6. `resources/js/app.ts` - Import bootstrap before other modules
7. `resources/views/app.blade.php` - Added CSRF token meta tag
8. `.env.example` - Added Groq configuration examples

### Documentation Files
1. `docs/AI_POST_ENHANCEMENT.md` - This comprehensive documentation
2. `docs/GROQ_SETUP.md` - Complete Groq setup guide with model comparison
3. `docs/OPENAI_SETUP.md` - Updated with Groq alternative and troubleshooting

---

## Dependencies

### Backend
- `openai-php/laravel` - OpenAI PHP client for Laravel
- Laravel 12 framework

### Frontend
- `axios` - HTTP client for API calls
- `@heroicons/vue` - SparklesIcon
- `@headlessui/vue` - Dialog components
- Vue 3 Composition API

---

## Troubleshooting

### Issue: "Failed to generate suggestion"
**Causes:**
- Invalid or missing API key
- Network connectivity issues
- API rate limits exceeded
- Service downtime
- Missing CSRF token

**Solutions:**
1. Verify `OPENAI_API_KEY` in `.env` (starts with `gsk_` for Groq, `sk-` for OpenAI)
2. Verify `OPENAI_BASE_URI` is set correctly for your provider
3. Check Laravel logs: `storage/logs/laravel.log`
4. Restart dev server after changing `.env` (`composer dev`)
5. Clear browser cache and hard refresh (`Ctrl+Shift+R`)
6. Check provider status page (Groq: https://status.groq.com)

### Issue: "OpenAI rate limit exceeded"
**For Groq:**
- 30 requests/minute limit - wait 1 minute
- 14,400 requests/day limit - wait until next day
- Switch to a different model if needed

**For OpenAI:**
- Free tier: 3 requests/minute, 200/day
- Solution: Switch to Groq or add OpenAI credits

### Issue: "Incorrect API key provided"
**Symptoms:** Error shows mismatched provider (e.g., Groq key sent to OpenAI)

**Cause:** `OPENAI_BASE_URI` not set correctly

**Solution:**
1. For Groq: Set `OPENAI_BASE_URI=https://api.groq.com/openai/v1`
2. For OpenAI: Leave `OPENAI_BASE_URI` empty or unset
3. Restart dev server: Stop (`Ctrl+C`) and run `composer dev`

### Issue: 422 Unprocessable Content
**Causes:**
- Missing CSRF token (fixed in current version)
- Validation failure (content < 5 chars or > 500 chars)
- Invalid tone value

**Solutions:**
1. Verify `resources/views/app.blade.php` has `<meta name="csrf-token">`
2. Verify `resources/js/bootstrap.ts` exists and sets axios CSRF header
3. Verify `resources/js/app.ts` imports bootstrap before other modules
4. Check browser console for CSRF token errors

### Issue: Button stays disabled
**Causes:**
- Content less than 5 characters
- Already generated suggestion once
- Form processing in progress

**Solutions:**
1. Type at least 5 characters
2. Refresh modal (close and reopen)
3. Wait for loading to complete

### Issue: Preview appears empty (0 / 200 characters)
**Cause:** API returned success but no content, or error wasn't caught

**Solution:**
1. This is now fixed - preview won't show if `enhanced_content` is empty
2. Error message will display instead
3. Check `storage/logs/laravel.log` for the actual error

### Issue: Create post modal closes when accepting AI suggestion
**Status:** ✅ Fixed in current version

**Previous Issue:** Separate preview modal caused state management conflicts

**Solution Implemented:** Redesigned to inline preview (no modal blocking)
**Causes:**
- API returned error
- JavaScript console errors
- Network request blocked
- Empty response from AI

---

## Conclusion

This feature successfully integrates AI-powered content enhancement into the post creation workflow using Groq (free) or OpenAI (paid) providers. The implementation follows Laravel and Vue.js best practices, includes comprehensive error handling, and provides an intuitive inline preview workflow that doesn't disrupt the user's creative process.

**Key Success Metrics:**
- ✅ Clean separation of concerns (Service, Request, Controller)
- ✅ Type-safe TypeScript implementation
- ✅ Responsive inline UI with no modal blocking
- ✅ Comprehensive error handling with specific messages
- ✅ CSRF protection via axios bootstrap
- ✅ Security best practices (API keys never exposed to frontend)
- ✅ One-time generation prevents API abuse
- ✅ 200-character limit enforced on both frontend and backend
- ✅ Multi-provider support (Groq, OpenAI, Together AI, OpenRouter)
- ✅ Free tier support via Groq (14,400 requests/day)