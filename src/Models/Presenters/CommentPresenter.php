<?php

namespace Usamamuneerchaudhary\Commentify\Models\Presenters;

use Illuminate\Support\HtmlString;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentPresenter
{
    /**
     * @var Comment
     */
    public Comment $comment;

    /**
     * @param  Comment  $comment
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return HtmlString
     */
    public function markdownBody(): HtmlString
    {
        return new HtmlString(app('markdown')->convertToHtml($this->comment->body));
    }

    /**
     * @return mixed
     */
    public function relativeCreatedAt(): mixed
    {
        return $this->comment->created_at->diffForHumans();
    }

    /**
     * @param $text
     * @return array|string
     */
    public function replaceUserMentions($text): array|string
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $matches);
        $usernames = $matches[1];
        $replacements = [];

        foreach ($usernames as $username) {
            $user = User::where('name', $username)->first();

            if ($user) {
                $userRoutePrefix = config('commentify.users_route_prefix', 'users');

                $replacements['@'.$username] = '<a href="/'.$userRoutePrefix.'/'.$username.'">@'.$username.
                    '</a>';
            } else {
                $replacements['@'.$username] = '@'.$username;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }


}
