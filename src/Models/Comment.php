<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Usamamuneerchaudhary\Commentify\Database\Factories\CommentFactory;
use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;

class Comment extends Model
{

    use CommentScopes, SoftDeletes, HasFactory;

    /**
     * @var string
     */
    protected $table = 'comments';

    /**
     * @var string[]
     */
    protected $fillable = ['body'];


    /**
     * @return CommentPresenter
     */
    public function presenter(): CommentPresenter
    {
        return new CommentPresenter($this);
    }

    /**
     * @return bool
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * @return BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    /**
     * @return MorphTo
     */
    public function commentable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory()
    {
        return CommentFactory::new();
    }
}
