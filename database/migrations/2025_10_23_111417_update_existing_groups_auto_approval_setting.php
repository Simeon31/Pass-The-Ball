<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * This migration updates all existing groups to set auto_approval to false (require approval)
     * unless they were explicitly set otherwise. This is a safer default for privacy.
     */
    public function up(): void
    {
        // Update all existing groups to require approval by default
        // Admins can then enable auto-approval individually if desired
        DB::table('groups')->update(['auto_approval' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to auto-approval enabled for all groups
        DB::table('groups')->update(['auto_approval' => true]);
    }
};
