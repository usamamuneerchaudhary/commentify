<?php


use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentTest extends TestCase
{
    use WithFaker;

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
            'commentable_type' => 'App\Models\Article',
            'commentable_id' => $this->article->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
            'created_at' => now()
        ]);

//        $comment->user()->associate($this->user);
//        dd($comment);
//        $this->app->get('/articles/{article:slug}', [\App\Http\Controllers\ArticlesController::class]);
    }

    /** @test */
    public function it_shows_comment_component_livewire()
    {
        $this->actingAs($this->user);
        Livewire::test(Comments::class, [
            'model' => $this->article
        ])
            ->assertSee($this->comment->body) // Checks if property is wired
        ;
    }

    /** @test */
    public function it_shows_no_comments_text_if_empty_for_model()
    {
        Livewire::test(Comments::class, [
            'model' => $this->episode
        ])
            ->assertSee('No comments yet!') // Checks if property is wired
        ;
    }

    /** @test */
    public function it_doesnt_show_comment_form_if_logged_out()
    {
//        $this->actingAs($this->user);
        Livewire::test(Comments::class, [
            'model' => $this->article
        ])
            ->assertSee($this->comment->body)
            ->assertSee('Log in to comment!')
        ;
    }

}
