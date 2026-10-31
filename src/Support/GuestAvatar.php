<?php

namespace Usamamuneerchaudhary\Commentify\Support;

final class GuestAvatar
{
    /**
     * Gravatar URL for a guest email, or a default image when the email is empty.
     */
    public static function url(?string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim((string) $email)));

        return "https://gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }
}
