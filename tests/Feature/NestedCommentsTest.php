<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NestedCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_creates_a_top_level_comment()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/post/{$this->post->id}/comment", [
                'comment' => 'This is a top-level comment',
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'comment' => 'This is a top-level comment',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function it_creates_a_nested_reply()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/post/{$this->post->id}/comment", [
                'comment' => 'This is a reply',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'comment' => 'This is a reply',
        ]);
    }

    /** @test */
    public function it_prevents_nesting_beyond_max_depth()
    {
        // Create nested comments up to MAX_DEPTH
        $comments = [];
        $comments[0] = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        for ($i = 1; $i <= Comment::MAX_DEPTH; $i++) {
            $comments[$i] = Comment::factory()->create([
                'post_id' => $this->post->id,
                'parent_id' => $comments[$i - 1]->id,
            ]);
        }

        // Try to create one more level (should fail)
        $response = $this->actingAs($this->user)
            ->postJson("/post/{$this->post->id}/comment", [
                'comment' => 'Too deep',
                'parent_id' => $comments[Comment::MAX_DEPTH]->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Maximum comment nesting depth reached']);
    }

    /** @test */
    public function it_validates_parent_belongs_to_same_post()
    {
        $otherPost = Post::factory()->create(['user_id' => $this->user->id]);
        $otherComment = Comment::factory()->create([
            'post_id' => $otherPost->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/post/{$this->post->id}/comment", [
                'comment' => 'Reply',
                'parent_id' => $otherComment->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Parent comment does not belong to this post']);
    }

    /** @test */
    public function it_returns_comments_in_tree_structure()
    {
        $comment1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
            'comment' => 'Top-level 1',
        ]);

        $comment2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment1->id,
            'comment' => 'Reply to 1',
        ]);

        $comment3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment2->id,
            'comment' => 'Reply to 2',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/post/{$this->post->id}/comments");

        $response->assertSuccessful();

        $data = $response->json('data');

        // Check tree structure
        $this->assertCount(1, $data); // One top-level comment
        $this->assertEquals('Top-level 1', $data[0]['comment']);
        $this->assertEquals(0, $data[0]['depth']);

        // Check first level reply
        $this->assertCount(1, $data[0]['replies']);
        $this->assertEquals('Reply to 1', $data[0]['replies'][0]['comment']);
        $this->assertEquals(1, $data[0]['replies'][0]['depth']);

        // Check second level reply
        $this->assertCount(1, $data[0]['replies'][0]['replies']);
        $this->assertEquals('Reply to 2', $data[0]['replies'][0]['replies'][0]['comment']);
        $this->assertEquals(2, $data[0]['replies'][0]['replies'][0]['depth']);
    }

    /** @test */
    public function it_cascade_deletes_nested_comments()
    {
        $comment1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        $comment2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment1->id,
        ]);

        $comment3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment2->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/comment/{$comment1->id}");

        // All comments should be deleted
        $this->assertDatabaseMissing('comments', ['id' => $comment1->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment2->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment3->id]);
    }

    /** @test */
    public function it_loads_more_replies_for_a_comment()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        // Create 10 replies
        Comment::factory()->count(10)->create([
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/comment/{$parentComment->id}/replies?page=1&per_page=5");

        $response->assertSuccessful();

        $this->assertCount(5, $response->json('data'));
        $this->assertTrue($response->json('has_more'));
        $this->assertEquals(10, $response->json('total'));
    }

    /** @test */
    public function it_paginates_top_level_comments()
    {
        // Create 10 top-level comments
        Comment::factory()->count(10)->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/post/{$this->post->id}/comments?page=1&per_page=5");

        $response->assertSuccessful();

        $this->assertCount(5, $response->json('data'));
        $this->assertTrue($response->json('has_more'));
        $this->assertEquals(10, $response->json('total'));
    }

    /** @test */
    public function it_calculates_depth_correctly()
    {
        $depth0 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => null]);
        $depth1 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $depth0->id]);
        $depth2 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $depth1->id]);
        $depth3 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $depth2->id]);

        $this->assertEquals(0, $depth0->fresh()->getDepth());
        $this->assertEquals(1, $depth1->fresh()->getDepth());
        $this->assertEquals(2, $depth2->fresh()->getDepth());
        $this->assertEquals(3, $depth3->fresh()->getDepth());
    }

    /** @test */
    public function it_checks_if_comment_is_at_max_depth()
    {
        $comments = [];
        $comments[0] = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => null]);

        for ($i = 1; $i <= Comment::MAX_DEPTH; $i++) {
            $comments[$i] = Comment::factory()->create([
                'post_id' => $this->post->id,
                'parent_id' => $comments[$i - 1]->id,
            ]);
        }

        $this->assertFalse($comments[0]->fresh()->isAtMaxDepth());
        $this->assertFalse($comments[1]->fresh()->isAtMaxDepth());
        $this->assertTrue($comments[Comment::MAX_DEPTH]->fresh()->isAtMaxDepth());
    }

    /** @test */
    public function it_identifies_top_level_comments()
    {
        $topLevel = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => null]);
        $reply = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $topLevel->id]);

        $this->assertTrue($topLevel->isTopLevel());
        $this->assertFalse($reply->isTopLevel());
    }

    /** @test */
    public function it_uses_top_level_scope()
    {
        Comment::factory()->count(5)->create(['post_id' => $this->post->id, 'parent_id' => null]);

        $topLevel = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => null]);
        Comment::factory()->count(3)->create(['post_id' => $this->post->id, 'parent_id' => $topLevel->id]);

        $topLevelComments = Comment::where('post_id', $this->post->id)
            ->topLevel()
            ->get();

        $this->assertCount(6, $topLevelComments); // 5 + 1, excluding replies
    }

    /** @test */
    public function it_gets_ancestors_of_a_comment()
    {
        $level0 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => null]);
        $level1 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $level0->id]);
        $level2 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $level1->id]);

        $ancestors = $level2->fresh()->ancestors();

        $this->assertCount(2, $ancestors);
        $this->assertTrue($ancestors->contains($level1));
        $this->assertTrue($ancestors->contains($level0));
    }
}
