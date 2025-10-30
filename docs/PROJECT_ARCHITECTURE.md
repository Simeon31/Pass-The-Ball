# Pass The Ball - Project Architecture

## Overview

**Pass The Ball** is a social media platform built with Laravel 12 + Inertia.js 2 + Vue 3 (TypeScript) + Tailwind CSS 4.

**Architecture:** Inertia.js hybrid SPA (no separate API, no client-side routing)
- **Backend:** Laravel handles routing, controllers, models, validation
- **Frontend:** Vue 3 SFCs with Composition API + TypeScript
- **Bridge:** Inertia.js manages page transitions without full page reloads

---

## Directory Structure

```
pass_the_ball/
│
├── app/                          # Laravel Backend (PHP)
│   ├── Console/                  # Artisan CLI commands
│   ├── Enums/                    # Type-safe constants (notification types, roles)
│   ├── Events/                   # Broadcasting events for real-time updates
│   ├── Http/
│   │   ├── Controllers/          # Route handlers returning Inertia responses
│   │   ├── Middleware/           # Request filters (auth, Inertia shared data)
│   │   ├── Requests/             # FormRequest validation classes
│   │   └── Resources/            # API transformers (Eloquent → JSON)
│   ├── Models/                   # Eloquent ORM models (User, Post, Comment, Group, etc.)
│   ├── Notifications/            # Email/database/broadcast notification classes
│   ├── Policies/                 # Authorization logic (user permissions)
│   ├── Providers/                # Service provider bootstrapping
│   └── Services/                 # Business logic (image optimization, AI enhancement)
│
├── bootstrap/                    # Application initialization
│   ├── app.php                   # Creates Laravel instance
│   ├── providers.php             # Registers service providers
│   └── cache/                    # Cached config/routes (auto-generated)
│
├── config/                       # Configuration files (.env values)
│   ├── app.php                   # Core settings (timezone, locale, debug)
│   ├── auth.php                  # Authentication guards
│   ├── broadcasting.php          # Pusher/Echo WebSocket config
│   ├── database.php              # Database connections (SQLite default)
│   ├── filesystems.php           # Storage disks (local, S3, public)
│   ├── fortify.php               # Authentication features (2FA, password reset)
│   ├── inertia.php               # Inertia.js server config
│   ├── openai.php                # OpenAI API for AI features
│   ├── purifier.php              # HTML sanitization (XSS protection)
│   ├── queue.php                 # Queue drivers for background jobs
│   └── services.php              # Third-party service credentials
│
├── database/
│   ├── migrations/               # Version-controlled database schema
│   ├── factories/                # Faker factories for test data
│   └── seeders/                  # Database seeders for initial/demo data
│
├── docs/                         # Technical documentation (markdown guides)
│   ├── FLASH_MESSAGES_FLOW.md
│   ├── GROUPS_FEATURE_GUIDE.md
│   ├── NOTIFICATIONS_SYSTEM.md
│   ├── PHOTO_GALLERY_FEATURE.md
│   ├── POLYMORPHIC_REACTIONS.md
│   └── ... (20+ feature/architecture docs)
│
├── public/                       # Web root (publicly accessible)
│   ├── index.php                 # Entry point for all HTTP requests
│   ├── build/                    # Compiled Vite assets (JS/CSS bundles)
│   ├── images/                   # Static image assets
│   └── storage/                  # Symlink to /storage/app/public (user uploads)
│
├── resources/
│   ├── css/                      # Global CSS/Tailwind entry points
│   ├── js/                       # Vue 3 Frontend (TypeScript)
│   │   ├── actions/              # Reusable Inertia form actions
│   │   ├── components/
│   │   │   ├── ui/               # Reka UI primitives (Button, Dialog, Input)
│   │   │   ├── app/              # Feature components (CreatePost, PostList)
│   │   │   └── groups/           # Group-specific components
│   │   ├── composables/          # Reusable composition functions (useFlashMessage)
│   │   ├── layouts/              # Page layouts (AppLayout, AuthLayout)
│   │   ├── lib/                  # Utility libraries (cn() for class merging)
│   │   ├── pages/                # Inertia page components (Dashboard, Profile)
│   │   ├── routes/               # Auto-generated TypeScript route helpers (Wayfinder)
│   │   ├── types/                # TypeScript type definitions (User, Post, etc.)
│   │   ├── app.ts                # Inertia app initialization
│   │   ├── bootstrap.ts          # Axios/Laravel Echo setup
│   │   ├── echo.ts               # WebSocket configuration
│   │   └── ssr.ts                # Server-side rendering entry
│   └── views/
│       └── app.blade.php         # Root HTML template (mounts Vue app)
│
├── routes/                       # Laravel route definitions
│   ├── web.php                   # Main app routes (posts, groups, profiles)
│   ├── auth.php                  # Authentication routes (Fortify)
│   ├── settings.php              # User settings routes
│   └── console.php               # Artisan command routes
│
├── storage/                      # Private file storage
│   ├── app/
│   │   └── public/               # User uploads (symlinked to /public/storage)
│   ├── framework/                # Cache, sessions, compiled views
│   └── logs/                     # Application logs (daily rotation)
│
├── tests/                        # Pest PHP test suite
│   ├── Feature/                  # End-to-end tests (HTTP, database)
│   ├── Unit/                     # Isolated unit tests (models, services)
│   ├── Pest.php                  # Pest configuration
│   └── TestCase.php              # Base test class
│
├── vendor/                       # Composer dependencies (gitignored)
│
├── composer.json                 # PHP dependencies & autoloading
├── package.json                  # Node.js dependencies & scripts
├── vite.config.ts                # Vite build configuration
├── tsconfig.json                 # TypeScript compiler options
├── eslint.config.js              # ESLint code quality rules
├── components.json               # Reka UI component library config
├── phpunit.xml                   # Pest PHP test configuration
└── .env                          # Environment variables (not in repo)
```

---

## Key Technologies

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | Laravel 12 | MVC framework, routing, ORM, authentication |
| **Frontend** | Vue 3 + TypeScript | Reactive UI with Composition API |
| **Bridge** | Inertia.js 2 | SPA experience without client-side routing |
| **Styling** | Tailwind CSS 4 | Utility-first CSS framework |
| **UI Components** | Reka UI | Radix Vue wrapper components |
| **Database** | SQLite/MySQL | Relational database (SQLite default) |
| **Real-time** | Laravel Echo + Pusher | WebSocket broadcasting |
| **Build Tool** | Vite 7 | Fast frontend asset bundling |
| **Testing** | Pest PHP | Modern PHP testing framework |
| **Image Processing** | Intervention Image | Image optimization/resizing |
| **Rich Text** | CKEditor 5 | WYSIWYG editor with HTML sanitization |
| **AI** | OpenAI SDK | AI-powered post enhancements |

---

## Data Flow

### Request Lifecycle (Inertia)
1. User action → Vue component
2. Inertia `useForm()` → TypeScript route helper → Laravel route
3. Controller → FormRequest validation → Model/Service
4. Controller returns Inertia response (page data, not JSON)
5. Inertia swaps Vue component props (no full page reload)

### Real-time Broadcasting
1. Laravel event dispatched → Pusher WebSocket
2. Laravel Echo listener (frontend) → Vue reactivity updates UI

---

## Development Commands

```bash
# Setup
composer install && npm install
php artisan key:generate && php artisan migrate --seed

# Dev server (all-in-one)
composer dev  # Runs: serve + queue:listen + vite dev

# Code quality
composer pint  # PHP formatting (Laravel Pint)
npm run lint   # TypeScript/Vue linting
npm run format # Prettier formatting

# Testing
php artisan test  # Pest PHP tests
```

---

## Further Reading

See `/docs` directory for detailed feature guides:
- Flash Messages, Notifications, Groups, Gallery, Comments, Reactions
- Testing patterns, UI/UX conventions, troubleshooting guides
