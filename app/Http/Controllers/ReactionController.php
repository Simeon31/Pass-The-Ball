<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReactionController extends Controller
{
    /**
     * Toggle a reaction on a reactable model (Post or Comment)
     */
    public function toggle(Request $request, string $type, int $id)
    {
        $request->validate([
            'type' => 'required|in:' . implode(',', Reaction::TYPES),
        ]);

        $userId = $request->user()->id;
        $reactionType = $request->input('type');

        // Determine the model class from the type
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json(['error' => 'Invalid reactable type'], 400);
        }

        // Find the reactable model
        $reactable = $modelClass::find($id);

        if (!$reactable) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        // Find existing reaction
        $existingReaction = Reaction::where('reactable_type', $modelClass)
            ->where('reactable_id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $reactionType) {
                // Same reaction type - remove it (toggle off)
                $existingReaction->delete();
                $action = 'removed';
            } else {
                // Different reaction type - update it
                $existingReaction->update(['type' => $reactionType]);
                $action = 'updated';
            }
        } else {
            // No existing reaction - create new one
            Reaction::create([
                'reactable_type' => $modelClass,
                'reactable_id' => $id,
                'user_id' => $userId,
                'type' => $reactionType,
            ]);
            $action = 'added';
        }

        // Get updated reactions summary
        $reactionsSummary = Reaction::where('reactable_type', $modelClass)
            ->where('reactable_id', $id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $totalReactions = Reaction::where('reactable_type', $modelClass)
            ->where('reactable_id', $id)
            ->count();

        $currentUserReaction = Reaction::where('reactable_type', $modelClass)
            ->where('reactable_id', $id)
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
        $this->broadcastReaction($type, $id, $response['reactions']);

        return response()->json($response);
    }

    /**
     * Get the model class from the reactable type
     */
    private function getModelClass(string $type): ?string
    {
        $models = [
            'post' => \App\Models\Post::class,
            'comment' => \App\Models\Comment::class,
        ];

        return $models[$type] ?? null;
    }

    /**
     * Broadcast reaction event based on type
     */
    private function broadcastReaction(string $type, int $id, array $reactions): void
    {
        if ($type === 'post') {
            broadcast(new \App\Events\PostReacted($id, $reactions))->toOthers();
        } elseif ($type === 'comment') {
            broadcast(new \App\Events\CommentReacted($id, $reactions))->toOthers();
        }
    }
}
