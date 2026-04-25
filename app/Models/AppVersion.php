<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use UsesCentralConnection;

    protected $fillable = ['version', 'release_notes', 'released_at', 'is_forced', 'download_url'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime', 'is_forced' => 'boolean'];
    }

    public function isNewerThan(?string $currentVersion): bool
    {
        $publishedVersion = self::normalizeVersion($this->version);
        $installedVersion = self::normalizeVersion($currentVersion);

        if (! $publishedVersion) {
            return false;
        }

        if (! $installedVersion) {
            return true;
        }

        return version_compare($publishedVersion, $installedVersion, '>');
    }

    public static function normalizeVersion(?string $version): ?string
    {
        if (! is_string($version)) {
            return null;
        }

        $normalized = trim($version);

        if ($normalized === '') {
            return null;
        }

        return preg_replace('/^[vV](?=\d)/', '', $normalized) ?: null;
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('released_at');
    }
}
