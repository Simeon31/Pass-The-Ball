<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page (Facebook-like public view).
     */
    public function show($username): Response
    {
        // Find user by username
        $user = User::where('username', $username)->firstOrFail();

        // Load relationships - only personal posts (not group posts)
        $user->load([
            'posts' => function ($query) {
                $query->whereNull('group_id') // Only personal posts, not group posts
                    ->latest()
                    ->with([
                        'user',
                        'reactions',
                        'comments.user',
                        'comments.reactions',
                        'attachments'
                    ]);
            }
        ]);

        // Transform posts using PostResource
        $posts = \App\Http\Resources\PostResource::collection($user->posts);

        return Inertia::render('Profile', [
            'user' => new UserResource($user),
            'posts' => $posts,
        ]);
    }

    /**
     * Search for users (API endpoint for invitations).
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $limit = $request->input('limit', 10);

        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('username', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->where('id', '!=', auth()->id()) // Exclude current user
            ->limit($limit)
            ->get();

        return response()->json([
            'users' => UserResource::collection($users),
        ]);
    }
}
