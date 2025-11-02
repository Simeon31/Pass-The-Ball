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
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('reactable'); // Creates reactable_id and reactable_type
            $table->string('type'); // e.g., 'like', 'love', 'haha', etc.
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
