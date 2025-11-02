<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugGroupPermissions extends Command
{
    protected $signature = 'debug:group-permissions';
    protected $description = 'Debug group permissions setup';

    public function handle()
    {
        $this->info('=== Groups Table ===');
        $groups = Group::select('id', 'name', 'user_id', 'slug')->get();
        foreach ($groups as $group) {
            $this->line("ID: {$group->id}, Name: {$group->name}, Owner: {$group->user_id}");
        }

        $this->info('');
        $this->info('=== Group Users Table ===');
        $groupUsers = DB::table('group_users')
            ->select('group_id', 'user_id', 'status', 'role')
            ->get();
        foreach ($groupUsers as $gu) {
            $this->line("Group: {$gu->group_id}, User: {$gu->user_id}, Status: {$gu->status}, Role: {$gu->role}");
        }

        $this->info('');
        $this->info('=== Authorization Test ===');

        // Get first group and its owner
        $group = Group::first();
        if ($group) {
            $owner = User::find($group->user_id);
            if ($owner) {
                $this->line("Testing Group: {$group->name} (ID: {$group->id})");
                $this->line("Owner: {$owner->name} (ID: {$owner->id})");
                $this->line("isOwner check: " . ($group->isOwner($owner) ? 'true' : 'false'));
                $this->line("getUserRole: " . ($group->getUserRole($owner) ?? 'null'));
                $this->line("Can approve: " . ($owner->can('approveRequests', $group) ? 'true' : 'false'));
            }
        }

        return 0;
    }
}
