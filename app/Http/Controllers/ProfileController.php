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
    public function show(User $user): Response
    {
        // Load relationships if needed
        $user->load([
            'posts' => function ($query) {
                $query->latest()->with(['user', 'reactions', 'comments']);
            }
        ]);

        return Inertia::render('Profile', [
            'user' => new UserResource($user),
            'posts' => $user->posts,
        ]);
    }
}
