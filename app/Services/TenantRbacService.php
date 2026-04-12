<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class TenantRbacService
{
    public function tenantAdminDefinitions(): array
    {
        return User::tenantAdminPermissionDefinitions();
    }

    public function officeStaffDefinitions(): array
    {
        return User::officeStaffPermissionDefinitions();
    }

    public function tenantAdminStates(?Tenant $tenant): array
    {
        return User::tenantAdminPermissionStates($tenant);
    }

    public function officeStaffStates(?Tenant $tenant): array
    {
        return User::officeStaffPermissionStates($tenant);
    }

    public function viewData(?Tenant $tenant): array
    {
        return [
            'tenant' => $tenant,
            'tenantAdminPermissionDefinitions' => $this->tenantAdminDefinitions(),
            'tenantAdminPermissions' => $this->tenantAdminStates($tenant),
            'permissionDefinitions' => $this->officeStaffDefinitions(),
            'officeStaffPermissions' => $this->officeStaffStates($tenant),
        ];
    }

    public function updateFromRequest(Tenant $tenant, array $payload): void
    {
        foreach ($this->tenantAdminDefinitions() as $definition) {
            if (($definition['setting'] ?? null) === null || ($definition['input'] ?? null) === null) {
                continue;
            }

            $tenant->setSetting($definition['setting'], (bool) ($payload[$definition['input']] ?? false));
        }

        foreach ($this->officeStaffDefinitions() as $definition) {
            $tenant->setSetting($definition['setting'], (bool) ($payload[$definition['input']] ?? false));
        }
    }
}
