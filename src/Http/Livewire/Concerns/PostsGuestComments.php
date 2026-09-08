<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns;

use Usamamuneerchaudhary\Commentify\Models\Comment;

trait PostsGuestComments
{
    protected function guestCommentsAllowed(): bool
    {
        return config('commentify.allow_guests', false) && auth()->guest();
    }

    /**
     * @return array<string, mixed>
     */
    protected function guestCommentRules(): array
    {
        $rules = [
            'guest_name' => ['required', 'string', 'max:100'],
        ];

        if (config('commentify.guest.require_email', true)) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
        } else {
            $rules['guest_email'] = ['nullable', 'email', 'max:255'];
        }

        return $rules;
    }

    protected function applyGuestAttributes(Comment $comment): void
    {
        if (! $this->guestCommentsAllowed()) {
            return;
        }

        $comment->guest_name = $this->guest_name ?? null;
        $comment->guest_email = $this->guest_email ?? null;
        $comment->ip = request()->ip();
        $comment->user_agent = request()->userAgent();
    }

    protected function associateCommentAuthor(Comment $comment): void
    {
        if (auth()->check()) {
            $comment->user()->associate(auth()->user());

            return;
        }

        $this->applyGuestAttributes($comment);
    }

    protected function resetGuestFields(): void
    {
        if (property_exists($this, 'guest_name')) {
            $this->guest_name = '';
        }

        if (property_exists($this, 'guest_email')) {
            $this->guest_email = '';
        }
    }
}
