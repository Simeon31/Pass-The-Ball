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

        // Build base response
        $response = [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'comment' => $this->comment,
            'user' => new UserResource($this->user),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'reactions' => [
                'summary' => $reactionsSummary,
                'total' => $totalReactions,
                'current_user_reaction' => $currentUserReaction,
            ],
            'depth' => $this->depth ?? 0,
        ];

        // Add replies if they are loaded (for tree structure)
        if ($this->relationLoaded('replies')) {
            $response['replies'] = CommentResource::collection($this->replies);
            $response['replies_count'] = $this->replies->count();
            $response['has_more_replies'] = $this->has_more_replies ?? false;
        }

        return $response;
    }
}
