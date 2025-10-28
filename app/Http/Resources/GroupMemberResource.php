<?php

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupMemberResource extends JsonResource
{
    protected ?Group $group = null;

    /**
     * Set the group context for permission checks.
     */
    public function withGroup(Group $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canRemove = false;

        if ($user && $this->group) {
            // User can remove this member if they have removeMembers permission,
            // the target is not the owner, and the target is not themselves
            $canRemove = $user->can('removeMembers', $this->group)
                && !$this->group->isOwner($this->resource)
                && $user->id !== $this->resource->id;
        }

        // The resource is a User model with pivot data
        return [
            'id' => $this->id,
            'user' => new UserResource($this->resource),
            'role' => $this->pivot->role ?? null,
            'status' => $this->pivot->status ?? null,
            'joined_at' => $this->pivot->created_at ?? null,
            'can_remove' => $canRemove,
        ];
    }
}

