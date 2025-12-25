<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Usamamuneerchaudhary\Commentify\Database\Factories\CommentFactory;
use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;
use Usamamuneerchaudhary\Commentify\Scopes\CommentScopes;
use Usamamuneerchaudhary\Commentify\Scopes\HasLikes;

class Comment extends Model
{
    use CommentScopes, HasFactory, HasLikes, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'comments';

    /**
     * @var string[]
     */
    protected $fillable = ['body', 'is_approved'];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    protected $withCount = [
        'likes',
    ];

    public function presenter(): CommentPresenter
    {
        return new CommentPresenter($this);
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    public function commentable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Check if the current user/IP has already reported this comment
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
     * Check if the comment is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    /**
     * Check if the comment is pending approval
     */
    public function isPending(): bool
    {
        return $this->is_approved === false;
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
