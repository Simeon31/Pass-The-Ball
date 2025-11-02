<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PostResource extends JsonResource
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

        // Get latest comments (limited to 5)
        $latestComments = CommentResource::collection(
            $this->comments()->with('user')->take(5)->get()
        );

        // Get total comments count
        $totalComments = $this->comments()->count();

        return [
            "id" => $this->id,
            "title" => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
            'user' => new UserResource($this->user),
            'group' => $this->group,
            'attachments' => PostAttachmentResource::collection($this->attachments),
            'reactions' => [
                'summary' => $reactionsSummary,
                'total' => $totalReactions,
                'current_user_reaction' => $currentUserReaction,
            ],
            'comments' => [
                'data' => $latestComments,
                'total' => $totalComments,
            ],
            'can_delete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
        ];
    }
}
