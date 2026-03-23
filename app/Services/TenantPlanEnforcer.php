<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Tenant;
use App\Models\User;

class TenantPlanEnforcer
{
    public function hasFeature(?Tenant $tenant, string $feature): bool
    {
        return $tenant?->hasFeature($feature) ?? false;
    }

    public function officeLimitReached(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $limit = $tenant->plan?->max_offices;

        if ($limit === null) {
            return false;
        }

        return Office::query()
            ->forTenant($tenant->id)
            ->count() >= $limit;
    }

    public function userLimitReached(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $limit = $tenant->plan?->max_users_per_tenant;

        if ($limit === null) {
            return false;
        }

        return User::query()
            ->forTenant($tenant->id)
            ->notArchived()
            ->count() >= $limit;
    }
}
