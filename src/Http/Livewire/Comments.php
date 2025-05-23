<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Model;

class Comments extends Component
{
    use WithPagination, AuthorizesRequests;

    public Model $model;

    public $users = [];

    public $showDropdown = false;

    protected $numberOfPaginatorsRendered = [];

    public $newCommentState = [
        'body' => ''
    ];

    protected $listeners = [
        'refresh' => '$refresh'
    ];

    protected $validationAttributes = [
        'newCommentState.body' => 'comment'
    ];

    public function mount(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null
     */
    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application|null
    {
        $comments = $this->model
            ->comments()
            ->with('user', 'children.user', 'children.children')
            ->parent()
            ->latest()
            ->paginate(config('commentify.pagination_count', 10));
        return view('commentify::livewire.comments', [
            'comments' => $comments
        ]);
    }

    /**
     * @return void
     */
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
            'newCommentState.body' => 'required'
        ]);

        $comment = $this->model->comments()->make($this->newCommentState);
        $comment->user()->associate(auth()->user());
        $comment->save();

        $this->newCommentState = [
            'body' => ''
        ];
        $this->users = [];
        $this->showDropdown = false;

        $this->resetPage();
        session()->flash('message', 'Comment Posted Successfully!');
    }
}
