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
        return ! $currentVersion || version_compare($this->version, $currentVersion, '>');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('released_at');
    }
}
