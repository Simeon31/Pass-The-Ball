<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AlbumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users or create test users
        $users = User::all();

        if ($users->isEmpty()) {
            // Create test users if none exist
            $users = User::factory(3)->create();
        }

        // Take first 3 users or all if less than 3
        $users = $users->take(3);

        foreach ($users as $user) {
            // Create 3-6 albums per user with different visibility settings
            $albumCount = rand(3, 6);

            for ($i = 0; $i < $albumCount; $i++) {
                $album = Album::factory()->create([
                    'user_id' => $user->id,
                    'visibility' => $this->getVisibilityByIndex($i),
                ]);

                // Create 5-20 photos per album
                $photoCount = rand(5, 20);

                $photos = Photo::factory($photoCount)->create([
                    'album_id' => $album->id,
                    'user_id' => $user->id,
                ]);

                // Set random photo as cover
                if ($photos->isNotEmpty()) {
                    $coverPhoto = $photos->random();
                    $album->update([
                        'cover_path' => $coverPhoto->thumbnail_path,
                    ]);
                }

                // Add tags to some photos
                $photos->each(function ($photo) {
                    if (rand(0, 100) > 40) { // 60% chance of having tags
                        $this->addRandomTags($photo, rand(1, 5));
                    }
                });
            }
        }

        $this->command->info('Albums and photos seeded successfully!');
    }

    /**
     * Get visibility setting based on index to ensure variety.
     *
     * @param int $index
     * @return string
     */
    protected function getVisibilityByIndex(int $index): string
    {
        $visibilities = ['public', 'private', 'followers_only', 'link_only'];
        return $visibilities[$index % count($visibilities)];
    }

    /**
     * Add random tags to a photo.
     *
     * @param Photo $photo
     * @param int $count
     * @return void
     */
    protected function addRandomTags(Photo $photo, int $count): void
    {
        $tagNames = [
            'landscape',
            'portrait',
            'nature',
            'wildlife',
            'urban',
            'street',
            'architecture',
            'travel',
            'sunset',
            'sunrise',
            'beach',
            'mountain',
            'forest',
            'ocean',
            'city',
            'night',
            'black-white',
            'macro',
            'abstract',
            'minimalist',
            'vintage',
            'colorful',
            'moody',
        ];

        $selectedTags = array_rand(array_flip($tagNames), min($count, count($tagNames)));
        $selectedTags = is_array($selectedTags) ? $selectedTags : [$selectedTags];

        foreach ($selectedTags as $tagName) {
            // Find or create tag
            $tag = PhotoTag::firstOrCreate(
                ['name' => $tagName, 'user_id' => $photo->user_id],
                ['slug' => Str::slug($tagName)]
            );

            // Attach tag to photo if not already attached
            if (!$photo->tags()->where('photo_tag_id', $tag->id)->exists()) {
                $photo->tags()->attach($tag->id);
            }
        }
    }
}
