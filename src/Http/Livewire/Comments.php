<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns\PostsGuestComments;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns\PreviewsMarkdown;
use Usamamuneerchaudhary\Commentify\Models\Comment;

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

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->sort = config('commentify.default_sort', 'newest');
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null
    {
        $requireApproval = config('commentify.require_approval', false);

        $query = $this->model
            ->comments()
            ->with([
                'user',
                'likes',
                'children' => function ($query) use ($requireApproval) {
                    $query->with([
                        'user',
                        'likes',
                        'children' => function ($nestedQuery) use ($requireApproval) {
                            $nestedQuery->with('user', 'likes');
                            if ($requireApproval) {
                                $nestedQuery->approved();
                            }
                        },
                    ]);
                    if ($requireApproval) {
                        $query->approved();
                    }
                },
            ])
            ->parent()
            ->pinnedFirst()
            ->withCount('children');

        // Filter by approval status if moderation is enabled
        if ($requireApproval) {
            $query->approved();
        }

        if (config('commentify.enable_sorting', true)) {
            $query = match ($this->sort) {
                'oldest' => $query->oldest(),
                'most_liked' => $query->mostLiked(),
                'most_replied' => $query->mostReplied(),
                default => $query->newest(),
            };
        } else {
            $query = $query->newest();
        }

        $comments = $query->paginate(config('commentify.pagination_count', 10));

        return view('commentify::livewire.comments', [
            'comments' => $comments,
        ]);
    }

    #[On('refresh')]
    public function postComment(): void
    {
        if (config('commentify.read_only')) {
            session()->flash('message', __('commentify::commentify.comments.read_only_message'));
            session()->flash('alertType', 'warning');

            return;
        }

        // Authorize using the CommentPolicy@create method
        $this->authorize('create', Comment::class);

        $rules = [
            'newCommentState.body' => 'required',
        ];

        if ($this->guestCommentsAllowed()) {
            $rules = array_merge($rules, $this->guestCommentRules());
        }

        $this->validate($rules);

        $comment = $this->model->comments()->make($this->newCommentState);
        $this->associateCommentAuthor($comment);

        // Set approval status based on config
        $comment->is_approved = ! config('commentify.require_approval', false);

        $comment->save();

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

        $this->newCommentState = [
            'body' => '',
        ];
        $this->resetGuestFields();
        $this->users = [];
        $this->showDropdown = false;

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
