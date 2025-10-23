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
        Schema::table('post_reactions', function (Blueprint $table) {
            // Drop old foreign key and post_id column
            $table->dropForeign('post_reactions_post_id_foreign');
            $table->dropColumn('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_reactions', function (Blueprint $table) {
            // Add back post_id column
            $table->foreignId('post_id')->nullable()->after('id')->constrained('posts');
        });
    }
};
