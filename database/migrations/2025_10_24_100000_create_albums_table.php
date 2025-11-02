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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private', 'followers_only', 'link_only'])->default('public');
            $table->string('cover_path', 1024)->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('visibility');
            $table->index(['user_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
