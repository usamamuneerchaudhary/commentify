<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Usamamuneerchaudhary\Commentify\Events\CommentLiked;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class Like extends Component
{
    public $comment;

    public $count;

    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
        $this->count = $comment->likes_count;
    }

    public function like(): void
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        if ($this->comment->isLiked()) {
            $this->comment->removeLike();

            $this->count--;
        } elseif (auth()->user()) {
            $this->comment->likes()->create([
                'user_id' => auth()->id(),
            ]);

            $this->count++;

            if (config('commentify.enable_notifications', false)) {
                event(new CommentLiked($this->comment, auth()->id()));
            }
        } elseif ($ip && $userAgent) {
            $this->comment->likes()->create([
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            $this->count++;
        }
    }

    public function render(
    ): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|null {
        return view('commentify::livewire.like');
    }
}
