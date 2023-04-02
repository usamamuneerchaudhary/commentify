<?php

namespace Usamamuneerchaudhary\Commentify\Models\Presenters;

use Illuminate\Support\HtmlString;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPresenter
{
    public Comment $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function markdownBody()
    {
        return new HtmlString(app('markdown')->convertToHtml($this->comment->body));
    }

    public function relativeCreatedAt()
    {
        return $this->comment->created_at->diffForHumans();
    }

    public function replaceUserMentions($text)
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $text, $matches);
        $usernames = $matches[1];
        $replacements = [];

        foreach ($usernames as $username) {
            $user = \App\Models\User::where('name', $username)->first();

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
