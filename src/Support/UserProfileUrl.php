<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class UserProfileUrl
{
    /**
     * Build a profile URL for a user.
     *
     * Prefers a named route (`user_profile_route`) when configured; otherwise
     * builds `/{users_route_prefix}/{users_route_key}` (default key: name).
     */
    public static function for(Model $user): ?string
    {
        $routeName = config('commentify.user_profile_route');

        if (is_string($routeName) && $routeName !== '') {
            try {
                return route($routeName, $user);
            } catch (RouteNotFoundException) {
                // Fall through to the prefix-based URL.
            }
        }

        $prefix = config('commentify.users_route_prefix', 'users');

        if (! is_string($prefix) || $prefix === '') {
            return null;
        }

        $segment = self::segment($user);

        if ($segment === null || $segment === '') {
            return null;
        }

        return '/'.trim($prefix, '/').'/'.$segment;
    }

    protected static function segment(Model $user): mixed
    {
        $key = config('commentify.users_route_key', 'name');

        if (! is_string($key) || $key === '') {
            $key = 'name';
        }

        if ($key === 'id') {
            return $user->getKey();
        }

        return $user->getAttribute($key);
    }
}
