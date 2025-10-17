<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentTreeService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Comment tree service for handling nested comments.
     */
    protected CommentTreeService $treeService;

    public function __construct(CommentTreeService $treeService)
    {
        $this->treeService = $treeService;
    }

    /**
     * Store a newly created comment or reply.
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        // If replying to a comment, verify it belongs to this post
        if (!empty($validated['parent_id'])) {
            $parentComment = Comment::findOrFail($validated['parent_id']);

            if ($parentComment->post_id !== $post->id) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this post'
                ], 422);
            }

            // Check if parent is at max depth
            if ($parentComment->isAtMaxDepth()) {
                return response()->json([
                    'message' => 'Maximum comment nesting depth reached'
                ], 422);
            }
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Load the user relationship
        $comment->load('user', 'reactions');

        // Set depth for the resource
        $comment->depth = $comment->parent_id ? $comment->getDepth() : 0;

        // Broadcast the new comment for real-time updates
        $commentResource = new CommentResource($comment);
        broadcast(new CommentCreated($post->id, $commentResource))->toOthers();

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $commentResource,
        ]);
    }

    /**
     * Get comments tree for a post with pagination.
     */
    public function index(Request $request, Post $post)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);

        // Get tree-structured comments
        $result = $this->treeService->getCommentsTree($post, $page, $perPage);

        return response()->json([
            'data' => CommentResource::collection($result['data']),
            'total' => $result['total'],
            'has_more' => $result['hasMore'],
            'current_page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Get replies for a specific comment (lazy loading).
     */
    public function replies(Request $request, Comment $comment)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);

        $result = $this->treeService->getReplies($comment, $page, $perPage);

        return response()->json([
            'data' => CommentResource::collection($result['data']),
            'total' => $result['total'],
            'has_more' => $result['hasMore'],
            'current_page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, Comment $comment)
    {
        // Check if user owns the comment
        if (auth()->id() !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment->update([
            'comment' => $request->input('comment'),
        ]);

        // Load the user relationship
        $comment->load('user', 'reactions');
        $comment->depth = $comment->getDepth();

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => new CommentResource($comment),
        ]);
    }

    /**
     * Delete a comment.
     * Cascade deletes all child comments due to foreign key constraint.
     */
    public function destroy(Comment $comment)
    {
        // Check if user owns the comment or the post
        if (auth()->id() !== $comment->user_id && auth()->id() !== $comment->post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get count of descendants for feedback
        $stats = $this->treeService->getThreadStats($comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
            'deleted_replies' => $stats['total_replies'],
        ]);
    }
}

