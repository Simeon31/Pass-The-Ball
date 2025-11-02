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
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('albums')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255)->nullable();
            $table->string('slug', 255);
            $table->text('description')->nullable();

            // File information
            $table->string('file_path', 1024); // Main optimized image path
            $table->string('original_file_path', 1024)->nullable(); // Original uncompressed file
            $table->string('thumbnail_path', 1024)->nullable(); // Thumbnail path
            $table->string('medium_path', 1024)->nullable(); // Medium size path
            $table->string('mime_type', 50);
            $table->bigInteger('size')->nullable(); // File size in bytes

            // Image dimensions
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Engagement metrics
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('downloads_count')->default(0);

            // Metadata (EXIF data, camera info, etc.)
            $table->json('metadata')->nullable();

            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('album_id');
            $table->index('user_id');
            $table->index('slug');
            $table->index(['album_id', 'created_at']);
            $table->index('views_count');

            // Unique constraint: slug unique within album
            $table->unique(['album_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
