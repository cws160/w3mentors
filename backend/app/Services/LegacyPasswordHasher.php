<?php

namespace App\Services;

class LegacyPasswordHasher
{
    public static function hash(string $password): string
    {
        $salt = config('auth.legacy_password_salt', '');

        return md5($salt . $password . $salt);
    }

    public static function check(string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return false;
        }

        return hash_equals($hash, self::hash($password));
    }
}
