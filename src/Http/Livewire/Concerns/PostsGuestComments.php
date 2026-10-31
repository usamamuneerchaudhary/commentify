<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns;

use Usamamuneerchaudhary\Commentify\Models\Comment;

trait PostsGuestComments
{
    /**
     * Whether the current visitor is a guest and guest comments are enabled.
     */
    protected function guestCommentsAllowed(): bool
    {
        return config('commentify.allow_guests', false) && auth()->guest();
    }

    /**
     * Validation rules for the guest name and email fields.
     *
     * @return array<string, mixed>
     */
    protected function guestCommentRules(): array
    {
        $rules = [
            'guest_name' => ['required', 'string', 'max:100'],
        ];

        if (config('commentify.guest.require_email', true)) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
        } elseif (filled($this->guest_email)) {
            $rules['guest_email'] = ['email', 'max:255'];
        }

        return $rules;
    }

    /**
     * Copy the guest name, email, IP, and user agent onto a comment before it is saved.
     */
    protected function applyGuestAttributes(Comment $comment): void
    {
        if (! $this->guestCommentsAllowed()) {
            return;
        }

        $comment->guest_name = $this->guest_name ?? null;
        $comment->guest_email = filled($this->guest_email) ? $this->guest_email : null;
        $comment->ip = request()->ip();
        $comment->user_agent = request()->userAgent();
    }

    /**
     * Attach the signed-in user, or the guest fields when nobody is signed in.
     */
    protected function associateCommentAuthor(Comment $comment): void
    {
        if (auth()->check()) {
            $comment->user()->associate(auth()->user());

            return;
        }

        $this->applyGuestAttributes($comment);
    }

    /**
     * Clear the guest name and email after a successful post.
     */
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
