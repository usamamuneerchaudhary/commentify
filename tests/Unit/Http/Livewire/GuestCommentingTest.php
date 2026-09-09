<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class GuestCommentingTest extends TestCase
{
    public function test_guest_can_post_comment_when_allowed(): void
    {
        config(['commentify.allow_guests' => true]);

        $article = ArticleStub::create([
            'slug' => Str::slug('Guest Article'),
        ]);

        Livewire::test(Comments::class, ['model' => $article])
            ->assertSee('Your name')
            ->assertSee('you@example.com')
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

        $article = ArticleStub::create([
            'slug' => Str::slug('No Guest Article'),
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

    public function test_guest_can_omit_email_when_not_required(): void
    {
        config([
            'commentify.allow_guests' => true,
            'commentify.guest.require_email' => false,
        ]);

        $article = ArticleStub::create([
            'slug' => Str::slug('Optional Email Article'),
        ]);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('guest_name', 'Nameless Guest')
            ->set('newCommentState.body', 'No email from me')
            ->call('postComment')
            ->assertSee('No email from me');

        $comment = Comment::query()->where('body', 'No email from me')->first();

        $this->assertNotNull($comment);
        $this->assertSame('Nameless Guest', $comment->guest_name);
        $this->assertNull($comment->guest_email);
    }
}
