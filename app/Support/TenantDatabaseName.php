<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantDatabaseName
{
    private const SQLITE_SUFFIX = '_buksu_queueless.db';

    public static function generate(string $source, ?callable $exists = null): string
    {
        $base = Str::slug($source, '_');
        $base = trim($base, '_') !== '' ? trim($base, '_') : 'tenant';

        $maxBaseLength = max(1, 64 - strlen(self::SQLITE_SUFFIX));
        $base = substr($base, 0, $maxBaseLength);
        $candidate = $base.self::SQLITE_SUFFIX;
        $counter = 2;

        while ($exists && $exists($candidate)) {
            $suffix = '_'.$counter;
            $trimmedBase = substr($base, 0, max(1, 64 - strlen(self::SQLITE_SUFFIX) - strlen($suffix)));
            $candidate = $trimmedBase.$suffix.self::SQLITE_SUFFIX;
            $counter++;
        }

        return $candidate;
    }

    public static function normalize(string $value, ?string $fallbackSource = null): string
    {
        $trimmed = trim($value);

        if ($trimmed === '' && $fallbackSource !== null) {
            return self::generate($fallbackSource);
        }

        if ($trimmed === '') {
            return self::generate('tenant');
        }

        if (str_ends_with($trimmed, '.sqlite')) {
            $trimmed = substr($trimmed, 0, -7).'.db';
        }

        if (str_ends_with($trimmed, '.db')) {
            $base = substr($trimmed, 0, -3);

            return self::generate(str_replace('_buksu_queueless', '', $base));
        }

        return self::generate($trimmed);
    }

    public static function sqliteFilename(Tenant $tenant): string
    {
        return str_ends_with((string) $tenant->database_name, '.db')
            ? (string) $tenant->database_name
            : self::normalize((string) $tenant->database_name, $tenant->name);
    }

    public static function mysqlSchemaName(Tenant $tenant): string
    {
        $filename = self::sqliteFilename($tenant);

        return str_replace('.', '_', substr($filename, 0, -3));
    }
}
