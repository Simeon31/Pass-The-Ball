<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for building and managing comment trees.
 * Uses optimized queries to prevent N+1 problems.
 */
class CommentTreeService
{
    /**
     * Maximum number of replies to load per comment level
     */
    private const REPLIES_PER_LEVEL = 3;

    /**
     * Build a tree structure from a flat collection of comments.
     * Uses in-memory grouping for optimal performance.
     *
     * @param Collection $comments All comments (flat)
     * @param int|null $parentId The parent ID to start from (null for top-level)
     * @param int $currentDepth Current depth in the tree
     * @return Collection Tree-structured comments
     */
    public function buildTree(Collection $comments, ?int $parentId = null, int $currentDepth = 0): Collection
    {
        // Group comments by parent_id for O(1) lookup
        $grouped = $comments->groupBy('parent_id');

        return $this->buildTreeRecursive($grouped, $parentId, $currentDepth);
    }

    /**
     * Recursive tree building with memoization.
     *
     * @param \Illuminate\Support\Collection $grouped Comments grouped by parent_id
     * @param int|null $parentId Current parent ID
     * @param int $currentDepth Current depth in tree
     * @return Collection
     */
    private function buildTreeRecursive($grouped, ?int $parentId, int $currentDepth): Collection
    {
        // Base case: no children at this level or max depth reached
        if (!$grouped->has($parentId) || $currentDepth >= Comment::MAX_DEPTH) {
            return collect();
        }

        $children = $grouped->get($parentId);

        return $children->map(function ($comment) use ($grouped, $currentDepth) {
            // Attach depth for frontend rendering
            $comment->depth = $currentDepth;

            // Recursively build children
            $replies = $this->buildTreeRecursive($grouped, $comment->id, $currentDepth + 1);

            // Attach replies as a relationship-like property
            $comment->setRelation('replies', $replies);

            // Set flag if there are more replies (for load more functionality)
            $comment->has_more_replies = $replies->count() > self::REPLIES_PER_LEVEL;

            return $comment;
        });
    }

    /**
     * Get comments for a post as a tree structure with pagination.
     * Optimized with single query + eager loading.
     *
     * @param Post $post
     * @param int $page
     * @param int $perPage Number of top-level comments per page
     * @return array ['data' => Collection, 'total' => int, 'hasMore' => bool]
     */
    public function getCommentsTree(Post $post, int $page = 1, int $perPage = 5): array
    {
        // Get total count of top-level comments
        $totalTopLevel = Comment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->count();

        // Get paginated top-level comment IDs
        $topLevelIds = Comment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->pluck('id')
            ->toArray();

        if (empty($topLevelIds)) {
            return [
                'data' => collect(),
                'total' => $totalTopLevel,
                'hasMore' => false,
            ];
        }

        // Single query to get all comments in the tree (top-level + all their descendants)
        // Uses recursive CTE for PostgreSQL/MySQL 8.0+ or fallback for older versions
        $allComments = $this->fetchCommentsWithDescendants($post->id, $topLevelIds);

        // Build the tree structure
        $tree = $this->buildTree($allComments);

        return [
            'data' => $tree,
            'total' => $totalTopLevel,
            'hasMore' => ($page * $perPage) < $totalTopLevel,
        ];
    }

    /**
     * Fetch all comments and their descendants for given top-level IDs.
     * Uses optimized query with recursive CTE when available.
     *
     * @param int $postId
     * @param array $topLevelIds
     * @return Collection
     */
    private function fetchCommentsWithDescendants(int $postId, array $topLevelIds): Collection
    {
        $driver = DB::connection()->getDriverName();

        // Use recursive CTE for databases that support it
        if (in_array($driver, ['mysql', 'pgsql', 'sqlsrv'])) {
            return $this->fetchWithRecursiveCTE($postId, $topLevelIds);
        }

        // Fallback: fetch all comments for the post and filter in PHP
        return Comment::where('post_id', $postId)
            ->with(['user', 'reactions'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->filter(function ($comment) use ($topLevelIds) {
                // Include if it's a top-level comment we want
                if (in_array($comment->id, $topLevelIds)) {
                    return true;
                }

                // Include if any ancestor is in our top-level list
                $current = $comment;
                while ($current->parent_id) {
                    if (in_array($current->parent_id, $topLevelIds)) {
                        return true;
                    }
                    // Get parent from already loaded collection
                    $current = Comment::find($current->parent_id);
                    if (!$current)
                        break;
                }

                return false;
            });
    }

    /**
     * Fetch comments using recursive CTE (Common Table Expression).
     * This is the most efficient approach for tree queries.
     *
     * @param int $postId
     * @param array $topLevelIds
     * @return Collection
     */
    private function fetchWithRecursiveCTE(int $postId, array $topLevelIds): Collection
    {
        $idsString = implode(',', $topLevelIds);

        $query = "
            WITH RECURSIVE comment_tree AS (
                -- Base case: select top-level comments
                SELECT 
                    id, post_id, parent_id, user_id, comment, created_at, updated_at,
                    0 as depth
                FROM comments
                WHERE post_id = ? 
                AND id IN ({$idsString})
                
                UNION ALL
                
                -- Recursive case: select children
                SELECT 
                    c.id, c.post_id, c.parent_id, c.user_id, c.comment, c.created_at, c.updated_at,
                    ct.depth + 1
                FROM comments c
                INNER JOIN comment_tree ct ON c.parent_id = ct.id
                WHERE ct.depth < ?
            )
            SELECT * FROM comment_tree
            ORDER BY created_at ASC
        ";

        $results = DB::select($query, [$postId, Comment::MAX_DEPTH]);

        // Convert to Comment models and eager load relationships
        $commentIds = collect($results)->pluck('id')->toArray();

        return Comment::whereIn('id', $commentIds)
            ->with(['user', 'reactions'])
            ->get()
            ->sortBy('created_at');
    }

    /**
     * Get immediate replies for a comment (for lazy loading).
     *
     * @param Comment $comment
     * @param int $page
     * @param int $perPage
     * @return array ['data' => Collection, 'total' => int, 'hasMore' => bool]
     */
    public function getReplies(Comment $comment, int $page = 1, int $perPage = 5): array
    {
        $total = $comment->replies()->count();

        $replies = $comment->replies()
            ->with(['user', 'reactions'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Add depth to each reply
        $replies->each(function ($reply) use ($comment) {
            $reply->depth = ($comment->depth ?? $comment->getDepth()) + 1;
        });

        return [
            'data' => $replies,
            'total' => $total,
            'hasMore' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Calculate statistics for a comment thread.
     *
     * @param Comment $comment
     * @return array
     */
    public function getThreadStats(Comment $comment): array
    {
        // Count all descendants recursively
        $totalReplies = 0;
        $maxDepth = 0;

        $countDescendants = function ($parent, $currentDepth = 0) use (&$countDescendants, &$totalReplies, &$maxDepth) {
            $replies = $parent->replies;
            $totalReplies += $replies->count();

            if ($currentDepth > $maxDepth) {
                $maxDepth = $currentDepth;
            }

            foreach ($replies as $reply) {
                $countDescendants($reply, $currentDepth + 1);
            }
        };

        $countDescendants($comment);

        return [
            'total_replies' => $totalReplies,
            'max_depth' => $maxDepth,
        ];
    }
}
