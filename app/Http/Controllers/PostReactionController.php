<?php

namespace App\Http\Controllers;

use App\Events\PostReacted;
use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostReactionController extends Controller
{
    /**
     * Toggle a reaction on a post (add if doesn't exist, update if different type, remove if same type)
     */
    public function toggle(Request $request, Post $post)
    {
        $request->validate([
            'type' => 'required|in:' . implode(',', PostReaction::TYPES),
        ]);

        $userId = $request->user()->id;
        $type = $request->input('type');

        // Find existing reaction
        $existingReaction = PostReaction::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                // Same reaction type - remove it (toggle off)
                $existingReaction->delete();
                $action = 'removed';
            } else {
                // Different reaction type - update it
                $existingReaction->update(['type' => $type]);
                $action = 'updated';
            }
        } else {
            // No existing reaction - create new one
            PostReaction::create([
                'post_id' => $post->id,
                'user_id' => $userId,
                'type' => $type,
            ]);
            $action = 'added';
        }

        // Get updated reactions summary
        $reactionsSummary = PostReaction::where('post_id', $post->id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $totalReactions = PostReaction::where('post_id', $post->id)->count();

        $currentUserReaction = PostReaction::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        $response = [
            'action' => $action,
            'reactions' => [
                'summary' => $reactionsSummary,
                'total' => $totalReactions,
                'current_user_reaction' => $currentUserReaction ? $currentUserReaction->type : null,
            ],
        ];

        // Broadcast the reaction event for real-time updates
        broadcast(new PostReacted($post->id, $response['reactions']))->toOthers();

        return response()->json($response);
    }
}
