<?php

namespace App\Http\Resources;

use App\Services\GroupPermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isMember = $user && $this->isMember($user);
        $isOwner = $user && $this->isOwner($user);

        $permissionService = app(GroupPermissionService::class);
        $permissions = $user ? $permissionService->getUserPermissions($user, $this->resource) : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'about' => $this->about,
            'cover_url' => $this->cover_path,
            'thumbnail_url' => $this->thumbnail_path,
            'auto_approval' => $this->auto_approval,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'member_count' => $this->when($isMember || !$user, $this->members()->count()),
            'is_member' => $isMember,
            'is_owner' => $isOwner,
            'user_role' => $this->when($isMember, function () use ($user) {
                return $this->getUserRole($user);
            }),
            'permissions' => $this->when($isMember, array_map(fn($p) => $p->value, $permissions)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
