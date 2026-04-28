<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Services\TenantDatabaseManager;

trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        return 'tenant';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if ($tenant instanceof Tenant) {
            app(TenantDatabaseManager::class)->activate($tenant);
        }

        return $this->resolveRouteBindingQuery($this, $value, $field)->first();
    }
}
