<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Models\Group;
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

        // Fetch user's groups (groups where user is a member)
        $groups = Group::whereHas('members', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->with('owner')
            ->withCount('members')
            ->latest()
            ->take(10) // Limit to 10 groups for sidebar
            ->get();

        return Inertia::render("Welcome", [
            'posts' => PostResource::collection($posts),
            'groups' => GroupResource::collection($groups),
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
