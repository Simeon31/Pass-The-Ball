<?php

namespace App\Http\Controllers;

use App\Events\UserFollowed;
use App\Http\Resources\UserResource;
use App\Models\Follower;
use App\Models\User;
use App\Notifications\UserFollowedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowerController extends Controller
{
    /**
     * Toggle follow/unfollow a user.
     */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Prevent users from following themselves
        if ($currentUser->id === $user->id) {
            return back()->with('error', 'You cannot follow yourself.');
        }

        // Check if already following
        $existingFollow = Follower::where('user_id', $user->id)
            ->where('follower_id', $currentUser->id)
            ->first();

        if ($existingFollow) {
            // Unfollow
            $existingFollow->delete();
            $isFollowing = false;
            $message = "You unfollowed {$user->name}";

            // Broadcast unfollow event
            broadcast(new UserFollowed($user, $currentUser, false))->toOthers();

            // Redirect to current user's profile with success message
            return redirect("/profile/{$currentUser->username}")
                ->with('status', $message);
        } else {
            // Follow
            Follower::create([
                'user_id' => $user->id,
                'follower_id' => $currentUser->id,
            ]);
            $isFollowing = true;
            $message = "You are now following {$user->name}";

            // Send notification to the user being followed
            $user->notify(new UserFollowedNotification($currentUser));

            // Broadcast follow event
            broadcast(new UserFollowed($user, $currentUser, true))->toOthers();

            // Stay on the same page with success message
            return back()->with('status', $message);
        }
    }

    /**
     * Get followers list for a user.
     */
    public function followers(User $user): Response
    {
        $followers = $user->followers()
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return Inertia::render('Followers', [
            'user' => new UserResource($user),
            'followers' => UserResource::collection($followers),
            'type' => 'followers',
        ]);
    }

    /**
     * Get following list for a user.
     */
    public function following(User $user): Response
    {
        $following = $user->following()
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return Inertia::render('Followers', [
            'user' => new UserResource($user),
            'followers' => UserResource::collection($following),
            'type' => 'following',
        ]);
    }
}
