<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Usamamuneerchaudhary\Commentify\Models\CommentReport;
use Illuminate\Database\Eloquent\SoftDeletes;
use Usamamuneerchaudhary\Commentify\Database\Factories\CommentFactory;
use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;
use Usamamuneerchaudhary\Commentify\Scopes\HasLikes;

class Comment extends Model
{

    use CommentScopes, SoftDeletes, HasFactory, HasLikes;

    /**
     * @var string
     */
    protected $table = 'comments';

    /**
     * @var string[]
     */
    protected $fillable = ['body'];

    protected $withCount = [
        'likes',
    ];

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

    /**
     * @return HasMany
     */
    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Check if the current user/IP has already reported this comment
     *
     * @return bool
     */
    public function isReportedByCurrentUser(): bool
    {
        $query = $this->reports();

        if (auth()->check()) {
            return $query->where('user_id', auth()->id())->exists();
        }

        $ip = request()->ip();
        $userAgent = request()->userAgent();

        if ($ip && $userAgent) {
            return $query->whereNull('user_id')
                ->where('ip', $ip)
                ->where('user_agent', $userAgent)
                ->exists();
        }

        return false;
    }

    /**
     * @return CommentFactory
     */
    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
