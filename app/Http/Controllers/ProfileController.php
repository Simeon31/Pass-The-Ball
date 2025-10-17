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

        // Load relationships
        $user->load([
            'posts' => function ($query) {
                $query->latest()
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
}
