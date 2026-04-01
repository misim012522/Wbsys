<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['tenant_id', 'name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function isProtected(): bool
    {
        return $this->tenant_id === null || in_array($this->slug, [
            User::ROLE_TENANT_ADMIN,
            User::ROLE_OFFICE_STAFF,
            User::ROLE_STUDENT,
        ], true);
    }

    public function assignedUsersCount(): int
    {
        return User::query()->where('role', $this->slug)->count();
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId));
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
