# Database Seeders - Photo Gallery

## Overview
This document explains how to use the database seeders for the Photo Gallery feature to generate realistic test data.

## Files Created
- `database/factories/AlbumFactory.php` - Factory for generating albums with realistic data
- `database/factories/PhotoFactory.php` - Factory for generating photos with EXIF metadata
- `database/seeders/AlbumSeeder.php` - Seeds albums and photos for existing users
- `database/seeders/PhotoSeeder.php` - Seeds additional photos with themed tags

## Running Seeders

### Seed Everything (including photo gallery)
```bash
php artisan db:seed
```

### Seed Only Photo Gallery
```bash
php artisan db:seed --class=AlbumSeeder
php artisan db:seed --class=PhotoSeeder
```

### Fresh Migration + Seed
```bash
php artisan migrate:fresh --seed
```

## What Gets Seeded

### AlbumSeeder
**Per User:**
- Creates 3-6 albums per user
- Each album has different visibility (public, private, followers_only, link_only)
- Each album contains 5-20 photos
- Random photo selected as album cover
- 60% of photos get 1-5 random tags

**Album Data:**
- Title: 2-4 random words (e.g., "Beautiful Summer Moments")
- Slug: Auto-generated from title
- Description: Random paragraph (70% chance)
- Visibility: Cycles through all types
- Cover: Thumbnail of random photo from album

**Photo Data:**
- Title: 2-5 random words (60% chance)
- Slug: Auto-generated or random 12-char string
- Description: Random paragraph (50% chance)
- File paths: Simulated (photos/sample/*.jpg)
- Size: 500KB - 5MB
- Dimensions: Various HD/4K resolutions
- Views: 0-500
- Downloads: 0-50
- EXIF metadata: Realistic camera data

### PhotoSeeder
**Per Album:**
- Adds 5-15 additional photos if album has < 10 photos
- 30% of albums get "popular" photos (high views/downloads)
- Photos get themed tags based on random category
- Updates album cover if not set

**Tag Categories:**
- `landscape`: nature, sunset, mountain, forest, beach, ocean
- `urban`: city, architecture, street, night, urban
- `people`: portrait, family, wedding, event, candid
- `wildlife`: animals, birds, wildlife, nature, macro
- `abstract`: abstract, minimalist, colorful, pattern, texture

**Popular Photos:**
- Views: 500-5000
- Downloads: 50-500

## Factory Features

### AlbumFactory
**State Modifiers:**
```php
Album::factory()->public()->create();           // Public album
Album::factory()->private()->create();          // Private album
Album::factory()->followersOnly()->create();    // Followers only
Album::factory()->linkOnly()->create();         // Link only
```

### PhotoFactory
**State Modifiers:**
```php
Photo::factory()->popular()->create();  // High views/downloads
Photo::factory()->recent()->create();   // Created in last week
```

**Generated EXIF Metadata:**
- Camera makes: Canon, Nikon, Sony, Fujifilm, Apple
- Realistic lens models
- Focal lengths: 24mm - 200mm
- Apertures: f/1.4 - f/8.0
- Shutter speeds: 1/1000 - 1/30
- ISO: 100 - 3200
- GPS coordinates: 30% chance

## Tag Management

**Available Tags (23 total):**
- Photography styles: landscape, portrait, abstract, minimalist
- Subjects: nature, wildlife, urban, architecture, street
- Settings: sunset, sunrise, beach, mountain, forest, ocean, city, night
- Techniques: black-white, macro, vintage, colorful, moody

**Tag Assignment:**
- AlbumSeeder: 60% of photos get random tags
- PhotoSeeder: All photos get 2-4 themed tags based on album category
- Tags are automatically created if they don't exist
- Duplicate tags are prevented with `firstOrCreate()`

## Example Usage

### Create Test Data for Development
```bash
# Start fresh with test data
php artisan migrate:fresh --seed

# Or add gallery data to existing database
php artisan db:seed --class=AlbumSeeder
```

### Create Custom Test Data
```php
use App\Models\Album;
use App\Models\Photo;
use App\Models\User;

// Create user with albums
$user = User::factory()->create();

// Create public album with 10 photos
$album = Album::factory()->public()->create(['user_id' => $user->id]);
Photo::factory(10)->create(['album_id' => $album->id, 'user_id' => $user->id]);

// Create popular photos
Photo::factory()->popular()->create([
    'album_id' => $album->id,
    'user_id' => $user->id,
]);
```

## Notes

### File Paths
- Seeders create **placeholder paths** only (no actual image files)
- Paths follow pattern: `photos/sample/{type}/{uuid}.jpg`
- Actual photo uploads use: `photos/{user_id}/{album_slug}/{filename}`

### Performance
- AlbumSeeder processes 3 users by default
- Each user gets 3-6 albums × 5-20 photos = ~45-360 photos per user
- Total: ~135-1080 photos for 3 users
- PhotoSeeder adds ~5-15 photos per album

### Dependencies
- Requires existing users (or creates 3 test users)
- PhotoSeeder requires AlbumSeeder to run first
- Tags are created automatically during seeding

## Troubleshooting

### "No albums found" error in PhotoSeeder
**Solution:** Run AlbumSeeder first
```bash
php artisan db:seed --class=AlbumSeeder
php artisan db:seed --class=PhotoSeeder
```

### Duplicate tags
**Solution:** Already handled via `firstOrCreate()` - tags are reused

### Need more/less data
**Edit seeders:**
- Change `rand(3, 6)` in AlbumSeeder for album count
- Change `rand(5, 20)` in AlbumSeeder for photo count
- Change `$users->take(3)` in AlbumSeeder for user count

## Testing

After seeding, verify data:
```bash
# Check counts
php artisan tinker
>>> \App\Models\Album::count();
>>> \App\Models\Photo::count();
>>> \App\Models\PhotoTag::count();

# Check relationships
>>> $album = \App\Models\Album::first();
>>> $album->photos->count();
>>> $album->user->name;

# Check tags
>>> \App\Models\PhotoTag::pluck('name');
```

## Integration with Feature Tests

Use factories in tests:
```php
// tests/Feature/AlbumTest.php
test('user can view their albums', function () {
    $user = User::factory()->create();
    $albums = Album::factory(3)->create(['user_id' => $user->id]);
    
    $response = $this->actingAs($user)->get(route('gallery.index', $user));
    
    $response->assertOk();
    $response->assertInertia(fn ($page) => 
        $page->has('albums.data', 3)
    );
});
```
