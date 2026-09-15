<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $comment_id
 * @property int|null $user_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string $reason
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Comment|null $comment
 */
class CommentReport extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'comment_reports';

    /**
     * @var string[]
     */
    protected $fillable = [
        'comment_id',
        'user_id',
        'ip',
        'user_agent',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        $model = config('commentify.user_model');

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new \RuntimeException('commentify.user_model must be an Eloquent model class.');
        }

        return $this->belongsTo($model);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function reviewer(): BelongsTo
    {
        $model = config('commentify.user_model');

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new \RuntimeException('commentify.user_model must be an Eloquent model class.');
        }

        return $this->belongsTo($model, 'reviewed_by');
    }
}
