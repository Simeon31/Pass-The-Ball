<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing albums
        $albums = Album::all();

        if ($albums->isEmpty()) {
            $this->command->warn('No albums found. Please run AlbumSeeder first or create albums manually.');
            return;
        }

        $tagCategories = [
            'landscape' => ['nature', 'sunset', 'mountain', 'forest', 'beach', 'ocean'],
            'urban' => ['city', 'architecture', 'street', 'night', 'urban'],
            'people' => ['portrait', 'family', 'wedding', 'event', 'candid'],
            'wildlife' => ['animals', 'birds', 'wildlife', 'nature', 'macro'],
            'abstract' => ['abstract', 'minimalist', 'colorful', 'pattern', 'texture'],
        ];

        foreach ($albums as $album) {
            // Determine if this album should have popular photos
            $shouldHavePopular = rand(0, 100) > 70; // 30% chance

            // Get random category for album theme
            $category = array_rand($tagCategories);
            $availableTags = $tagCategories[$category];

            // Create additional photos for each album (if not already seeded by AlbumSeeder)
            $currentPhotoCount = $album->photos()->count();

            if ($currentPhotoCount < 10) {
                $additionalPhotos = rand(5, 15);

                for ($i = 0; $i < $additionalPhotos; $i++) {
                    $photo = Photo::factory()->create([
                        'album_id' => $album->id,
                        'user_id' => $album->user_id,
                    ]);

                    // Make some photos popular
                    if ($shouldHavePopular && rand(0, 100) > 60) {
                        $photo->update([
                            'views_count' => rand(500, 5000),
                            'downloads_count' => rand(50, 500),
                        ]);
                    }

                    // Add themed tags
                    $this->addThemedTags($photo, $availableTags, rand(2, 4));
                }

                // Update album cover if not set
                if (!$album->cover_path) {
                    $coverPhoto = $album->photos()->inRandomOrder()->first();
                    if ($coverPhoto) {
                        $album->update([
                            'cover_path' => $coverPhoto->thumbnail_path,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Additional photos seeded successfully!');
    }

    /**
     * Add themed tags to a photo.
     *
     * @param Photo $photo
     * @param array $availableTags
     * @param int $count
     * @return void
     */
    protected function addThemedTags(Photo $photo, array $availableTags, int $count): void
    {
        $selectedTags = array_rand(array_flip($availableTags), min($count, count($availableTags)));
        $selectedTags = is_array($selectedTags) ? $selectedTags : [$selectedTags];

        foreach ($selectedTags as $tagName) {
            // Find or create tag
            $tag = PhotoTag::firstOrCreate(
                ['name' => $tagName],
                ['slug' => Str::slug($tagName)]
            );

            // Attach tag to photo if not already attached
            if (!$photo->tags()->where('photo_tag_id', $tag->id)->exists()) {
                $photo->tags()->attach($tag->id);
            }
        }
    }
}
