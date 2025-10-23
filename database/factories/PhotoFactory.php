<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->optional(0.6)->words(rand(2, 5), true);
        $width = fake()->randomElement([1920, 2560, 3840, 4096]);
        $height = fake()->randomElement([1080, 1440, 2160, 2304]);

        return [
            'album_id' => Album::factory(),
            'user_id' => User::factory(),
            'title' => $title ? ucwords($title) : null,
            'slug' => $title ? Str::slug($title) : Str::random(12),
            'description' => fake()->optional(0.5)->paragraph(),
            'file_path' => 'photos/sample/' . fake()->uuid() . '.jpg',
            'original_file_path' => 'photos/sample/original/' . fake()->uuid() . '.jpg',
            'thumbnail_path' => 'photos/sample/thumbnail/' . fake()->uuid() . '.jpg',
            'medium_path' => 'photos/sample/medium/' . fake()->uuid() . '.jpg',
            'large_path' => 'photos/sample/large/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(500000, 5000000), // 500KB - 5MB
            'width' => $width,
            'height' => $height,
            'views_count' => fake()->numberBetween(0, 500),
            'downloads_count' => fake()->numberBetween(0, 50),
            'metadata' => $this->generateMetadata(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Generate realistic EXIF metadata.
     *
     * @return array<string, mixed>
     */
    protected function generateMetadata(): array
    {
        $cameras = [
            ['make' => 'Canon', 'model' => 'EOS R5'],
            ['make' => 'Nikon', 'model' => 'Z9'],
            ['make' => 'Sony', 'model' => 'A7R V'],
            ['make' => 'Fujifilm', 'model' => 'X-T5'],
            ['make' => 'Apple', 'model' => 'iPhone 15 Pro'],
        ];

        $lenses = [
            'Canon RF 24-70mm f/2.8L',
            'Nikkor Z 24-70mm f/2.8 S',
            'Sony FE 24-70mm f/2.8 GM II',
            'Fujifilm XF 16-55mm f/2.8',
            null, // For smartphones
        ];

        $camera = fake()->randomElement($cameras);
        $focalLength = fake()->randomElement([24, 35, 50, 70, 85, 100, 135, 200]);
        $aperture = fake()->randomElement([1.4, 1.8, 2.0, 2.8, 4.0, 5.6, 8.0]);
        $shutterSpeed = fake()->randomElement(['1/1000', '1/500', '1/250', '1/125', '1/60', '1/30']);
        $iso = fake()->randomElement([100, 200, 400, 800, 1600, 3200]);

        return [
            'exif' => [
                'Make' => $camera['make'],
                'Model' => $camera['model'],
                'LensModel' => $camera['make'] === 'Apple' ? null : fake()->randomElement($lenses),
                'FocalLength' => $focalLength . 'mm',
                'FNumber' => 'f/' . $aperture,
                'ExposureTime' => $shutterSpeed,
                'ISO' => $iso,
                'DateTimeOriginal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y:m:d H:i:s'),
            ],
            'gps' => fake()->optional(0.3)->passthrough([
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ]),
        ];
    }

    /**
     * Indicate that the photo should have high engagement.
     */
    public function popular(): static
    {
        return $this->state(fn(array $attributes) => [
            'views_count' => fake()->numberBetween(1000, 10000),
            'downloads_count' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Indicate that the photo should be recent.
     */
    public function recent(): static
    {
        return $this->state(fn(array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
