<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created comment
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'comment' => $request->input('comment'),
        ]);

        // Load the user relationship
        $comment->load('user');

        // Broadcast the new comment for real-time updates
        $commentResource = new CommentResource($comment);
        broadcast(new CommentCreated($post->id, $commentResource))->toOthers();

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $commentResource,
        ]);
    }

    /**
     * Get paginated comments for a post
     */
    public function index(Request $request, Post $post)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);

        // Get comments ordered by most recent first
        $comments = $post->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return CommentResource::collection($comments);
    }

    /**
     * Delete a comment
     */
    public function destroy(Comment $comment)
    {
        // Check if user owns the comment or the post
        if (auth()->id() !== $comment->user_id && auth()->id() !== $comment->post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
