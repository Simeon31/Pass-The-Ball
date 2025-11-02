<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->getCategory(),
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Get the notification category based on its type.
     */
    private function getCategory(): string
    {
        $typeMap = [
            'App\\Notifications\\GroupInvitationNotification' => 'invitation',
            'App\\Notifications\\GroupJoinRequestNotification' => 'join_request',
            'App\\Notifications\\GroupJoinApprovedNotification' => 'join_approved',
            'App\\Notifications\\GroupJoinRejectedNotification' => 'join_rejected',
            'App\\Notifications\\PostCommentNotification' => 'comment',
            'App\\Notifications\\PostReactionNotification' => 'reaction',
            'App\\Notifications\\UserFollowedNotification' => 'follow',
        ];

        return $typeMap[$this->type] ?? 'general';
    }
}
