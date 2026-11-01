<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comment as LivewireComment;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;
use Usamamuneerchaudhary\Commentify\Notifications\CommentPostedNotification;
use Usamamuneerchaudhary\Commentify\Traits\Commentable;
use Usamamuneerchaudhary\CommentifyPro\Listeners\SendCommentNotifications;

class CommentPostedNotificationTest extends TestCase
{
    public function test_a_reply_notifies_the_parent_author(): void
    {
        [$author, $replier, $reply] = $this->replyBetweenTwoUsers();

        event(new CommentPosted($reply));

        Notification::assertSentTo(
            $author,
            CommentPostedNotification::class,
            function (CommentPostedNotification $notification) use ($author, $replier, $reply): bool {
                $payload = $notification->toArray($author);

                return $payload['comment_id'] === $reply->id
                    && $payload['user_id'] === $replier->id
                    && $payload['user_name'] === $replier->name
                    && $payload['message'] === $replier->name.' posted a new comment.';
            },
        );
        Notification::assertNotSentTo($replier, CommentPostedNotification::class);
    }

    public function test_notifications_stay_off_until_enabled(): void
    {
        config(['commentify.enable_notifications' => false]);
        [, , $reply] = $this->replyBetweenTwoUsers(enableNotifications: false);

        event(new CommentPosted($reply));

        Notification::assertNothingSent();
    }

    public function test_a_pending_comment_does_not_notify(): void
    {
        [, , $reply] = $this->replyBetweenTwoUsers();
        $reply->is_approved = false;
        $reply->save();

        event(new CommentPosted($reply));

        Notification::assertNothingSent();
    }

    public function test_an_author_is_not_notified_about_their_own_reply(): void
    {
        $author = User::factory()->create();
        $article = ArticleStub::create(['slug' => 'self']);
        $parent = $this->comment($article, $author, 'Parent');
        $reply = $this->comment($article, $author, 'Reply', $parent);

        event(new CommentPosted($reply));

        Notification::assertNothingSent();
    }

    public function test_a_top_level_comment_notifies_the_commentable_owner(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $article = $this->articleOwnedBy($owner);
        $comment = $this->comment($article, $author, 'Hello');

        event(new CommentPosted($comment));

        Notification::assertSentTo($owner, CommentPostedNotification::class);
        Notification::assertNotSentTo($author, CommentPostedNotification::class);
    }

    public function test_the_owner_is_not_notified_about_their_own_comment(): void
    {
        $owner = User::factory()->create();
        $article = $this->articleOwnedBy($owner);
        $comment = $this->comment($article, $owner, 'Hello');

        event(new CommentPosted($comment));

        Notification::assertNothingSent();
    }

    public function test_a_reply_notifies_the_parent_author_ahead_of_the_owner(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $replier = User::factory()->create();
        $article = $this->articleOwnedBy($owner);
        $parent = $this->comment($article, $author, 'Parent');
        $reply = $this->comment($article, $replier, 'Reply', $parent);

        event(new CommentPosted($reply));

        Notification::assertSentTo($author, CommentPostedNotification::class);
        Notification::assertNotSentTo($owner, CommentPostedNotification::class);
        Notification::assertNotSentTo($replier, CommentPostedNotification::class);
    }

    public function test_a_reply_to_a_guest_notifies_the_owner(): void
    {
        $owner = User::factory()->create();
        $replier = User::factory()->create();
        $article = $this->articleOwnedBy($owner);
        $parent = $article->comments()->create([
            'body' => 'Guest parent',
            'user_id' => null,
            'guest_name' => 'Pat Guest',
            'is_approved' => true,
        ]);
        $reply = $this->comment($article, $replier, 'Reply', $parent);

        event(new CommentPosted($reply));

        Notification::assertSentTo($owner, CommentPostedNotification::class);
        Notification::assertNotSentTo($replier, CommentPostedNotification::class);
    }

    public function test_a_reply_to_a_guest_with_no_owner_notifies_nobody(): void
    {
        $replier = User::factory()->create();
        $article = ArticleStub::create(['slug' => 'guest-parent']);
        $parent = $article->comments()->create([
            'body' => 'Guest parent',
            'user_id' => null,
            'guest_name' => 'Pat Guest',
            'is_approved' => true,
        ]);
        $reply = $this->comment($article, $replier, 'Reply', $parent);

        event(new CommentPosted($reply));

        Notification::assertNothingSent();
    }

    public function test_a_top_level_comment_notifies_nobody_without_an_owner(): void
    {
        $author = User::factory()->create();
        $article = ArticleStub::create(['slug' => 'plain']);
        $comment = $this->comment($article, $author, 'Hello');

        event(new CommentPosted($comment));

        Notification::assertNothingSent();
    }

    public function test_a_missing_owner_notifies_nobody(): void
    {
        $author = User::factory()->create();
        $article = $this->articleOwnedBy(null);
        $comment = $this->comment($article, $author, 'Hello');

        event(new CommentPosted($comment));

        Notification::assertNothingSent();
    }

    public function test_an_owner_without_notifications_is_skipped(): void
    {
        $this->ensureOwnerColumn();

        $owner = User::factory()->create();
        $author = User::factory()->create();
        $article = CommentableWithPlainOwnerStub::query()->create([
            'slug' => 'plain-owner',
            'user_id' => $owner->id,
        ]);
        $comment = $this->comment($article, $author, 'Hello');

        event(new CommentPosted($comment));

        Notification::assertNothingSent();
    }

    public function test_a_guest_comment_uses_the_guest_name_in_the_notification(): void
    {
        $owner = User::factory()->create();
        $comment = $this->articleOwnedBy($owner)->comments()->create([
            'body' => 'Hello',
            'user_id' => null,
            'guest_name' => 'Jane Guest',
            'is_approved' => true,
        ]);
        $notification = new CommentPostedNotification(new CommentPosted($comment));

        $payload = $notification->toArray($owner);
        $mail = $notification->toMail($owner);

        $this->assertNull($payload['user_id']);
        $this->assertSame('Jane Guest', $payload['user_name']);
        $this->assertSame('Jane Guest posted a new comment.', $payload['message']);
        $this->assertSame('New Comment Posted', $mail->subject);
        $this->assertSame(['Jane Guest posted a new comment.'], $mail->introLines);
    }

    public function test_delivery_channels_keep_database_mail_and_broadcast(): void
    {
        config(['commentify.notification_channels' => ['database', 'mail', 'broadcast', 'slack']]);

        $owner = User::factory()->create();
        $comment = $this->comment($this->articleOwnedBy($owner), User::factory()->create(), 'Hello');
        $notification = new CommentPostedNotification(new CommentPosted($comment));

        $this->assertSame(
            ['database', 'mail', 'broadcast'],
            array_values($notification->via($owner)),
        );
        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_posting_from_livewire_notifies_the_owner(): void
    {
        $owner = User::factory()->create();
        $author = User::factory()->create();
        $article = $this->articleOwnedBy($owner);

        $this->actingAs($author);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('newCommentState.body', 'From the form')
            ->call('postComment');

        Notification::assertSentTo($owner, CommentPostedNotification::class);
        Notification::assertNotSentTo($author, CommentPostedNotification::class);
    }

    public function test_posting_from_livewire_stays_quiet_when_notifications_are_off(): void
    {
        config(['commentify.enable_notifications' => false]);

        $owner = User::factory()->create();
        $author = User::factory()->create();
        $article = $this->articleOwnedBy($owner);

        $this->actingAs($author);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('newCommentState.body', 'From the form')
            ->call('postComment');

        Notification::assertNothingSent();
    }

    public function test_a_pending_livewire_comment_does_not_notify(): void
    {
        config(['commentify.require_approval' => true]);

        $owner = User::factory()->create();
        $author = User::factory()->create();
        $article = $this->articleOwnedBy($owner);

        $this->actingAs($author);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('newCommentState.body', 'Needs approval')
            ->call('postComment');

        Notification::assertNothingSent();
    }

    public function test_a_livewire_reply_notifies_the_parent_author(): void
    {
        $author = User::factory()->create();
        $replier = User::factory()->create();
        $article = ArticleStub::create(['slug' => 'livewire-reply']);
        $parent = $this->comment($article, $author, 'Parent');

        $this->actingAs($replier);

        Livewire::test(LivewireComment::class, ['comment' => $parent])
            ->set('replyState.body', 'A reply')
            ->call('postReply');

        Notification::assertSentTo($author, CommentPostedNotification::class);
        Notification::assertNotSentTo($replier, CommentPostedNotification::class);
    }

    public function test_a_guest_livewire_comment_notifies_the_owner_by_guest_name(): void
    {
        config(['commentify.allow_guests' => true]);

        $owner = User::factory()->create();
        $article = $this->articleOwnedBy($owner);

        Livewire::test(Comments::class, ['model' => $article])
            ->set('guest_name', 'Jane Guest')
            ->set('guest_email', 'jane@example.com')
            ->set('newCommentState.body', 'Hello from a guest')
            ->call('postComment');

        Notification::assertSentTo(
            $owner,
            CommentPostedNotification::class,
            function (CommentPostedNotification $notification) use ($owner): bool {
                $payload = $notification->toArray($owner);

                return $payload['user_id'] === null
                    && $payload['user_name'] === 'Jane Guest';
            },
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_pro_listener_replaces_the_core_one(): void
    {
        if (! class_exists(SendCommentNotifications::class)) {
            class_alias(CommentPostedNotification::class, SendCommentNotifications::class);
        }

        [, , $reply] = $this->replyBetweenTwoUsers();

        event(new CommentPosted($reply));

        Notification::assertNothingSent();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['commentify.enable_notifications' => true]);
        Notification::fake();
    }

    /**
     * @return array{0: User, 1: User, 2: Comment}
     */
    protected function replyBetweenTwoUsers(bool $enableNotifications = true): array
    {
        config(['commentify.enable_notifications' => $enableNotifications]);

        $author = User::factory()->create();
        $replier = User::factory()->create();
        $article = ArticleStub::create(['slug' => 'notes-'.uniqid()]);
        $parent = $this->comment($article, $author, 'Parent');
        $reply = $this->comment($article, $replier, 'Reply', $parent);

        return [$author, $replier, $reply];
    }

    protected function comment(Model $article, User $author, string $body, ?Comment $parent = null): Comment
    {
        return $article->comments()->create([
            'body' => $body,
            'user_id' => $author->id,
            'parent_id' => $parent?->id,
            'is_approved' => true,
        ]);
    }

    protected function articleOwnedBy(?User $owner): CommentableWithOwnerStub
    {
        $this->ensureOwnerColumn();

        return CommentableWithOwnerStub::query()->create([
            'slug' => 'owned-'.uniqid(),
            'user_id' => $owner?->id,
        ]);
    }

    protected function ensureOwnerColumn(): void
    {
        if (Schema::connection('testbench')->hasColumn('articles', 'user_id')) {
            return;
        }

        Schema::connection('testbench')->table('articles', function ($table): void {
            $table->unsignedInteger('user_id')->nullable();
        });
    }
}

class CommentableWithOwnerStub extends Model
{
    use Commentable;

    protected $connection = 'testbench';

    protected $table = 'articles';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class PlainOwnerStub extends Model
{
    protected $connection = 'testbench';

    protected $table = 'users';

    protected $guarded = [];
}

class CommentableWithPlainOwnerStub extends Model
{
    use Commentable;

    protected $connection = 'testbench';

    protected $table = 'articles';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(PlainOwnerStub::class, 'user_id');
    }
}
