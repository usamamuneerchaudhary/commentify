<?php

use Illuminate\Contracts\View\View;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comment as LivewireComment;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentComponentTest extends TestCase
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
    public function it_can_edit_a_comment()
    {
        $this->actingAs($this->user);
        Livewire::test(\Usamamuneerchaudhary\Commentify\Http\Livewire\Comments::class, [
            'model' => $this->article
        ])
            ->set('newCommentState.body', $this->comment->body)
            ->call('postComment')
            ->assertSee($this->comment->body);

        Livewire::test(LivewireComment::class, [
            'comment' => $this->comment
        ])
            ->set('editState.body', 'Updated comment!!!')
            ->call('editComment')
            ->assertSee('Updated comment!!!');
    }

    /** @test */
    public function only_authenticated_user_can_edit_a_comment()
    {
        Livewire::test(LivewireComment::class, [
            'comment' => $this->comment
        ])
            ->assertSee($this->comment->body)
            ->assertDontSee('Reply');
    }

    /** @test */
    public function it_can_reply_to_a_comment()
    {
        $this->actingAs($this->user);
        $reply = $this->comment->children()->make([
            'body' => 'this is a reply',
            'parent_id' => $this->comment->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $reply->user()->associate($this->user);
        $reply->commentable()->associate($this->comment->commentable);
        $reply->save();

        Livewire::test(LivewireComment::class, [
            'comment' => $reply
        ])
            ->set('replyState.body', $reply)
            ->call('postReply')
            ->assertSee($reply->body);
    }

    /** @test */
    public function it_can_only_edit_to_a_comment_if_owner()
    {
        $this->actingAs($this->user);

        if ($this->user->id == $this->comment->user_id) {
            Livewire::test(LivewireComment::class, [
                'comment' => $this->comment
            ])
                ->set('editState.body', 'Updated comment!!!')
                ->call('editComment')
                ->assertSee('Updated comment!!!');
        }
    }

    /** @test */
    public function it_can_not_edit_a_comment_if_owned_by_another_user()
    {
        $newUser = User::factory()->create();
        $this->actingAs($newUser);

        if ($newUser->id != $this->comment->user_id) {
            Livewire::test(LivewireComment::class, [
                'comment' => $this->comment
            ])
                ->set('editState.body', 'Updated comment!!!')
                ->call('editComment')
                ->assertUnauthorized();
        }
    }

    /** @test */
    public function it_can_only_delete_a_comment_if_owner()
    {
        $this->actingAs($this->user);

        if ($this->user->id == $this->comment->user_id) {
            Livewire::test(LivewireComment::class, [
                'comment' => $this->comment
            ])
                ->call('deleteComment')
                ->dispatch('refresh')
                ->set('showOptions', false);
            $this->assertTrue($this->comment->delete());
            $this->assertDatabaseHas('comments', []);
        }
    }

    /** @test */
    public function only_authorized_users_can_edit_comments()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id
        ]);
        $this->actingAs(User::factory()->create());
        Livewire::test(LivewireComment::class, ['comment' => $comment])
            ->set('isEditing', true)
            ->set('editState.body', 'edited commented')
            ->call('editComment')
            ->assertStatus(401);
    }

    /** @test */
    public function only_authorized_users_can_delete_comments()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id
        ]);

        $this->actingAs(User::factory()->create());
        Livewire::test(LivewireComment::class, ['comment' => $comment])
            ->call('deleteComment')
            ->assertStatus(401);
    }

    /** @test */
    public function it_can_show_reply_boxes_for_child_comments()
    {
        $this->actingAs($this->user);
        $childComment = Comment::factory()->create([
            'parent_id' => $this->comment->id
        ]);

        Livewire::test(LivewireComment::class, ['comment' => $childComment])
            ->set('isReplying', true)
            ->assertSee('Post Reply')
            ->assertDontSee('Cancel');
    }

    /** @test */
    public function it_can_mention_users_in_reply()
    {
        $this->actingAs($this->user);
        $user = User::factory()->create([
            'name' => 'Usama Munir'
        ]);
        $childComment = Comment::factory()->create([
            'parent_id' => $this->comment->id
        ]);

        $component = Livewire::test(LivewireComment::class, ['comment' => $childComment])
            ->set('isReplying', true)
            ->assertDontSee('@Usama_Munir')
            ->assertDontSee('@usamamunir')
            ->set('replyState.body', '@usama')
            ->call('getUsers', 'usama')
            ->assertSee('Usama')
            ->call('selectUser', 'usama');
        $this->assertEquals('@usama ', $component->get('replyState.body'));
    }

    /** @test */
    public function it_can_mention_users_when_editing_a_child_comment()
    {
        $this->actingAs($this->user);
        $user = User::factory()->create([
            'name' => 'Usama Munir'
        ]);
        $childComment = Comment::factory()->create([
            'parent_id' => $this->comment->id
        ]);

        $component = Livewire::test(LivewireComment::class, ['comment' => $childComment])
            ->set('isEditing', true)
            ->assertDontSee('@Usama_Munir')
            ->assertDontSee('@usamamunir')
            ->set('editState.body', '@usama')
            ->call('getUsers', 'usama')
            ->assertSee('Usama')
            ->call('selectUser', 'usama');
        $this->assertEquals('@usama ', $component->get('editState.body'));
    }

    /** @test */
    public function it_can_mention_users_when_editing_a_comment()
    {
        $this->actingAs($this->user);
        $user = User::factory()->create([
            'name' => 'Usama Munir'
        ]);


        $component = Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isEditing', true)
            ->assertDontSee('@Usama_Munir')
            ->assertDontSee('@usamamunir')
            ->set('editState.body', '@usama')
            ->call('getUsers', 'usama')
            ->assertSee('Usama')
            ->call('selectUser', 'usama');
        $this->assertEquals('@usama ', $component->get('editState.body'));
    }

    /** @test */
    public function it_can_edit_comment()
    {
        $this->actingAs($this->user);
        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isEditing', true)
            ->set('editState.body', 'This is an edited comment!!!!')
            ->call('editComment')
            ->assertSee('This is an edited comment!!!!');
    }

    /** @test */
    public function it_can_delete_comment()
    {
        $this->actingAs($this->user);
        $this->assertNotNull($this->comment);
        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->call('deleteComment')
            ->dispatch('refresh')
            ->set('showOptions', false);
        $this->assertTrue($this->comment->delete());
        $this->assertDatabaseHas('comments', []);
    }

    /** @test */
    public function it_shows_validation_error_on_edit_submit_if_required_fields_empty()
    {
        $this->actingAs($this->user);
        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isEditing', true)
            ->set('editState.body', '')
            ->call('editComment')
            ->assertHasErrors(['editState.body' => 'required']);
    }

    /** @test */
    public function it_shows_validation_error_on_reply_post_if_body_empty()
    {
        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isReplying', true)
            ->set('replyState.body', '')
            ->call('postReply')
            ->assertHasErrors(['replyState.body' => 'required']);
    }

    /** @test */
    public function it_renders_livewire_component_correctly()
    {
        $this->actingAs($this->user);

        $view = Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->assertViewIs('commentify::livewire.comment')
            ->assertViewHas('comment');
    }

    /** @test */
    public function can_search_for_users_for_mentioning()
    {
        $this->actingAs($this->user);
        $user1 = User::factory()->create([
            'name' => 'Usama Munir'
        ]);
        $user2 = User::factory()->create([
            'name' => 'John Doe'
        ]);
        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isEditing', true)
            ->call('getUsers', 'usa')
            ->assertSee($user1->name);
    }

    /** @test */
    public function it_should_not_set_edit_state_if_not_editing()
    {
        $this->actingAs($this->user);

        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isEditing', false);

        $this->assertNotEquals([
            'body' => $this->comment->body
        ], $this->comment->editState);
    }

    /** @test */
    public function it_should_not_set_reply_state_if_not_replying()
    {
        $this->actingAs($this->user);

        Livewire::test(LivewireComment::class, ['comment' => $this->comment])
            ->set('isReplying', false);

        $this->assertNotEquals([
            'body' => $this->comment->body
        ], $this->comment->replyState);
    }

    /** @test */
    public function it_should_only_post_reply_if_parent_comment()
    {
        $this->actingAs($this->user);
        $reply = $this->comment->children()->make([
            'body' => 'this is a reply',
            'parent_id' => $this->comment->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $reply->user()->associate($this->user);
        $reply->commentable()->associate($this->comment->commentable);
        $reply->save();

        Livewire::test(LivewireComment::class, [
            'comment' => $reply
        ])
            ->set('isReplying', true)
            ->set('replyState.body', $reply)
            ->call('postReply')
            ->assertSee($reply->body);

        $this->assertCount(1, $this->comment->children);
        $this->assertEquals('this is a reply', $this->comment->children->first()->body);
    }

}
