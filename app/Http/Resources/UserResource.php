<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class UserResource extends JsonResource
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
      'name' => $this->name,
      'email' => $this->email,
      'email_verified_at' => $this->email_verified_at,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'username' => $this->username,
      'cover_url' => $this->cover_path,
      'profile_picture_url' => $this->profile_picture_path,
      'followers_count' => $this->followers_count ?? $this->followers()->count(),
      'following_count' => $this->following_count ?? $this->following()->count(),
      'is_followed_by_auth' => $this->when(
        $request->user() !== null,
        fn() => $request->user()->isFollowing($this->resource)
      ),
    ];
  }
}
