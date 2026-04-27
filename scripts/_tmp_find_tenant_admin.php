<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;

$tenants = Tenant::query()->orderBy('id')->get();
foreach ($tenants as $tenant) {
    $admins = User::on('tenant')
        ->where('tenant_id', $tenant->id)
        ->where('role', User::ROLE_TENANT_ADMIN)
        ->get(['id', 'username', 'email']);

    if ($admins->isNotEmpty()) {
        echo "TENANT {$tenant->id} {$tenant->slug}\n";
        foreach ($admins as $admin) {
            echo "ADMIN {$admin->id} {$admin->username} {$admin->email}\n";
        }
    }
}
