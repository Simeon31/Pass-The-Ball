<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->whereNull('group_id')
            ->latest()
            ->paginate(10);

        return Inertia::render("Welcome", [
            'posts' => PostResource::collection($posts),
        ]);
    }

    public function getPosts(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $posts = Post::query()
            ->whereNull('group_id') // Only show posts that are not in groups
            ->latest()
            ->with([
                'user',
                'reactions',
                'comments.user',
                'comments.reactions',
                'attachments',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return PostResource::collection($posts);
    }
}
