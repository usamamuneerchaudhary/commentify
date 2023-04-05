<?php

namespace Usamamuneerchaudhary\Commentify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use Usamamuneerchaudhary\Commentify\Database\Factories\UserFactory;
use Usamamuneerchaudhary\Commentify\Traits\HasUserAvatar;

class User extends BaseUser
{
    use HasUserAvatar, HasFactory;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @return UserFactory
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
}
