# Photo Gallery Feature

## Overview
The Photo Gallery feature provides a comprehensive photo management system allowing users to organize photos into albums with advanced features including multi-size image optimization, EXIF metadata extraction, visibility controls, tagging, and infinite scroll. Users can create public/private albums, upload photos in batches, and view them in a full-screen lightbox with zoom controls.

## Table of Contents
- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Backend Components](#backend-components)
- [Frontend Components](#frontend-components)
- [Image Processing](#image-processing)
- [API Endpoints](#api-endpoints)
- [TypeScript Interfaces](#typescript-interfaces)
- [Usage Examples](#usage-examples)
- [Configuration](#configuration)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Architecture

### Tech Stack
- **Backend**: Laravel 12 + Intervention Image v3
- **Frontend**: Vue 3 (Composition API) + TypeScript + Inertia.js 2
- **UI Components**: Reka UI (Radix Vue)
- **Routing**: Laravel Wayfinder (TypeScript route generation)
- **Storage**: Laravel Storage facade (supports local & cloud)

### Key Features
✅ **Album Management**: Create, update, delete albums with soft deletes  
✅ **Batch Photo Upload**: Upload up to 20 photos at once (max 10MB each)  
✅ **Multi-Size Generation**: Thumbnail (300x300), Medium (800x600), Large (1920x1080), Original  
✅ **EXIF Metadata**: Automatic extraction of camera data, GPS coordinates  
✅ **Visibility Controls**: Public, Private, Followers Only, Link Only  
✅ **Photo Tags**: Categorize photos with reusable tags  
✅ **View/Download Tracking**: Analytics for photo engagement  
✅ **Infinite Scroll**: Efficient loading for large albums  
✅ **Lightbox Viewer**: Full-screen photo viewer with zoom, navigation, metadata display  
✅ **SEO-Friendly URLs**: Slug-based routes for albums and photos  

## Database Schema

### Albums Table
```sql
CREATE TABLE albums (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    visibility ENUM('public', 'private', 'followers_only', 'link_only') DEFAULT 'public',
    cover_path VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_visibility (visibility)
);
```

### Photos Table
```sql
CREATE TABLE photos (
    id BIGINT UNSIGNED PRIMARY KEY,
    album_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NOT NULL,
    medium_path VARCHAR(255) NOT NULL,
    large_path VARCHAR(255) NOT NULL,
    original_file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    size BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    views_count BIGINT UNSIGNED DEFAULT 0,
    downloads_count BIGINT UNSIGNED DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_album_id (album_id),
    INDEX idx_user_id (user_id),
    INDEX idx_views_count (views_count),
    INDEX idx_downloads_count (downloads_count)
);
```

### Photo Tags Table
```sql
CREATE TABLE photo_tags (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_slug (slug)
);

CREATE TABLE photo_photo_tag (
    photo_id BIGINT UNSIGNED NOT NULL,
    photo_tag_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_tag_id) REFERENCES photo_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (photo_id, photo_tag_id)
);
```

## Backend Components

### 1. Models

#### **Album Model** (`app/Models/Album.php`)
**Traits**: `SoftDeletes`, `HasSlug` (Spatie)  
**Fillable**: `user_id`, `title`, `description`, `visibility`, `cover_path`  
**Casts**: `created_at` → `datetime`

**Relationships**:
```php
public function user(): BelongsTo  // Owner
public function photos(): HasMany  // Album photos
```

**Accessors**:
```php
public function getCoverUrlAttribute(): ?string  // Full URL to cover image
```

**Scopes**:
```php
public function scopeVisible($query, $userId = null)  // Filter by visibility
public function scopeAccessibleTo($query, $userId)    // Check access rights
public function scopeMostViewed($query)               // Order by photo views
```

**Slug Configuration**:
- Generated from `title` field
- Auto-updates on title change

#### **Photo Model** (`app/Models/Photo.php`)
**Traits**: `SoftDeletes`, `HasSlug`  
**Fillable**: `album_id`, `user_id`, `title`, `description`, `file_path`, `thumbnail_path`, `medium_path`, `large_path`, `original_file_path`, `mime_type`, `size`, `width`, `height`, `metadata`  
**Casts**: `metadata` → `array`, `created_at` → `datetime`

**Relationships**:
```php
public function album(): BelongsTo       // Parent album
public function user(): BelongsTo        // Owner
public function tags(): BelongsToMany    // Photo tags
```

**Accessors**:
```php
public function getThumbnailUrlAttribute(): string  // 300x300 thumbnail
public function getMediumUrlAttribute(): string     // 800x600 medium
public function getLargeUrlAttribute(): string      // 1920x1080 large
public function getOriginalUrlAttribute(): string   // Original size
```

**Scopes**:
```php
public function scopeMostViewed($query)  // Order by views_count DESC
public function scopeRecent($query)      // Order by created_at DESC
```

#### **PhotoTag Model** (`app/Models/PhotoTag.php`)
**Fillable**: `name`, `slug`  
**Relationships**:
```php
public function photos(): BelongsToMany  // Tagged photos
```

### 2. Resources

#### **AlbumResource** (`app/Http/Resources/AlbumResource.php`)
```php
[
    'id' => $this->id,
    'title' => $this->title,
    'slug' => $this->slug,
    'description' => $this->description,
    'visibility' => $this->visibility,
    'cover_url' => $this->cover_url,
    'photos_count' => $this->photos->count(),
    'user' => new UserResource($this->whenLoaded('user')),
    'created_at' => $this->created_at,
]
```

#### **PhotoResource** (`app/Http/Resources/PhotoResource.php`)
```php
[
    'id' => $this->id,
    'album_id' => $this->album_id,
    'title' => $this->title,
    'slug' => $this->slug,
    'description' => $this->description,
    'thumbnail_url' => $this->thumbnail_url,
    'medium_url' => $this->medium_url,
    'large_url' => $this->large_url,
    'original_url' => $this->original_url,
    'mime_type' => $this->mime_type,
    'size' => $this->size,
    'width' => $this->width,
    'height' => $this->height,
    'views_count' => $this->views_count,
    'downloads_count' => $this->downloads_count,
    'metadata' => $this->metadata,
    'tags' => PhotoTagResource::collection($this->whenLoaded('tags')),
    'user' => new UserResource($this->whenLoaded('user')),
    'created_at' => $this->created_at,
]
```

### 3. Form Requests

#### **StoreAlbumRequest** (`app/Http/Requests/StoreAlbumRequest.php`)
```php
[
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string', 'max:5000'],
    'visibility' => ['required', 'in:public,private,followers_only,link_only'],
    'cover' => ['nullable', 'image', 'max:5120'], // 5MB max
]
```

#### **StorePhotoRequest** (`app/Http/Requests/StorePhotoRequest.php`)
```php
[
    'photos' => ['required', 'array', 'max:20'],
    'photos.*' => ['required', 'image', 'max:10240'], // 10MB max
    'titles' => ['nullable', 'array'],
    'titles.*' => ['nullable', 'string', 'max:255'],
    'descriptions' => ['nullable', 'array'],
    'descriptions.*' => ['nullable', 'string', 'max:5000'],
    'tags' => ['nullable', 'array'],
    'tags.*' => ['array'],
    'tags.*.*' => ['string', 'max:100'],
]
```

### 4. Services

#### **ImageOptimizationService** (`app/Services/ImageOptimizationService.php`)

**Methods**:

**`processPhoto(UploadedFile $file, string $path): array`**
- Generates 4 image sizes: thumbnail (300x300), medium (800x600), large (1920x1080), original
- Extracts EXIF metadata (camera, lens, settings, GPS)
- Returns paths and metadata array
- Uses JPEG compression at 85% quality
- Maintains aspect ratio for all resizes

**`processCoverImage(UploadedFile $file, string $path): string`**
- Creates optimized cover image (1200x675)
- Crops to center if needed
- Returns storage path

**`deletePhoto(array $paths): void`**
- Removes all photo variants from storage
- Used when deleting photos

**Protected `extractMetadata(UploadedFile $file): array`**
- Reads EXIF data from uploaded file
- Extracts: Make, Model, Lens, Focal Length, Aperture, Shutter Speed, ISO, GPS
- Returns sanitized metadata array

### 5. Controllers

#### **AlbumController** (`app/Http/Controllers/AlbumController.php`)

**`index(User $user)`** - GET `/profile/{username}/gallery`
- Lists user's albums (paginated, 12 per page)
- Filters by visibility based on viewer
- Supports search query parameter
- Returns Inertia response: `Gallery/Index`

**`store(StoreAlbumRequest $request)`** - POST `/profile/{username}/gallery`
- Creates new album
- Processes optional cover image via ImageOptimizationService
- Redirects to gallery index
- Flash message: "Album created successfully."

**`show(User $user, Album $album)`** - GET `/profile/{username}/gallery/{album_slug}`
- Shows album with photos (paginated, 24 per page)
- Checks visibility access
- Eager loads photos with tags
- Returns Inertia response: `Gallery/Show`

**`update(UpdateAlbumRequest $request, User $user, Album $album)`** - PUT `/profile/{username}/gallery/{album_slug}`
- Updates album details
- Replaces cover image if provided
- Deletes old cover if replaced
- Flash message: "Album updated successfully."

**`destroy(User $user, Album $album)`** - DELETE `/profile/{username}/gallery/{album_slug}`
- Soft deletes album (cascade deletes photos)
- Deletes cover image file
- Redirects to gallery index
- Flash message: "Album deleted successfully."

#### **PhotoController** (`app/Http/Controllers/PhotoController.php`)

**`store(StorePhotoRequest $request, User $user, Album $album)`** - POST `/profile/{username}/gallery/{album_slug}/photos`
- Batch processes up to 20 photos
- Generates 4 sizes per photo via ImageOptimizationService
- Extracts EXIF metadata
- Creates/attaches tags with slug generation
- Stores in `storage/app/public/photos/{user_id}/{album_slug}/`
- Flash message: "{count} photo(s) uploaded successfully."

**`show(User $user, Album $album, Photo $photo)`** - GET `/profile/{username}/gallery/{album_slug}/{photo_slug}`
- Displays single photo
- Returns Inertia response: `Gallery/PhotoShow`

**`incrementView(User $user, Album $album, Photo $photo)`** - POST `/profile/{username}/gallery/{album_slug}/{photo_slug}/view`
- Increments `views_count`
- Returns JSON: `{'views_count': 123}`

**`update(UpdatePhotoRequest $request, User $user, Album $album, Photo $photo)`** - PUT `/profile/{username}/gallery/{album_slug}/{photo_slug}`
- Updates title, description
- Syncs tags (creates new, attaches existing)
- Flash message: "Photo updated successfully."

**`destroy(User $user, Album $album, Photo $photo)`** - DELETE `/profile/{username}/gallery/{album_slug}/{photo_slug}`
- Soft deletes photo
- Deletes all 4 image files via ImageOptimizationService
- Flash message: "Photo deleted successfully."

**`download(User $user, Album $album, Photo $photo)`** - GET `/profile/{username}/gallery/{album_slug}/{photo_slug}/download`
- Increments `downloads_count`
- Returns original file download
- Filename: `{album-slug}_{photo-slug}.{ext}`

## Frontend Components

### 1. Pages

#### **Gallery/Index.vue** (`resources/js/pages/Gallery/Index.vue`)
Album grid view with search and pagination.

**Props**:
```typescript
{
  albums: PaginatedResponse<Album>;
  user: User;
}
```

**Features**:
- Responsive grid (1-4 columns)
- Album cover images with fallback
- Photo count badges
- Search input (debounced)
- Pagination links
- "Create Album" button (owner only)
- CreateAlbumModal integration

#### **Gallery/Show.vue** (`resources/js/pages/Gallery/Show.vue`)
Photo grid view with infinite scroll.

**Props**:
```typescript
{
  album: Album;
  photos: PaginatedResponse<Photo>;
  user: User;
}
```

**Features**:
- Responsive photo grid (2-4 columns)
- Album header (title, description, stats)
- "Upload Photos" button (owner only)
- "Edit Album" / "Delete Album" buttons (owner only)
- Infinite scroll with IntersectionObserver
- Photo click → opens lightbox
- EditAlbumModal and UploadPhotosModal integration

#### **Gallery/PhotoShow.vue** (Not implemented - using lightbox instead)
Single photo view page (optional implementation).

### 2. Components

#### **CreateAlbumModal.vue** (`resources/js/components/app/CreateAlbumModal.vue`)
Modal for creating new albums.

**Props**:
```typescript
{
  isOpen: boolean;
  user: User;
}
```

**Emits**: `update:isOpen`, `created`

**Features**:
- Title input (required, max 255)
- Description textarea (optional, max 5000)
- Visibility selector (radio buttons)
- Cover image upload with preview (max 5MB)
- Form validation with error display
- useForm composable with Inertia

#### **EditAlbumModal.vue** (`resources/js/components/app/EditAlbumModal.vue`)
Modal for editing album details.

**Props**:
```typescript
{
  isOpen: boolean;
  album: Album;
  user: User;
}
```

**Emits**: `update:isOpen`, `updated`

**Features**:
- Pre-populated form fields
- Cover image replacement
- Visibility change
- Delete album button
- Confirmation dialog for deletion

#### **UploadPhotosModal.vue** (`resources/js/components/app/UploadPhotosModal.vue`)
Modal for batch photo uploads.

**Props**:
```typescript
{
  isOpen: boolean;
  album: Album;
  user: User;
}
```

**Emits**: `update:isOpen`, `uploaded`

**Features**:
- Drag & drop zone
- Multi-file selection (max 20 photos, 10MB each)
- Preview thumbnails with remove buttons
- Per-photo metadata input:
  - Title (optional)
  - Description (optional)
  - Tags (array of strings)
- File validation with error messages
- Progress indication during upload
- Tags input with comma separation

#### **PhotoLightbox.vue** (`resources/js/components/app/PhotoLightbox.vue`)
Full-screen photo viewer with advanced controls.

**Props**:
```typescript
{
  isOpen: boolean;
  photos: Photo[];
  initialIndex: number;
  album: Album;
  user: User;
}
```

**Emits**: `update:isOpen`

**Features**:
- **Navigation**:
  - Previous/Next buttons
  - Keyboard arrows (←/→)
  - Close button / ESC key
- **Zoom Controls**:
  - Zoom in/out buttons (+/-)
  - Reset zoom (0 key)
  - Zoom range: 0.5x - 3x
  - Mouse wheel zoom (Ctrl + wheel)
- **Image Display**:
  - Responsive images with srcset
  - Smooth transitions
  - Pan support when zoomed
- **Metadata Sidebar** (toggle with 'I' key):
  - Photo title & description
  - Tags (clickable)
  - EXIF data:
    - Camera make & model
    - Lens model
    - Focal length
    - Aperture (f-stop)
    - Shutter speed
    - ISO
    - Date taken
    - GPS coordinates (if available)
  - File info: size, dimensions, format
  - View & download counts
- **Download Button**:
  - Downloads original photo
  - Increments download counter
- **View Tracking**:
  - Auto-increments view count on photo open
  - Debounced to prevent multiple counts

## Image Processing

### Storage Structure
```
storage/app/public/
├── photos/
│   └── {user_id}/
│       └── {album_slug}/
│           ├── thumbnail_{filename}.jpg    # 300x300
│           ├── medium_{filename}.jpg       # 800x600
│           ├── large_{filename}.jpg        # 1920x1080
│           └── original_{filename}.jpg     # Original size
└── albums/
    └── covers/
        └── {user_id}/
            └── {filename}.jpg              # 1200x675
```

### Image Sizes
| Size | Dimensions | Use Case |
|------|------------|----------|
| **Thumbnail** | 300x300 | Grid thumbnails, album covers |
| **Medium** | 800x600 | Lightbox preview, mobile view |
| **Large** | 1920x1080 | Desktop lightbox, high-res preview |
| **Original** | Unchanged | Downloads, archival |

### EXIF Metadata Format
```json
{
  "exif": {
    "Make": "Canon",
    "Model": "EOS R5",
    "LensModel": "Canon RF 24-70mm f/2.8L",
    "FocalLength": "50mm",
    "FNumber": "f/2.8",
    "ExposureTime": "1/500",
    "ISO": 400,
    "DateTimeOriginal": "2025:10:24 14:30:00"
  },
  "gps": {
    "latitude": 40.7128,
    "longitude": -74.0060
  }
}
```

## API Endpoints

### Albums

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/profile/{username}/gallery` | List albums | Optional |
| POST | `/profile/{username}/gallery` | Create album | Required (owner) |
| GET | `/profile/{username}/gallery/{album_slug}` | View album | Based on visibility |
| PUT | `/profile/{username}/gallery/{album_slug}` | Update album | Required (owner) |
| DELETE | `/profile/{username}/gallery/{album_slug}` | Delete album | Required (owner) |

### Photos

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/profile/{username}/gallery/{album_slug}/photos` | Upload photos | Required (owner) |
| GET | `/profile/{username}/gallery/{album_slug}/{photo_slug}` | View photo | Based on album visibility |
| POST | `/profile/{username}/gallery/{album_slug}/{photo_slug}/view` | Increment views | Optional |
| PUT | `/profile/{username}/gallery/{album_slug}/{photo_slug}` | Update photo | Required (owner) |
| DELETE | `/profile/{username}/gallery/{album_slug}/{photo_slug}` | Delete photo | Required (owner) |
| GET | `/profile/{username}/gallery/{album_slug}/{photo_slug}/download` | Download photo | Based on album visibility |

## TypeScript Interfaces

### Core Interfaces (`resources/js/types/index.d.ts`)

```typescript
export type AlbumVisibility = 'public' | 'private' | 'followers_only' | 'link_only';

export interface Album {
  id: number;
  title: string;
  slug: string;
  description: string | null;
  visibility: AlbumVisibility;
  cover_url: string | null;
  photos_count: number;
  user: User;
  created_at: string;
}

export interface PhotoMetadata {
  exif?: {
    Make?: string;
    Model?: string;
    LensModel?: string;
    FocalLength?: string;
    FNumber?: string;
    ExposureTime?: string;
    ISO?: number;
    DateTimeOriginal?: string;
  };
  gps?: {
    latitude: number;
    longitude: number;
  };
}

export interface PhotoTag {
  id: number;
  name: string;
  slug: string;
}

export interface Photo {
  id: number;
  album_id: number;
  title: string | null;
  slug: string;
  description: string | null;
  thumbnail_url: string;
  medium_url: string;
  large_url: string;
  original_url: string;
  mime_type: string;
  size: number;
  width: number;
  height: number;
  views_count: number;
  downloads_count: number;
  metadata: PhotoMetadata | null;
  tags: PhotoTag[];
  user: User;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}
```

## Usage Examples

### Creating an Album (Frontend)
```typescript
import { useForm } from '@inertiajs/vue3';
import { create as createAlbum } from '@/routes/gallery/index';

const form = useForm({
  title: 'Summer Vacation 2025',
  description: 'Photos from our trip to Hawaii',
  visibility: 'public',
  cover: null as File | null,
});

const submit = () => {
  form.post(createAlbum.url(), {
    onSuccess: () => {
      // Album created
    },
  });
};
```

### Uploading Photos (Frontend)
```typescript
import { useForm } from '@inertiajs/vue3';
import { storePhotos } from '@/routes/gallery/album/photos';

const form = useForm({
  photos: [] as File[],
  titles: [] as string[],
  descriptions: [] as string[],
  tags: [] as string[][],
});

const handleFilesSelected = (files: FileList) => {
  form.photos = Array.from(files);
  form.titles = Array(files.length).fill('');
  form.descriptions = Array(files.length).fill('');
  form.tags = Array(files.length).fill([]);
};

const submit = () => {
  form.post(storePhotos.url({ user: username, album: albumSlug }), {
    onSuccess: () => {
      // Photos uploaded
    },
  });
};
```

### Processing Photos (Backend)
```php
use App\Services\ImageOptimizationService;

$imageService = new ImageOptimizationService();

foreach ($request->file('photos') as $index => $file) {
    $path = "photos/{$user->id}/{$album->slug}";
    
    $result = $imageService->processPhoto($file, $path);
    
    $photo = Photo::create([
        'album_id' => $album->id,
        'user_id' => $user->id,
        'title' => $request->titles[$index] ?? null,
        'description' => $request->descriptions[$index] ?? null,
        'thumbnail_path' => $result['thumbnail_path'],
        'medium_path' => $result['medium_path'],
        'large_path' => $result['large_path'],
        'original_file_path' => $result['original_path'],
        'mime_type' => $result['mime_type'],
        'size' => $result['size'],
        'width' => $result['width'],
        'height' => $result['height'],
        'metadata' => $result['metadata'],
    ]);
}
```

## Configuration

### Environment Variables
```env
# Storage Configuration
FILESYSTEM_DISK=public

# Image Processing
MAX_ALBUM_COVER_SIZE=5120  # 5MB in KB
MAX_PHOTO_SIZE=10240       # 10MB in KB
MAX_PHOTOS_PER_UPLOAD=20

# Pagination
GALLERY_ALBUMS_PER_PAGE=12
GALLERY_PHOTOS_PER_PAGE=24
```

### File System Configuration (`config/filesystems.php`)
```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

## Testing

### Running Tests
```bash
# Run all gallery tests
php artisan test --filter=Album
php artisan test --filter=Photo

# Run specific test file
php artisan test tests/Feature/AlbumControllerTest.php
php artisan test tests/Unit/PhotoModelTest.php

# Run with coverage
php artisan test --coverage --min=80
```

### Test Coverage
- **Feature Tests**: 33 tests (AlbumController, PhotoController)
- **Unit Tests**: 34 tests (Models, Services)
- **Total**: 67 comprehensive tests
- **Coverage**: >80%

See [PHOTO_GALLERY_TESTING.md](./PHOTO_GALLERY_TESTING.md) for detailed testing documentation.

## Troubleshooting

### Common Issues

#### **Issue**: Photos not displaying
**Symptoms**: Broken image links, 404 errors  
**Solutions**:
1. Verify storage symlink exists:
   ```bash
   php artisan storage:link
   ```
2. Check file permissions:
   ```bash
   chmod -R 775 storage/app/public
   ```
3. Verify `APP_URL` in `.env` is correct

#### **Issue**: Upload fails with "File too large"
**Symptoms**: Validation error on upload  
**Solutions**:
1. Check `php.ini` settings:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 50M
   max_file_uploads = 20
   ```
2. Restart web server after changing php.ini
3. Check validation rules in StorePhotoRequest

#### **Issue**: EXIF data not extracting
**Symptoms**: Empty metadata field  
**Solutions**:
1. Ensure PHP EXIF extension is enabled:
   ```bash
   php -m | grep exif
   ```
2. Enable in `php.ini`:
   ```ini
   extension=exif
   ```
3. Test images may not have EXIF data (use real camera photos)

#### **Issue**: Image processing slow
**Symptoms**: Upload timeouts, slow responses  
**Solutions**:
1. Increase PHP timeout in `php.ini`:
   ```ini
   max_execution_time = 300
   ```
2. Process images in background job (see Performance Optimization)
3. Reduce max upload count or file sizes

#### **Issue**: Soft-deleted photos still visible
**Symptoms**: Deleted photos appear in albums  
**Solutions**:
1. Ensure queries don't include trashed:
   ```php
   Photo::where('album_id', $album->id)->get();  // Excludes soft deleted
   ```
2. Check AlbumController uses proper eager loading
3. Verify Photo model has `SoftDeletes` trait

#### **Issue**: Tags not attaching to photos
**Symptoms**: Photos upload but no tags  
**Solutions**:
1. Verify tags array format in request:
   ```json
   {"tags": [["sunset", "nature"], ["landscape"]]}
   ```
2. Check PhotoController `attachTags()` method
3. Ensure photo_photo_tag pivot table exists

#### **Issue**: Infinite scroll not loading more photos
**Symptoms**: "Load More" doesn't fetch photos  
**Solutions**:
1. Check network tab for API errors
2. Verify `photos.links.next` is not null
3. Check IntersectionObserver implementation in Show.vue
4. Ensure pagination is enabled in AlbumController

### Debug Mode

Enable debug mode to see detailed error messages:

**.env**:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

**Check logs**:
```bash
tail -f storage/logs/laravel.log
```

### Performance Optimization

For production environments with high traffic:

1. **Queue Image Processing**:
   ```php
   // In PhotoController
   ProcessPhotoJob::dispatch($photo, $file);
   ```

2. **Cache Album Queries**:
   ```php
   Cache::remember("user.{$userId}.albums", 3600, function() {
       return Album::where('user_id', $userId)->get();
   });
   ```

3. **Eager Load Relationships**:
   ```php
   Album::with('photos.tags', 'user')->paginate(12);
   ```

4. **Use CDN for Images** (see Task 20 for CDN setup)

5. **Add Database Indexes** (already included in migrations)

## Future Enhancements

Planned features (not yet implemented):

- [ ] **SEO Enhancements** (Task 14): Schema.org markup, image sitemaps, Open Graph tags
- [ ] **Access Control Policies** (Task 15): AlbumPolicy, PhotoPolicy, sharing tokens
- [ ] **Profile Integration** (Task 16): Gallery tab on user profiles
- [ ] **Performance Optimization** (Task 20): CDN integration, caching, lazy loading
- [ ] **Advanced Features**:
  - Photo comments and reactions
  - Album sharing with external users
  - Bulk photo editing (tags, metadata)
  - Photo favoriting/starring
  - GPS map view for photos
  - Slideshow mode
  - Photo comparison view

## Related Documentation

- [Database Seeders Guide](./DATABASE_SEEDERS_GALLERY.md)
- [Testing Guide](./PHOTO_GALLERY_TESTING.md)
- [Post Attachments Feature](./POST_ATTACHMENTS_FEATURE.md)
- [Groups Feature Guide](./GROUPS_FEATURE_GUIDE.md)

## Support

For issues or questions:
1. Check this documentation
2. Review test files for usage examples
3. Check Laravel logs: `storage/logs/laravel.log`
4. Review browser console for frontend errors
5. Consult Intervention Image v3 documentation

---

**Version**: 1.0.0  
**Last Updated**: October 24, 2025  
**Status**: ✅ Core features complete, policies and SEO pending
