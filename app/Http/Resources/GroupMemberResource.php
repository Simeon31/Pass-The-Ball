<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The resource is a User model with pivot data
        return [
            'id' => $this->id,
            'user' => new UserResource($this),
            'role' => $this->pivot->role ?? null,
            'status' => $this->pivot->status ?? null,
            'joined_at' => $this->pivot->created_at ?? null,
        ];
    }
}
