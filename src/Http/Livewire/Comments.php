<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;


use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Comments extends Component
{
    use WithPagination;

    public $model;
    public $users = [];
    public $showDropdown = false;

    public $newCommentState = [
        'body' => ''
    ];

    protected $listeners = [
        'refresh' => '$refresh'
    ];

    protected $validationAttributes = [
        'newCommentState.body' => 'comment'
    ];

    public function render()
    {
        $comments = $this->model
            ->comments()
            ->with('user', 'children.user', 'children.children')
            ->parent()
            ->latest()
            ->paginate(3);
        return view('commentify::livewire.comments', [
            'comments' => $comments
        ]);
    }

    public function postComment()
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

    public function updatedNewCommentStateBody($value)
    {
        if (preg_match('/@(\w+)$/', $value, $matches)) {
            $term = $matches[1];

            $this->users = User::where('name', 'like', '%'.$term.'%')->get();
            $this->showDropdown = true;
        } else {
            $this->users = [];
            $this->showDropdown = false;
        }
    }


    public function selectUser($username)
    {
        $this->comment = preg_replace('/@(\w+)$/', '@'.str_replace(' ', '_', $username).' ', $this->comment);
        $this->users = [];
        $this->showDropdown = false;
    }
}
