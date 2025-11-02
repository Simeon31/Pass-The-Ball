<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Album>
 */
class AlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(rand(2, 4), true);

        return [
            'user_id' => User::factory(),
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->optional(0.7)->paragraph(),
            'visibility' => fake()->randomElement(['public', 'private', 'followers_only', 'link_only']),
            'cover_path' => null, // Will be set when photos are created
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the album should be public.
     */
    public function public(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    /**
     * Indicate that the album should be private.
     */
    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Indicate that the album should be followers only.
     */
    public function followersOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => 'followers_only',
        ]);
    }

    /**
     * Indicate that the album should be link only.
     */
    public function linkOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => 'link_only',
        ]);
    }
}
