<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginAccountResolver
{
    public function __construct(
        private TenantDatabaseManager $tenantDatabaseManager
    ) {}

    /**
     * @return array{status: 'matched'|'invalid'|'ambiguous', user?: User, tenant?: ?Tenant}
     */
    public function resolve(string $login, string $password): array
    {
        $matches = collect();
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        $centralUser = User::on($this->centralConnectionName())
            ->where(function ($query) use ($login) {
                $query->where('email', $login)->orWhere('username', $login);
            })
            ->whereNull('tenant_id')
            ->first();

        if ($centralUser && Hash::check($password, $centralUser->password)) {
            $centralUser->setConnection('central');

            $matches->push([
                'user' => $centralUser,
                'tenant' => null,
            ]);
        }

        $tenants = $currentTenant instanceof Tenant
            ? collect([$currentTenant])
            : Tenant::active()->orderBy('id')->get();

        foreach ($tenants as $tenant) {
            try {
                $this->tenantDatabaseManager->activate($tenant);

                $tenantUser = User::on('tenant')
                    ->where(function ($query) use ($login) {
                        $query->where('email', $login)->orWhere('username', $login);
                    })
                    ->first();
            } catch (\Throwable) {
                continue;
            }

            if (! $tenantUser || ! Hash::check($password, $tenantUser->password)) {
                continue;
            }

            $tenantUser->setConnection('tenant');

            $matches->push([
                'user' => $tenantUser,
                'tenant' => $tenant,
            ]);
        }

        if ($matches->count() > 1) {
            return ['status' => 'ambiguous'];
        }

        if ($matches->isEmpty()) {
            return ['status' => 'invalid'];
        }

        /** @var array{user: User, tenant: ?Tenant} $match */
        $match = $matches->first();

        return [
            'status' => 'matched',
            'user' => $match['user'],
            'tenant' => $match['tenant'],
        ];
    }

    private function centralConnectionName(): string
    {
        return app()->environment('testing')
            ? (string) config('database.default', 'sqlite')
            : 'central';
    }
}
