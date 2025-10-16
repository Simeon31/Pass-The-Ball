# UI/UX Visual Guide - Reactions & Comments

## Post with Reactions & Comments - Full View

```
┌─────────────────────────────────────────────────────────────┐
│  👤 John Doe                                          ⋮     │
│  Posted 2 hours ago                                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  This is a post with some content that users can react to   │
│  and comment on. It demonstrates the new features!          │
│                                                              │
│  [Image/Attachment Preview]                                 │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  👍 ❤️ 😂  15                              3 comments       │
├─────────────────────────────────────────────────────────────┤
│  ┌────────────┐  ┌────────────┐                            │
│  │  👍 Like   │  │ 💬 Comment │                            │
│  └────────────┘  └────────────┘                            │
└─────────────────────────────────────────────────────────────┘
```

## Reaction Picker - Popover Open

When user hovers over "Like" button:

```
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│  ┌──────────────────────────────────────────────┐          │
│  │  👍   ❤️   😂   😮   😢   😠               │          │
│  │ Like Love Haha  Wow  Sad Angry              │          │
│  └──────────────────────────────────────────────┘          │
│  ↓                                                          │
│  ┌────────────┐  ┌────────────┐                            │
│  │  👍 Like   │  │ 💬 Comment │                            │
│  └────────────┘  └────────────┘                            │
└─────────────────────────────────────────────────────────────┘
```

Features:
- ✨ Hover animation: emojis scale up (1.1x)
- 🏷️ Tooltips show on hover
- 🎨 Smooth transitions
- 🖱️ Click to select

## User Has Reacted - "Love" Selected

```
┌─────────────────────────────────────────────────────────────┐
│  👍 ❤️ 😂  15                              3 comments       │
├─────────────────────────────────────────────────────────────┤
│  ┌────────────┐  ┌────────────┐                            │
│  │  ❤️ Love   │  │ 💬 Comment │  ← Highlighted in red      │
│  └────────────┘  └────────────┘                            │
└─────────────────────────────────────────────────────────────┘
```

## Comments Section - Expanded

```
┌─────────────────────────────────────────────────────────────┐
│  👍 ❤️ 😂  15                              3 comments       │
├─────────────────────────────────────────────────────────────┤
│  ┌────────────┐  ┌────────────┐                            │
│  │  ❤️ Love   │  │ 💬 Comment │                            │
│  └────────────┘  └────────────┘                            │
├─────────────────────────────────────────────────────────────┤
│  👤  [Write a comment...]              [Post]              │
├─────────────────────────────────────────────────────────────┤
│  3 Comments                                                 │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐│
│  │ 👤 Jane Smith                                          ││
│  │ ┌────────────────────────────────────────────────────┐ ││
│  │ │ This is a comment! It can be quite long and will   │ ││
│  │ │ show a "See More" button if it exceeds...          │ ││
│  │ │ [See More]                                         │ ││
│  │ └────────────────────────────────────────────────────┘ ││
│  │ 10 minutes ago  Delete                                 ││
│  └────────────────────────────────────────────────────────┘│
│                                                              │
│  ┌────────────────────────────────────────────────────────┐│
│  │ 👤 Bob Johnson                                         ││
│  │ ┌────────────────────────────────────────────────────┐ ││
│  │ │ Great post! 🎉                                     │ ││
│  │ └────────────────────────────────────────────────────┘ ││
│  │ 1 hour ago                                             ││
│  └────────────────────────────────────────────────────────┘│
│                                                              │
│  [Load More Comments]                                       │
└─────────────────────────────────────────────────────────────┘
```

## Comment "See More" - Expanded

```
│  ┌────────────────────────────────────────────────────────┐│
│  │ 👤 Jane Smith                                          ││
│  │ ┌────────────────────────────────────────────────────┐ ││
│  │ │ This is a comment! It can be quite long and will   │ ││
│  │ │ show a "See More" button if it exceeds 100         │ ││
│  │ │ characters. When clicked, it expands to show the   │ ││
│  │ │ full content of the comment with proper formatting │ ││
│  │ │ and line breaks preserved.                         │ ││
│  │ │ [Show Less]                                        │ ││
│  │ └────────────────────────────────────────────────────┘ ││
│  │ 10 minutes ago  Delete                                 ││
│  └────────────────────────────────────────────────────────┘│
```

## Interaction States

### 1. Reaction Button States

**Default (No reaction)**
```
┌────────────┐
│  👍 Like   │  ← Gray background
└────────────┘
```

**Hover**
```
┌────────────┐
│  👍 Like   │  ← Darker gray background
└────────────┘
```

**Active (User reacted)**
```
┌────────────┐
│  ❤️ Love   │  ← Colored (red for love, blue for like, etc.)
└────────────┘
```

### 2. Comment Input States

**Empty**
```
👤  [Write a comment...]              [Post]
                                       ↑ disabled
```

**With Text**
```
👤  [This is my comment]               [Post]
                                       ↑ enabled, blue
```

**Submitting**
```
👤  [This is my comment]               [Posting...]
                                       ↑ disabled
```

### 3. Load More Button States

**Default**
```
┌──────────────────────┐
│  Load More Comments  │
└──────────────────────┘
```

**Loading**
```
┌──────────────────────┐
│     Loading...       │
└──────────────────────┘
```

**No more comments**
```
(Button hidden)
```

## Animations & Transitions

### Reaction Picker
- **Appear**: Fade in + slide up (300ms ease-out)
- **Emoji Hover**: Scale 1.0 → 1.25 (200ms ease)
- **Tooltip**: Fade in (100ms)

### Comments
- **New Comment**: Slide in from top (300ms ease-out)
- **See More**: Smooth expand (200ms ease)
- **Delete**: Fade out + slide left (200ms ease-in)

### Real-time Updates
- **New Reaction**: Pulse animation on count (500ms)
- **New Comment**: Slide in with highlight (300ms)

## Responsive Behavior

### Desktop (>1024px)
- Full width buttons
- Side-by-side reaction/comment buttons
- Comfortable spacing

### Tablet (768px - 1024px)
- Slightly smaller buttons
- Maintained side-by-side layout
- Adjusted padding

### Mobile (<768px)
- Full width comment input
- Reaction picker adjusts to screen
- Optimized touch targets (min 44px)

## Color Scheme

### Reaction Colors
- **Like**: `text-blue-500` (#3B82F6)
- **Love**: `text-red-500` (#EF4444)
- **Haha**: `text-yellow-500` (#EAB308)
- **Wow**: `text-purple-500` (#A855F7)
- **Sad**: `text-blue-400` (#60A5FA)
- **Angry**: `text-orange-500` (#F97316)

### UI Elements
- **Background**: `bg-white`
- **Borders**: `border-gray-300`
- **Hover**: `bg-gray-100` → `bg-gray-200`
- **Text**: `text-gray-800` (primary), `text-gray-500` (secondary)
- **Accent**: `bg-indigo-600` (buttons)

## Accessibility Features

1. **Keyboard Navigation**
   - Tab through reactions
   - Enter to select
   - Escape to close popover

2. **Screen Readers**
   - ARIA labels on all buttons
   - Semantic HTML structure
   - Descriptive alt text

3. **Visual Feedback**
   - Focus rings on interactive elements
   - High contrast text
   - Clear hover states

4. **Touch Targets**
   - Minimum 44px for mobile
   - Adequate spacing between elements
   - No tiny click areas

## Performance Optimizations

1. **Lazy Loading**
   - Comments load 5 at a time
   - Images lazy loaded
   - Virtual scrolling for long lists

2. **Debouncing**
   - Comment input debounced
   - Reaction API calls optimized

3. **Caching**
   - Recent comments cached
   - Reaction state cached locally

4. **Real-time**
   - WebSocket connection pooling
   - Event batching for multiple updates
   - Automatic reconnection
