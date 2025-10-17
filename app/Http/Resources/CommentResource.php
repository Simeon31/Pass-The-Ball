<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get reactions summary (count per type)
        $reactionsSummary = $this->reactions()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Get current user's reaction if authenticated
        $currentUserReaction = null;
        if ($request->user()) {
            $userReaction = $this->reactions()
                ->where('user_id', $request->user()->id)
                ->first();
            $currentUserReaction = $userReaction ? $userReaction->type : null;
        }

        // Get total reactions count
        $totalReactions = $this->reactions()->count();

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'comment' => $this->comment,
            'user' => new UserResource($this->user),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'reactions' => [
                'summary' => $reactionsSummary,
                'total' => $totalReactions,
                'current_user_reaction' => $currentUserReaction,
            ],
        ];
    }
}
