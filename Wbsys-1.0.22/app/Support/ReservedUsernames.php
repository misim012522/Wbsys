<?php

namespace App\Support;

class ReservedUsernames
{
    /**
     * @return list<string>
     */
    public static function centralOnly(): array
    {
        return ['sysadmin'];
    }

    public static function isReservedForTenant(?string $username): bool
    {
        if (! is_string($username) || trim($username) === '') {
            return false;
        }

        return in_array(strtolower(trim($username)), self::centralOnly(), true);
    }

    public static function tenantMessage(): string
    {
        return 'The username sysadmin is reserved for the central account only.';
    }
}
