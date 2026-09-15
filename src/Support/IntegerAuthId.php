<?php

namespace Usamamuneerchaudhary\Commentify\Support;

/**
 * Commentify stores `user_id` as an integer.
 */
final class IntegerAuthId
{
    public static function get(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        $id = auth()->id();

        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        return null;
    }
}
