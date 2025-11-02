<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photo_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index('slug');
            $table->index('user_id');

            // Unique constraint: tag name unique per user
            $table->unique(['user_id', 'slug']);
        });

        // Pivot table for many-to-many relationship between photos and tags
        Schema::create('photo_photo_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained('photos')->onDelete('cascade');
            $table->foreignId('photo_tag_id')->constrained('photo_tags')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate tags on same photo
            $table->unique(['photo_id', 'photo_tag_id']);

            // Indexes for performance
            $table->index('photo_id');
            $table->index('photo_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_photo_tag');
        Schema::dropIfExists('photo_tags');
    }
};
