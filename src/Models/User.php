<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as BaseUser;
use Usamamuneerchaudhary\Commentify\Database\Factories\UserFactory;
use Usamamuneerchaudhary\Commentify\Traits\HasUserAvatar;

class User extends BaseUser
{
    use HasFactory, HasUserAvatar;

    /**
     * @var string
     */
    protected $table = 'users';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
}
