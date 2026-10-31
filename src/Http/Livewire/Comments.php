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

    public function mount(Model $model, ?string $thread = null): void
    {
        $this->model = $model;
        $this->sort = config('commentify.default_sort', 'newest');
        $this->thread = ($thread !== null && $thread !== '') ? $thread : 'default';
    }

    public function resolvedThread(): string
    {
        return $this->thread !== '' ? $this->thread : 'default';
    }

    public function commentsAreReadOnly(): bool
    {
        return (bool) config('commentify.read_only', false);
    }

    public function commentSetting(string $key, mixed $default = null): mixed
    {
        return config('commentify.'.$key, $default);
    }

    public function commentsRequireApproval(): bool
    {
        return (bool) config('commentify.require_approval', false);
    }

    protected function commentRateLimitMax(): int
    {
        return (int) config('commentify.rate_limit.max_attempts', 5);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraPostingRules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function newCommentAttributes(): array
    {
        return [
            'body' => $this->newCommentState['body'] ?? '',
        ];
    }

    protected function prepareCommentForCreate(Comment $comment): void
    {
        if (Comment::hasThreadColumn()) {
            $comment->thread = $this->resolvedThread();
        }
    }

    protected function beforeCommentCreate(Comment $comment): bool
    {
        return true;
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     */
    protected function orderChildren(Builder|Relation $query): void {}

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

    public function paginationSimpleView(): string
    {
        return 'livewire::simple-'.$this->commentifyPaginationTheme();
    }

    protected function commentifyPaginationTheme(): string
    {
        return config('commentify.css_framework') === 'bootstrap' ? 'bootstrap' : 'tailwind';
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function placeholder(): View
    {
        return view('commentify::livewire.placeholder');
    }

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

    public function getUsers(string $searchTerm): void
    {
        if (! empty($searchTerm)) {
            $this->users = config('commentify.user_model')::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
        } else {
            $this->users = [];
        }
    }

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
