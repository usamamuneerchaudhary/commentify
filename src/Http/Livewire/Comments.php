<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;


use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Livewire\WithPagination;

class Comments extends Component
{
    use WithPagination;

    /**
     * @var
     */
    public $model;

    /**
     * @var array
     */
    public $users = [];

    /**
     * @var bool
     */
    public $showDropdown = false;

    /**
     * @var array|string[]
     */
    public $newCommentState = [
        'body' => ''
    ];


    /**
     * @var string[]
     */
    protected $listeners = [
        'refresh' => '$refresh'
    ];

    /**
     * @var array|string[]
     */
    protected $validationAttributes = [
        'newCommentState.body' => 'comment'
    ];

    /**
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null
     */
    public function render(
    ): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application|null
    {
        $comments = $this->model
            ->comments()
            ->with('user', 'children.user', 'children.children')
            ->parent()
            ->latest()
            ->paginate(10);
        return view('commentify::livewire.comments', [
            'comments' => $comments
        ]);
    }

    /**
     * @return void
     */
    public function postComment(): void
    {
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
    }

}
