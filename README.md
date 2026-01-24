# Pass The Ball 🏀

A modern social media platform built with Laravel, Inertia.js, Vue 3, and TypeScript.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 🌟 Overview

**Pass The Ball** is a feature-rich social media platform that combines the power of Laravel's backend with the reactivity of Vue 3's Composition API, seamlessly bridged by Inertia.js for a smooth SPA experience without the complexity of separate API endpoints or client-side routing.

### Key Features

- 📝 **Posts & Content Creation** - Create, edit, and delete posts with rich text editing via CKEditor 5
- 💬 **Comments System** - Nested comments with real-time updates
- 👍 **Reactions** - 6 different reaction types (Like, Love, Care, Haha, Wow, Sad) with polymorphic support
- 👥 **Groups** - Create and manage groups with invitations and join requests
- 📸 **Photo Gallery** - Upload and manage photos with automatic optimization
- 🔔 **Real-time Notifications** - Instant notifications via Laravel Echo and Pusher
- 🤖 **AI Post Enhancement** - AI-powered content improvement using Groq
- 👤 **User Profiles** - Customizable profiles with avatar support
- 🔐 **Authentication** - Secure authentication with 2FA support via Laravel Fortify
- ♾️ **Infinite Scroll** - Smooth infinite scrolling for posts and content
- 🌙 **Dark Mode** - Built-in appearance customization
- 🔄 **Follow System** - Follow/unfollow users with real-time broadcasting
- 📎 **Post Attachments** - Attach files to posts

## 🛠️ Tech Stack

### Backend
- **Laravel 12** - PHP framework with elegant syntax
- **MariaDB** - Database (SQLite default for easy test setup)
- **Laravel Fortify** - Authentication scaffolding with 2FA
- **Laravel Echo** - Real-time event broadcasting
- **Intervention Image** - Image processing and optimization
- **Pusher** - WebSocket broadcasting (or Laravel WebSockets for local dev)

### Frontend
- **Vue 3** - Progressive JavaScript framework with Composition API
- **TypeScript** - Type-safe JavaScript
- **Inertia.js 2** - Modern monolith approach (no separate API needed)
- **Tailwind CSS 4** - Utility-first CSS framework
- **Reka UI/Headless UI** - Radix Vue wrapper components for accessible UI primitives
- **CKEditor 5** - Rich text editor with HTML sanitization
- **Vite 7** - Lightning-fast build tool

### Development Tools
- **Pest PHP** - Modern testing framework
- **Laravel Pint** - Opinionated PHP code formatter
- **ESLint** - JavaScript/TypeScript linting
- **Prettier** - Code formatting
- **Laravel Wayfinder** - Type-safe route generation

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2
- **Composer** >= 2.0
- **Node.js** >= 18.x
- **npm** >= 9.x
- **SQLite** (default) or **MySQL**
- **Git**

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/Simeon31/Pass-The-Ball.git
cd Pass-The-Ball
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite
```

### 4. Configure Environment

Edit `.env` file and configure your settings:

```env
APP_NAME="Pass The Ball"
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

# For real-time features (optional - can use log driver for development)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# (Optional) Seed the database with sample data
php artisan db:seed
```

### 6. Storage Link

```bash
# Create symbolic link for file storage
php artisan storage:link
```

### 7. Start Development Server

```bash
# All-in-one command (runs server + queue worker + Vite)
composer dev
```

**Or run separately:**

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Queue worker (for real-time features)
php artisan queue:listen

# Terminal 3: Vite dev server (for asset compilation)
npm run dev
```

Visit **http://localhost:8000** in your browser! 🎉

## ⚙️ Configuration

### Real-time Broadcasting Setup

#### Option A: Pusher (Recommended for Production)

1. Create a free account at [pusher.com](https://pusher.com)
2. Create a new app in the Pusher dashboard
3. Copy credentials to `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### Option B: Laravel WebSockets (Local Development)

```bash
# Install Laravel WebSockets
composer require beyondcode/laravel-websockets

# Publish configuration
php artisan websockets:install

# Run WebSocket server
php artisan websockets:serve
```

Update `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local

VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
```

### AI Features (Optional)

**Option A: OpenAI (Paid)**

```env
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...
```

**Option B: Groq (Free - Recommended)**

```env
OPENAI_API_KEY=gsk_...
OPENAI_BASE_URI=https://api.groq.com/openai/v1
OPENAI_MODEL=llama-3.1-8b-instant
```

Get your free API key at [console.groq.com](https://console.groq.com/keys)

## 📜 Available Scripts

### Development

```bash
composer dev          # Start all dev servers (Laravel + Queue + Vite)
composer dev:ssr      # Start with SSR support
npm run dev           # Run Vite dev server only
```

### Building

```bash
npm run build         # Build for production
npm run build:ssr     # Build with SSR support
```

### Code Quality

```bash
composer pint         # Format PHP code (Laravel Pint)
./vendor/bin/pint     # Alternative Pint command

npm run lint          # Lint and fix JS/TS/Vue files
npm run format        # Format code with Prettier
npm run format:check  # Check formatting without changes
```

### Testing

```bash
composer test         # Run all Pest PHP tests
php artisan test      # Alternative test command
```

### Database

```bash
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Drop all tables and re-run migrations
php artisan db:seed           # Seed database
php artisan migrate:fresh --seed  # Fresh migration + seed
```

## 📁 Project Structure

```
Pass-The-Ball/
├── app/                    # Laravel backend
│   ├── Console/           # Artisan commands
│   ├── Enums/             # Type-safe constants
│   ├── Events/            # Broadcasting events
│   ├── Http/
│   │   ├── Controllers/   # Route handlers
│   │   ├── Middleware/    # Request filters
│   │   ├── Requests/      # Form validation
│   │   └── Resources/     # API transformers
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization logic
│   └── Services/          # Business logic
│
├── resources/
│   ├── js/                # Vue 3 frontend
│   │   ├── actions/       # Inertia form actions
│   │   ├── components/    # Vue components
│   │   │   ├── ui/       # UI primitives (Reka UI)
│   │   │   ├── app/      # App components
│   │   │   └── groups/   # Group components
│   │   ├── composables/   # Composition functions
│   │   ├── layouts/       # Page layouts
│   │   ├── pages/         # Inertia pages
│   │   ├── routes/        # Auto-generated routes (Wayfinder)
│   │   └── types/         # TypeScript definitions
│   ├── css/               # Tailwind CSS
│   └── views/             # Blade templates
│
├── routes/
│   ├── web.php           # Main app routes
│   ├── auth.php          # Authentication routes
│   └── settings.php      # User settings routes
│
├── database/
│   ├── migrations/       # Database schema
│   ├── factories/        # Test data factories
│   └── seeders/          # Database seeders
│
├── tests/                # Pest PHP tests
│   ├── Feature/         # Integration tests
│   └── Unit/            # Unit tests
│
├── docs/                # Documentation
│   ├── PROJECT_ARCHITECTURE.md
│   ├── FLASH_MESSAGES_FLOW.md
│   ├── GROUPS_FEATURE_GUIDE.md
│   ├── NOTIFICATIONS_SYSTEM.md
│   └── ... (30+ guides)
│
├── public/              # Web root
│   ├── build/          # Compiled assets
│   └── storage/        # Symlink to storage
│
└── storage/            # File storage
    ├── app/public/    # User uploads
    └── logs/          # Application logs
```

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/PostTest.php

# Run with coverage (requires Xdebug/PCOV)
php artisan test --coverage
```

For detailed testing guidelines, see [docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md)

## 📚 Documentation

Comprehensive documentation is available in the `docs/` directory:

### Getting Started
- [Project Architecture](docs/PROJECT_ARCHITECTURE.md) - System overview and tech stack
- [Quick Start Checklist](docs/QUICKSTART_CHECKLIST.md) - Step-by-step setup guide

### Features
- [Groups Feature Guide](docs/GROUPS_FEATURE_GUIDE.md) - Groups, invitations, and members
- [Notifications System](docs/NOTIFICATIONS_SYSTEM.md) - Real-time notifications setup
- [Photo Gallery](docs/PHOTO_GALLERY_FEATURE.md) - Image upload and gallery
- [Reactions & Comments](docs/REACTIONS_COMMENTS_FEATURE.md) - Social interactions
- [Follow System](docs/FOLLOW_UNFOLLOW_FEATURE.md) - User following
- [Infinite Scroll](docs/INFINITE_SCROLL_FEATURE.md) - Pagination and infinite loading

### Development
- [Flash Messages Flow](docs/FLASH_MESSAGES_FLOW.md) - Flash message architecture
- [UI/UX Guide](docs/UI_UX_GUIDE.md) - Design system and conventions
- [HTML Sanitization](docs/HTML_SANITIZATION.md) - XSS protection
- [Testing Guide](docs/TESTING_GUIDE.md) - Testing patterns

### AI Features
- [AI Post Enhancement](docs/AI_POST_ENHANCEMENT.md) - OpenAI/Groq integration
- [Groq Setup](docs/GROQ_SETUP.md) - Free AI alternative configuration

## 🔧 Development Workflow

### Making Changes

1. **Backend Changes** (Laravel)
   - Edit controllers, models, or routes
   - Run migrations if database changes needed
   - Test with Pest PHP

2. **Frontend Changes** (Vue/TypeScript)
   - Edit components in `resources/js/`
   - Vite hot-reloads automatically
   - Check browser console for errors

3. **Route Changes**
   - Update `routes/web.php`
   - Restart dev server to regenerate TypeScript routes
   - Import routes from `@/routes/` in Vue components

### Code Style

This project follows:
- **Laravel** coding standards (via Pint)
- **Airbnb** JavaScript style guide (via ESLint)
- **Prettier** for consistent formatting

Always run linting before committing:

```bash
composer pint
npm run lint
npm run format
```

## 🐛 Troubleshooting

### Common Issues

**"Mix Manifest Not Found"**
```bash
npm run build
```

**"Class not found" errors**
```bash
composer dump-autoload
```

**Queue jobs not processing**
```bash
# Make sure queue worker is running
php artisan queue:listen
```

**WebSocket connection failed**
- Check Pusher credentials in `.env`
- Ensure queue worker is running
- Verify firewall allows WebSocket connections

**TypeScript errors after route changes**
```bash
# Restart Vite dev server
npm run dev
```

For more troubleshooting, see:
- [Notifications Troubleshooting](docs/NOTIFICATIONS_TROUBLESHOOTING.md)
- [Testing Guide](docs/TESTING_GUIDE.md)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

- Follow existing code style and conventions
- Run tests before submitting PR
- Update documentation for new features
- Ensure all linters pass (`composer pint`, `npm run lint`)

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Simeon Markov

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework for web artisans
- [Vue.js](https://vuejs.org) - The progressive JavaScript framework
- [Inertia.js](https://inertiajs.com) - The modern monolith
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Reka UI](https://reka-ui.com) - Accessible Vue components

## 📧 Support

For questions or issues:
- Check the [documentation](docs/)
- Open an issue on GitHub
- Review existing issues for solutions
