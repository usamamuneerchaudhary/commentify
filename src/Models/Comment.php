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
use Usamamuneerchaudhary\Commentify\Scopes\HasLikes;
use Usamamuneerchaudhary\Commentify\Support\GuestAvatar;
use Usamamuneerchaudhary\Commentify\Support\IntegerAuthId;

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
    protected $fillable = [
        'body',
        'is_approved',
        'pinned_at',
        'guest_name',
        'guest_email',
        'ip',
        'user_agent',
        'import_source',
        'import_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_approved' => 'boolean',
        'pinned_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        $model = config('commentify.user_model');

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new \RuntimeException('commentify.user_model must be an Eloquent model class.');
        }

        return $this->belongsTo($model);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Check if the current user/IP has already reported this comment
     */
    public function isReportedByCurrentUser(): bool
    {
        $query = $this->reports();

        $userId = IntegerAuthId::get();

        if ($userId !== null) {
            return $query->where('user_id', $userId)->exists();
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

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function authorName(): string
    {
        if ($this->isGuest()) {
            return (string) ($this->guest_name ?? 'Guest');
        }

        return (string) ($this->user?->name ?? 'Guest');
    }

    public function authorEmail(): ?string
    {
        if ($this->isGuest()) {
            return $this->guest_email;
        }

        return $this->user?->email;
    }

    public function authorAvatar(int $size = 80): string
    {
        if (! $this->isGuest() && $this->user !== null && method_exists($this->user, 'avatar')) {
            return (string) $this->user->avatar();
        }

        if (config('commentify.guest.show_gravatar', true) && filled($this->guest_email)) {
            return GuestAvatar::url($this->guest_email, $size);
        }

        return GuestAvatar::url(null, $size);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
