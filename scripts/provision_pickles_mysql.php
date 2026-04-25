<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

$dbName = 'pickles_buksu_queueless';
// create database
DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) {
    echo "Tenant not found\n";
    exit(1);
}

$t->database_name = $dbName;
$t->save();

echo "Set tenant database_name={$t->database_name}\n";

$adminPassword = 'ChangeMe123';

try {
    app(App\Services\TenantDatabaseManager::class)->provision($t, [
        'name' => $t->name . ' Admin',
        'username' => 'tenant_admin',
        'email' => $t->email,
        'password' => $adminPassword,
    ]);
    echo "Provisioning complete. Admin password: $adminPassword\n";
} catch (\Throwable $e) {
    echo "Provisioning failed: " . $e->getMessage() . "\n";
    exit(1);
}
