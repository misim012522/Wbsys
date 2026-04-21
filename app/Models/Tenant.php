<?php

namespace App\Models;

use App\Models\Concerns\UsesCentralConnection;
use App\Support\TenantDatabaseName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use UsesCentralConnection;

    protected $fillable = [
        'name', 'slug', 'plan_id', 'domain', 'subdomain',
        'database_name',
        'address', 'email', 'contact_name', 'contact_number',
        'settings', 'support_url', 'app_version', 'is_active', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean', 'approved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant): void {
            if ($tenant->database_name || $tenant->name) {
                $tenant->database_name = TenantDatabaseName::normalize(
                    (string) $tenant->database_name,
                    $tenant->name
                );
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules');
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public function getPrimaryColorAttribute(): string
    {
        return $this->getSetting('theme.primary_color', '#2563eb');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getSetting('theme.logo_url');
    }

    public function hasFeature(string $feature): bool
    {
        if ($this->plan && $this->plan->hasFeature($feature)) {
            return true;
        }

        return in_array($feature, $this->getSetting('feature_flags', []));
    }

    public function storagePath(string $path = ''): string
    {
        return 'tenants/'.$this->id.($path ? '/'.ltrim($path, '/') : '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain)->orWhere('subdomain', $domain);
    }

    public static function normalizeHost(?string $host): string
    {
        $host = preg_replace('/:\d+$/', '', (string) $host);

        return strtolower(trim((string) $host));
    }

    public static function resolveFromHost(?string $host, bool $includeInactive = false): ?self
    {
        $normalizedHost = self::normalizeHost($host);

        if ($normalizedHost === '') {
            return null;
        }

        $query = $includeInactive ? self::query() : self::active();
        $tenant = $query->where('domain', $normalizedHost)->first();

        if ($tenant || count(explode('.', $normalizedHost)) < 2) {
            return $tenant;
        }

        $subdomain = explode('.', $normalizedHost)[0] ?? '';

        if ($subdomain === '') {
            return null;
        }

        $query = $includeInactive ? self::query() : self::active();

        return $query->where('subdomain', $subdomain)->first();
    }
}
