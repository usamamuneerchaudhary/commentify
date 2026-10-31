<?php

namespace Usamamuneerchaudhary\Commentify\Models\Presenters;

use Illuminate\Support\HtmlString;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Support\UserProfileUrl;

class CommentPresenter
{
    public Comment $comment;

    /**
     * Wrap a comment so views can ask it for display values.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Render the comment body as sanitized HTML.
     */
    public function markdownBody(): HtmlString
    {
        return new HtmlString(app('markdown')->convertToHtml($this->comment->body));
    }

    /**
     * Render markdown to safe HTML and linkify known @mentions for the composer preview.
     */
    public static function preview(string $body): string
    {
        $html = (string) app('markdown')->convertToHtml($body);

        $presenter = new self(new Comment(['body' => $body]));

        return $presenter->replaceUserMentions($html);
    }

    /**
     * Human-readable time since the comment was created.
     */
    public function relativeCreatedAt(): mixed
    {
        return $this->comment->created_at->diffForHumans();
    }

    /**
     * Turn @mentions in the given text into profile links.
     */
    public function replaceUserMentions($text): array|string
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $matches);
        $usernames = $matches[1];
        $replacements = [];

        foreach ($usernames as $username) {
            $user = config('commentify.user_model')::where('name', $username)->first();

            if ($user && ($profileUrl = UserProfileUrl::for($user))) {
                $replacements['@'.$username] = '<a href="'.e($profileUrl).'">@'.$username.'</a>';
            } else {
                $replacements['@'.$username] = '@'.$username;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
