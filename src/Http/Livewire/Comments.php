<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Models\User;

class Comments extends Component
{
    use AuthorizesRequests, WithPagination;

    public Model $model;

    public $users = [];

    public $showDropdown = false;

    public $sort = 'newest';

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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application|null
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
        $this->authorize('create', \Usamamuneerchaudhary\Commentify\Models\Comment::class);

        $this->validate([
            'newCommentState.body' => 'required',
        ]);

        $comment = $this->model->comments()->make($this->newCommentState);
        $comment->user()->associate(auth()->user());

        // Set approval status based on config
        $comment->is_approved = ! config('commentify.require_approval', false);

        $comment->save();

        if (config('commentify.enable_notifications', false)) {
            event(new CommentPosted($comment));
        }

        $this->newCommentState = [
            'body' => '',
        ];
        $this->users = [];
        $this->showDropdown = false;

        $this->resetPage();
        session()->flash('message', 'Comment Posted Successfully!');
    }

    public function getUsers(string $searchTerm): void
    {
        if (! empty($searchTerm)) {
            $this->users = User::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
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
