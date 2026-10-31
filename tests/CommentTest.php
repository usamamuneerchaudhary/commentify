<?php

use Illuminate\Support\Str;
use Usamamuneerchaudhary\Commentify\Events\CommentPosting;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;
use Usamamuneerchaudhary\Commentify\Support\MentionSearch;

class CommentTest extends TestCase
{
    public $article;

    public $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->article = ArticleStub::create([
            'slug' => Str::slug('Article One'),
        ]);
        $this->user = User::factory()->create([
            'comment_banned_until' => null, // Not banned
        ]);

        $this->comment = $this->article->comments()->create([
            'body' => 'This is a test comment!',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => now(),
        ]);
    }

    public function test_comment_can_be_persisted_in_database(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => $comment->body,
        ]);
    }

    public function test_comment_has_user_relation(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
    }

    public function test_comment_has_children_relation(): void
    {
        $comment = Comment::factory()->create([
            'parent_id' => null,
        ]);
        Comment::factory()->count(2)->create([
            'parent_id' => $comment->id,
        ]);

        $this->assertInstanceOf(Comment::class, $comment->children->first());
        $this->assertCount(2, $comment->children);
    }

    public function test_for_thread_keeps_every_comment_when_the_column_is_absent(): void
    {
        Comment::flushColumnCache();

        $match = $this->article->comments()->forThread('reviews')->first();

        $this->assertTrue($this->comment->is($match));
        $this->assertFalse(Comment::hasThreadColumn());
    }

    public function test_is_edited_is_false_without_a_revision_history(): void
    {
        $this->assertFalse($this->comment->isEdited());
    }

    public function test_comment_posting_can_be_cancelled(): void
    {
        $event = new CommentPosting($this->comment);

        $this->assertFalse($event->isCancelled());

        $event->cancel('nope');

        $this->assertTrue($event->isCancelled());
        $this->assertSame('nope', $event->reason);
    }

    public function test_mention_search_treats_percent_signs_as_literal_text(): void
    {
        config(['commentify.user_model' => User::class]);

        User::factory()->create(['name' => 'Alice Cooper']);

        $this->assertCount(0, MentionSearch::defaultQuery('%%'));
        $this->assertSame(
            ['Alice Cooper'],
            MentionSearch::defaultQuery('alice')->pluck('name')->all(),
        );
    }

    public function test_comment_has_commentable_relation(): void
    {
        $this->assertEquals('ArticleStub', $this->comment->commentable_type);
        $this->assertEquals(1, $this->comment->commentable_id);
    }
}
