<?php

use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentsComponentTest extends TestCase
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
        $this->user = User::factory()->create([
            'comment_banned_until' => null, // Not banned
        ]);

        $this->comment = $this->article->comments()->create([
            'body' => 'This is a test comment!',
            'commentable_type' => '\ArticleStub',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => now()
        ]);
    }

    public function test_it_shows_comment_component_livewire(): void
    {
        $this->actingAs($this->user);
        Livewire::test(Comments::class, [
            'model' => $this->article
        ])
            ->set('newCommentState.body', $this->comment->body)
            ->call('postComment')
            ->assertSee($this->comment->body);
    }

    public function test_it_shows_no_comments_text_if_empty_for_model(): void
    {
        Livewire::test(Comments::class, [
            'model' => $this->episode
        ])
            ->assertSee('No comments yet!');
    }

    public function test_it_doesnt_show_comment_form_if_logged_out(): void
    {
        Livewire::test(Comments::class, [
            'model' => $this->article
        ])
            ->assertSee($this->comment->body)
            ->assertSee('Log in to comment!');
    }

    public function test_it_show_comment_form_if_logged_in(): void
    {
        $this->actingAs($this->user);
        Livewire::test(Comments::class, [
            'model' => $this->article
        ])
            ->set('newCommentState.body', $this->comment->body)
            ->call('postComment')
            ->assertSee($this->comment->body)
            ->assertSee('Your comment')
            ->assertSee('Post comment');
        $this->assertTrue(Comment::where('body', $this->comment->body)->exists());
        $this->assertDatabaseHas('comments', [
            'body' => $this->comment->body,
            'user_id' => $this->user->id,
            'commentable_id' => $this->article->id
        ]);
    }

    public function test_only_logged_in_user_can_post_a_new_comment(): void
    {
        $this->actingAs($this->user);
        $this->episode->comments()->create([
            'body' => 'This is an episode comment!',
            'commentable_type' => 'App\Models\Episode',
            'commentable_id' => $this->episode->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => now()
        ]);

        Livewire::test(Comments::class, [
            'model' => $this->episode
        ])
            ->set('newCommentState.body', $this->episode->comments()->first()->body)
            ->call('postComment')
            ->assertSee($this->episode->comments()->first()->body);
        $this->assertTrue(Comment::where('body', $this->episode->comments()->first()->body)
            ->exists());
        $this->assertDatabaseHas('comments', [
            'body' => $this->episode->comments()->first()->body,
            'user_id' => $this->user->id,
            'commentable_id' => $this->episode->id
        ]);
    }

    public function test_it_shows_validation_error_on_adding_comment_if_required_fields_empty(): void
    {
        $user = User::factory()->create([
            'comment_banned_until' => null,
        ]);
        $this->actingAs($user);

        Livewire::test(Comments::class, ['model' => $this->article])
            ->set('newCommentState.body', '')
            ->call('postComment')
            ->assertHasErrors(['newCommentState.body' => 'required']);
    }

    public function test_it_can_see_comments_total_count(): void
    {
        Livewire::test(Comments::class, ['model' => $this->article])
            ->assertSee($this->article->comments()->count());
    }


    public function test_pagination_links_if_comments_count(): void
    {
        Comment::factory(15)->create([
            'commentable_id' => $this->article->id,
            'commentable_type' => 'ArticleStub'
        ]);

        Livewire::test(Comments::class, ['model' => $this->article])
            ->assertSee(10)
            ->assertSeeHtml('<span wire:key="paginator-page-page1">')
            ->assertSee(2);//second page link
    }

    public function test_no_pagination_links_if_comments_count_less_than_10(): void
    {
        Comment::factory(5)->create([
            'commentable_id' => $this->article->id,
            'commentable_type' => 'ArticleStub'
        ]);

        Livewire::test(Comments::class, ['model' => $this->article])
            ->assertSee(6)
            ->assertDontSeeHtml('<span wire:key="paginator-page-1-page2"></span>');
    }

    public function test_it_renders_livewire_component_correctly(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Comments::class, ['model' => $this->article])
            ->assertViewIs('commentify::livewire.comments')
            ->assertViewHas('comments');
    }

}
