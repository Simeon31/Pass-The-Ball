<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixNotificationUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:fix-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix notification action URLs from full URLs to relative paths for Inertia.js';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing notification action URLs...');

        $notifications = DB::table('notifications')->get();
        $fixed = 0;

        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);

            if (isset($data['action_url']) && str_starts_with($data['action_url'], 'http')) {
                // Convert full URL to relative path
                $data['action_url'] = parse_url($data['action_url'], PHP_URL_PATH);

                DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update(['data' => json_encode($data)]);

                $fixed++;
            }
        }

        $this->info("Fixed {$fixed} notifications.");
        $this->info('Done!');

        return Command::SUCCESS;
    }
}
