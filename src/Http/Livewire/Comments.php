<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Events\CommentPosting;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns\PostsGuestComments;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns\PreviewsMarkdown;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Support\CommentTree;

class Comments extends Component
{
    use AuthorizesRequests, PostsGuestComments, PreviewsMarkdown, WithPagination;

    public Model $model;

    public $users = [];

    public $showDropdown = false;

    public $sort = 'newest';

    public string $guest_name = '';

    public string $guest_email = '';

    public bool $subscribe_to_replies = false;

    public string $thread = 'default';

    public string $announcement = '';

    protected $numberOfPaginatorsRendered = [];

    public $newCommentState = [
        'body' => '',
    ];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected $validationAttributes = [
        'newCommentState.body' => 'comment',
    ];

    /**
     * Store the commentable model and the thread name for this component.
     */
    public function mount(Model $model, ?string $thread = null): void
    {
        $this->model = $model;
        $this->sort = config('commentify.default_sort', 'newest');
        $this->thread = ($thread !== null && $thread !== '') ? $thread : 'default';
    }

    /**
     * Thread name used for queries, falling back to default.
     */
    public function resolvedThread(): string
    {
        return $this->thread !== '' ? $this->thread : 'default';
    }

    /**
     * Whether commentify.read_only is on.
     */
    public function commentsAreReadOnly(): bool
    {
        return (bool) config('commentify.read_only', false);
    }

    /**
     * Read one commentify config value.
     */
    public function commentSetting(string $key, mixed $default = null): mixed
    {
        return config('commentify.'.$key, $default);
    }

    /**
     * Whether new comments stay hidden until a moderator approves them.
     */
    public function commentsRequireApproval(): bool
    {
        return (bool) config('commentify.require_approval', false);
    }

    /**
     * How many comments a visitor may post inside the rate-limit window.
     */
    protected function commentRateLimitMax(): int
    {
        return (int) config('commentify.rate_limit.max_attempts', 5);
    }

    /**
     * Validation rules merged into postComment. Pro adds its own here.
     *
     * @return array<string, mixed>
     */
    protected function extraPostingRules(): array
    {
        return [];
    }

    /**
     * Attributes copied from the composer onto a new comment. Only the body is included, so a rating is not mass-assigned.
     *
     * @return array<string, mixed>
     */
    protected function newCommentAttributes(): array
    {
        return [
            'body' => $this->newCommentState['body'] ?? '',
        ];
    }

    /**
     * Set the thread on a new comment when that column exists.
     */
    protected function prepareCommentForCreate(Comment $comment): void
    {
        if (Comment::hasThreadColumn()) {
            $comment->thread = $this->resolvedThread();
        }
    }

    /**
     * Return false to stop the save without a success flash. Pro uses this for its own checks.
     */
    protected function beforeCommentCreate(Comment $comment): bool
    {
        return true;
    }

    /**
     * Hook for ordering preloaded replies. The default leaves the query unchanged.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     */
    protected function orderChildren(Builder|Relation $query): void {}

    /**
     * Clear the body, guest fields, and mention dropdown after a successful post.
     */
    protected function resetComposer(): void
    {
        $this->newCommentState = [
            'body' => '',
        ];
        $this->resetGuestFields();
        $this->users = [];
        $this->showDropdown = false;
    }

    /**
     * Keep Livewire's paginator views aligned with commentify.css_framework.
     */
    public function paginationView(): string
    {
        return 'livewire::'.$this->commentifyPaginationTheme();
    }

    /**
     * Simple paginator view matching the configured CSS framework.
     */
    public function paginationSimpleView(): string
    {
        return 'livewire::simple-'.$this->commentifyPaginationTheme();
    }

    /**
     * Livewire pagination theme name for the configured CSS framework.
     */
    protected function commentifyPaginationTheme(): string
    {
        return config('commentify.css_framework') === 'bootstrap' ? 'bootstrap' : 'tailwind';
    }

    /**
     * Reset to the first page when the sort changes.
     */
    public function updatedSort(): void
    {
        $this->resetPage();
    }

    /**
     * Markup shown while a lazy comments component is loading.
     */
    public function placeholder(): View
    {
        return view('commentify::livewire.placeholder');
    }

    /**
     * Render the thread for the current sort and loader.
     */
    public function render(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null
    {
        $comments = (new CommentTree(
            $this->model,
            $this->resolvedThread(),
            function (mixed $query): void {
                $this->orderChildren($query);
            },
        ))->paginate((string) $this->sort);

        return view('commentify::livewire.comments', [
            'comments' => $comments,
        ]);
    }

    /**
     * Validate and store a new top-level comment, aborting when a listener cancels CommentPosting.
     */
    #[On('refresh')]
    public function postComment(): void
    {
        if ($this->commentsAreReadOnly()) {
            session()->flash('message', __('commentify::commentify.comments.read_only_message'));
            session()->flash('alertType', 'warning');

            return;
        }

        // Authorize using the CommentPolicy@create method
        $this->authorize('create', Comment::class);

        $rules = array_merge([
            'newCommentState.body' => 'required',
        ], $this->extraPostingRules());

        if ($this->guestCommentsAllowed()) {
            $rules = array_merge($rules, $this->guestCommentRules());
        }

        $this->validate($rules);

        $comment = $this->model->comments()->make($this->newCommentAttributes());
        $this->associateCommentAuthor($comment);
        $comment->is_approved = ! $this->commentsRequireApproval();
        $this->prepareCommentForCreate($comment);

        if (! $this->beforeCommentCreate($comment)) {
            return;
        }

        $posting = new CommentPosting($comment);
        event($posting);

        if ($posting->isCancelled()) {
            session()->flash('message', $posting->reason ?: __('commentify::commentify.comments.read_only_message'));
            session()->flash('alertType', 'warning');

            return;
        }

        if (! $comment->save()) {
            session()->flash('message', __('commentify::commentify.comments.read_only_message'));
            session()->flash('alertType', 'warning');

            return;
        }

        if (config('commentify.enable_notifications', false)) {
            event(new CommentPosted($comment));
        }

        if ($this->subscribe_to_replies && app()->bound('commentify.subscriptions')) {
            $email = auth()->check() ? auth()->user()->email : $comment->guest_email;
            $name = auth()->check() ? auth()->user()->name : $comment->guest_name;

            app('commentify.subscriptions')->subscribeIfRequested(
                $this->model,
                true,
                $email,
                $name,
                auth()->id()
            );
        }

        $this->resetComposer();

        $this->resetPage();
        session()->flash('message', 'Comment Posted Successfully!');
    }

    /**
     * Search users for the @mention dropdown.
     */
    public function getUsers(string $searchTerm): void
    {
        if (! empty($searchTerm)) {
            $this->users = config('commentify.user_model')::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
        } else {
            $this->users = [];
        }
    }

    /**
     * Insert the chosen mention into the composer.
     */
    public function selectUser(string $userName): void
    {
        if ($this->newCommentState['body']) {
            $this->newCommentState['body'] = preg_replace(
                '/@(\w+)$/',
                '@'.str_replace(' ', '_', Str::lower($userName)).' ',
                $this->newCommentState['body']
            );
            $this->users = [];
        }
    }
}
