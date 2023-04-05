<?php

use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentTest extends TestCase
{

    public $article;
    public $comment;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = \ArticleStub::create([
            'slug' => \Illuminate\Support\Str::slug('Article One')
        ]);
        $this->user = User::factory()->create();

        $this->comment = $this->article->comments()->create([
            'body' => 'This is a test comment!',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => now()
        ]);
    }

    /** @test */
    public function comment_can_be_persisted_in_database()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => $comment->body
        ]);
    }

    /** @test */
    public function comment_has_user_relation()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
    }

    /** @test */
    public function comment_has_children_relation()
    {
        $comment = Comment::factory()->create([
            'parent_id' => null
        ]);
        Comment::factory()->count(2)->create([
            'parent_id' => $comment->id
        ]);

        $this->assertInstanceOf(Comment::class, $comment->children->first());
        $this->assertCount(2, $comment->children);
    }

    /** @test */
    public function comment_has_commentable_relation()
    {
        $this->assertEquals('ArticleStub', $this->comment->commentable_type);
        $this->assertEquals(1, $this->comment->commentable_id);
    }
}
