<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Usamamuneerchaudhary\Commentify\Events\CommentLiked;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Support\IntegerAuthId;

class Like extends Component
{
    public $comment;

    public $count;

    /**
     * Store the comment and its current like count.
     */
    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
        $this->count = $comment->likes_count;
    }

    /**
     * Toggle the current viewer like and broadcast CommentLiked when a signed-in user likes it.
     */
    public function like(): void
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        if ($this->comment->isLiked()) {
            $this->comment->removeLike();

            $this->count--;
        } elseif (($userId = IntegerAuthId::get()) !== null) {
            $this->comment->likes()->create([
                'user_id' => $userId,
            ]);

            $this->count++;

            if (config('commentify.enable_notifications', false)) {
                event(new CommentLiked($this->comment, $userId));
            }
        } elseif ($ip && $userAgent) {
            $this->comment->likes()->create([
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            $this->count++;
        }
    }

    /**
     * Render the like button.
     */
    public function render(
    ): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null {
        return view('commentify::livewire.like');
    }
}
