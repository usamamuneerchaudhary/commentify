<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('commentify.user_model'));
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(config('commentify.user_model'), 'reviewed_by');
    }
}
