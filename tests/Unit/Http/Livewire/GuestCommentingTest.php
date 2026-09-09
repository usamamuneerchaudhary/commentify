<?php

use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class GuestCommentingTest extends TestCase
{
    public function test_guest_can_post_comment_when_allowed(): void
    {
        config(['commentify.allow_guests' => true]);

        $article = \ArticleStub::create([
            'slug' => \Illuminate\Support\Str::slug('Guest Article'),
        ]);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('guest_name', 'Jane Guest')
            ->set('guest_email', 'jane@example.com')
            ->set('newCommentState.body', 'Hello from a guest!')
            ->call('postComment')
            ->assertSee('Hello from a guest!');

        $comment = Comment::query()->where('body', 'Hello from a guest!')->first();

        $this->assertNotNull($comment);
        $this->assertNull($comment->user_id);
        $this->assertSame('Jane Guest', $comment->guest_name);
        $this->assertSame('jane@example.com', $comment->guest_email);
        $this->assertTrue($comment->isGuest());
    }

    public function test_guest_cannot_post_when_disabled(): void
    {
        config(['commentify.allow_guests' => false]);

        $article = \ArticleStub::create([
            'slug' => \Illuminate\Support\Str::slug('No Guest Article'),
        ]);

        Livewire::test(Comments::class, ['model' => $article])
            ->assertSee('Log in to comment!')
            ->assertDontSee('Your name');
    }

    public function test_guest_comment_uses_gravatar(): void
    {
        config(['commentify.allow_guests' => true]);

        $comment = Comment::query()->create([
            'body' => 'Guest avatar test',
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => 1,
            'is_approved' => true,
        ]);

        $expectedHash = md5('guest@example.com');

        $this->assertStringContainsString($expectedHash, $comment->authorAvatar());
    }
}
