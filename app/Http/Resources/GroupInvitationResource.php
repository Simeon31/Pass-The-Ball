<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupInvitationResource extends JsonResource
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
            'group' => new GroupResource($this->whenLoaded('group')),
            'user' => new UserResource($this->whenLoaded('user')),
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'token' => $this->when($request->user()?->id === $this->user_id, $this->token),
            'status' => $this->status,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->token_expires_at->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
