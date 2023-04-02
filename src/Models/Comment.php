<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;

class Comment extends Model
{

    use CommentScopes, SoftDeletes;

    protected $table = 'comments';
    protected $fillable = ['body'];


    public function presenter()
    {
        return new CommentPresenter($this);
    }

    public function isParent()
    {
        return is_null($this->parent_id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}
