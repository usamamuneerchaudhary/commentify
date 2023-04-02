<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;


use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

class Comment extends Component
{
    use AuthorizesRequests;

    /**
     * @var
     */
    public $comment;

    /**
     * @var array
     */
    public $users = [];

    /**
     * @var bool
     */
    public $isReplying = false;

    /**
     * @var bool
     */
    public $isEditing = false;

    /**
     * @var bool
     */
    public $isMentioning = false;

    /**
     * @var string[]
     */
    public $replyState = [
        'body' => '',
        'search' => ''
    ];

    /**
     * @var string[]
     */
    public $editState = [
        'body' => ''
    ];

    /**
     * @var string[]
     */
    protected $validationAttributes = [
        'replyState.body' => 'Reply'
    ];

    /**
     * @var string[]
     */
    protected $listeners = [
        'refresh' => '$refresh',
        'getUsers'
    ];

    /**
     * @param $isEditing
     * @return void
     */
    public function updatedIsEditing($isEditing): void
    {
        if (!$isEditing) {
            return;
        }
        $this->editState = [
            'body' => $this->comment->body
        ];
    }

    /**
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function editComment(): void
    {
        $this->authorize('update', $this->comment);
        $this->comment->update($this->editState);
        $this->isEditing = false;
    }

    public function deleteComment()
    {
        $this->authorize('destroy', $this->comment);
        $this->comment->delete();
        $this->emitUp('refresh');
    }

    public function render()
    {
        return view('commentify::livewire.comment');
    }

    public function postReply()
    {
        if (!$this->comment->isParent()) {
            return;
        }
        $this->validate([
            'replyState.body' => 'required'
        ]);
        $reply = $this->comment->children()->make($this->replyState);
        $reply->user()->associate(auth()->user());
        $reply->commentable()->associate($this->comment->commentable);
        $reply->save();

        $this->replyState = [
            'body' => ''
        ];
        $this->isReplying = false;
        $this->emitSelf('refresh');
    }

    public function updatedReplyState()
    {
        $this->isMentioning = strpos($this->comment->body, '@') !== false;

        if ($this->isMentioning) {
            $searchTerm = str_replace('@', '', $this->comment->body);
            $this->users = User::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
        } else {
            $this->users = [];
        }
    }

    public function selectUser($userName)
    {
        $this->replyState['body'] = preg_replace('/@(\w+)$/', '@'.str_replace(' ', '_', Str::lower($userName)).' ',
            $this->replyState['body']);
        $this->users = [];
    }

    public function getUsers($searchTerm)
    {
        if (!empty($searchTerm)) {
            $this->users = User::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
        } else {
            $this->users = [];
        }
    }

}
