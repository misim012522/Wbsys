<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;

$tenant = Tenant::where('slug', 'vob')->first();
if (! $tenant) {
    echo "TENANT_NOT_FOUND\n";
    exit(1);
}

app(TenantDatabaseManager::class)->activate($tenant);

$username = 'tenant_admin_vob';
$email = 'tenant.admin.vob@example.test';
$password = 'ChangeMe123!';

$user = User::on('tenant')
    ->where('tenant_id', $tenant->id)
    ->where(function ($q) use ($username, $email) {
        $q->where('username', $username)->orWhere('email', $email);
    })
    ->first();

if (! $user) {
    $user = new User();
    $user->setConnection('tenant');
    $user->tenant_id = $tenant->id;
    $user->name = 'VOB Tenant Admin';
    $user->username = $username;
    $user->email = $email;
    $user->phone = '09123456789';
    $user->role = User::ROLE_TENANT_ADMIN;
}

$user->password = $password;
$user->approved_at = now();
$user->email_verified_at = now();
$user->archived_at = null;
$user->save();

echo "TENANT={$tenant->slug};HOST={$tenant->subdomain}.lvh.me:8000;LOGIN={$username};PASSWORD={$password}\n";
