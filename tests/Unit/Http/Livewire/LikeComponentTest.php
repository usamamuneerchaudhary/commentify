<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Like;
use Usamamuneerchaudhary\Commentify\Models\User;

class LikeComponentTest extends TestCase
{
    public $article;

    public $episode;

    public $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->article = ArticleStub::create([
            'slug' => Str::slug('Article One'),
        ]);
        $this->episode = EpisodeStub::create([
            'slug' => Str::slug('Episode One'),
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

    public function test_it_can_like_comment(): void
    {
        Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 0])
            ->call('like')
            ->assertSee($this->comment->likes_count + 1);
    }

    public function test_it_can_unlike_comment(): void
    {
        $this->comment->likes()->create(['user_id' => 1]);

        Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 1])
            ->call('like')
            ->assertSee($this->comment->likes_count - 1);
    }

    public function test_auth_users_can_like_comment(): void
    {
        $this->actingAs($this->user);
        $component = Livewire::test(Like::class, ['comment' => $this->comment, 'count' => 0])
            ->call('like');

        // Check that count increased
        $this->assertEquals(1, $component->get('count'));

        // Verify like was created in database
        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_uuid_authenticated_users_toggle_likes_by_ip(): void
    {
        $user = new class extends Illuminate\Foundation\Auth\User
        {
            public $incrementing = false;

            protected $keyType = 'string';

            protected $guarded = [];
        };
        $user->forceFill([
            'id' => '8523f9c3-9b12-4577-afe5-aa3300bdb640',
            'name' => 'Statamic',
            'email' => 'cp@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        Livewire::test(Like::class, ['comment' => $this->comment])
            ->call('like')
            ->assertSet('count', 1)
            ->call('like')
            ->assertSet('count', 0);

        $this->assertSame(0, $this->comment->likes()->count());
    }
}
