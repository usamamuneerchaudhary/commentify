<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Usamamuneerchaudhary\Commentify\Events\CommentReported;
use Usamamuneerchaudhary\Commentify\Models\CommentReport;
use Usamamuneerchaudhary\Commentify\Models\User;

class Comment extends Component
{
    use AuthorizesRequests;

    public $comment;

    public $users = [];

    public $isReplying = false;

    public $hasReplies = false;

    public $showOptions = false;

    public $isEditing = false;

    public $isReporting = false;

    public $alreadyReported = false;

    public $reportState = [
        'reason' => '',
        'additional_details' => '',
    ];

    public $replyState = [
        'body' => '',
    ];

    public $editState = [
        'body' => '',
    ];

    protected $validationAttributes = [
        'replyState.body' => 'Reply',
        'editState.body' => 'Reply',
        'reportState.reason' => 'reason',
    ];

    public function updatedIsEditing($isEditing): void
    {
        if (! $isEditing) {
            return;
        }
        $this->editState = [
            'body' => $this->comment->body,
        ];
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function editComment(): void
    {
        $this->authorize('update', $this->comment);
        $this->validate([
            'editState.body' => 'required|min:2',
        ]);
        $this->comment->update($this->editState);
        $this->isEditing = false;
        $this->showOptions = false;
    }

    /**
     * @throws AuthorizationException
     */
    #[On('refresh')]
    public function deleteComment(): void
    {
        $this->authorize('destroy', $this->comment);
        $this->comment->delete();
        $this->showOptions = false;
        $this->dispatch('refresh');
    }

    /**
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null
     */
    public function showReportForm(): void
    {
        if ($this->comment->isReportedByCurrentUser()) {
            $this->alreadyReported = true;
            $this->isReporting = true;
        } else {
            $this->alreadyReported = false;
            $this->isReporting = true;
        }
        $this->showOptions = false;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application|null
    {
        return view('commentify::livewire.comment');
    }

    #[On('refresh')]
    public function postReply(): void
    {
        if (config('commentify.read_only')) {
            session()->flash('message', __('commentify::commentify.comments.read_only_message'));
            session()->flash('alertType', 'warning');

            return;
        }

        $this->authorize('create', \Usamamuneerchaudhary\Commentify\Models\Comment::class);

        if (! $this->comment->isParent()) {
            return;
        }
        $this->validate([
            'replyState.body' => 'required',
        ]);
        $reply = $this->comment->children()->make($this->replyState);
        $reply->user()->associate(auth()->user());
        $reply->commentable()->associate($this->comment->commentable);

        // Set approval status based on config
        $reply->is_approved = ! config('commentify.require_approval', false);

        $reply->save();

        $this->replyState = [
            'body' => '',
        ];
        $this->isReplying = false;
        $this->showOptions = false;
        $this->dispatch('refresh')->self();
    }

    public function selectUser($userName): void
    {
        if ($this->replyState['body']) {
            $this->replyState['body'] = preg_replace('/@(\w+)$/', '@'.str_replace(' ', '_', Str::lower($userName)).' ',
                $this->replyState['body']);
            //            $this->replyState['body'] =$userName;
            $this->users = [];
        } elseif ($this->editState['body']) {
            $this->editState['body'] = preg_replace('/@(\w+)$/', '@'.str_replace(' ', '_', Str::lower($userName)).' ',
                $this->editState['body']);
            $this->users = [];
        }
    }

    #[On('getUsers')]
    public function getUsers($searchTerm): void
    {
        if (! empty($searchTerm)) {
            $this->users = User::where('name', 'like', '%'.$searchTerm.'%')->take(5)->get();
        } else {
            $this->users = [];
        }
    }

    public function reportComment(): void
    {
        if (! config('commentify.enable_reporting', true)) {
            return;
        }

        // Check if user has already reported this comment
        if ($this->comment->isReportedByCurrentUser()) {
            session()->flash('message', __('commentify::commentify.comments.already_reported'));
            session()->flash('alertType', 'warning');
            $this->isReporting = false;
            $this->showOptions = false;

            return;
        }

        $reportReasons = config('commentify.report_reasons', ['spam', 'inappropriate', 'offensive', 'other']);

        $this->validate([
            'reportState.reason' => 'required|in:'.implode(',', $reportReasons),
            'reportState.additional_details' => 'nullable|max:500',
        ]);

        $reason = $this->reportState['reason'];
        if (! empty($this->reportState['additional_details'])) {
            $reason .= ': '.$this->reportState['additional_details'];
        }

        $report = CommentReport::create([
            'comment_id' => $this->comment->id,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'status' => 'pending',
        ]);

        if (config('commentify.enable_notifications', false)) {
            event(new CommentReported($this->comment, $report));
        }

        $this->reportState = ['reason' => '', 'additional_details' => ''];
        $this->isReporting = false;
        $this->alreadyReported = false;
        $this->showOptions = false;

        session()->flash('message', __('commentify::commentify.comments.report_submitted'));
        session()->flash('alertType', 'success');
    }

    public function closeReportForm(): void
    {
        $this->isReporting = false;
        $this->alreadyReported = false;
    }
}
