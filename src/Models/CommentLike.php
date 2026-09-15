<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $comment_id
 * @property int|null $user_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CommentLike extends Model
{
    /**
     * @var string
     */
    protected $table = 'comment_likes';

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
    ];

    public function scopeForIp($query, string $ip): mixed
    {
        return $query->where('ip', $ip);
    }

    public function scopeForUserAgent($query, string $userAgent): mixed
    {
        return $query->where('user_agent', $userAgent);
    }
}
