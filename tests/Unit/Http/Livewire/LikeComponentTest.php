<?php

use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Like;
use Usamamuneerchaudhary\Commentify\Models\User;

class LikeComponentTest extends TestCase
{

    public $article;
    public $episode;
    public $comment;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = \ArticleStub::create([
            'slug' => \Illuminate\Support\Str::slug('Article One')
        ]);
        $this->episode = \EpisodeStub::create([
            'slug' => \Illuminate\Support\Str::slug('Episode One')
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
    public function it_can_like_comment()
    {
        Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 0])
            ->call('like')
            ->assertSee($this->comment->likes_count + 1);
    }

    /** @test */
    public function it_can_unlike_comment()
    {
        $this->comment->likes()->create(['user_id' => 1]);

        Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 1])
            ->call('like')
            ->assertSee($this->comment->likes_count - 1);
    }

    /** @test */
    public function auth_users_can_like_comment()
    {
        $this->actingAs($this->user);
        Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 0])
            ->call('like')
            ->assertSee($this->comment->likes_count + 1);
    }
}
