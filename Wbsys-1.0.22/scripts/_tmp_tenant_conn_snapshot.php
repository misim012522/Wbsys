<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $dbName = DB::connection('tenant')->getDatabaseName();
    echo "TENANT_CONNECTION_DB={$dbName}\n";

    $tenantIds = DB::connection('tenant')->table('tenants')->select('id', 'slug')->orderBy('id')->limit(20)->get();
    foreach ($tenantIds as $t) {
        echo "TENANT_ROW id={$t->id} slug={$t->slug}\n";
    }

    $users = DB::connection('tenant')->table('users')->select('id', 'tenant_id', 'username', 'email', 'role')->orderBy('id')->limit(50)->get();
    foreach ($users as $u) {
        echo "USER id={$u->id} tenant_id={$u->tenant_id} username={$u->username} email={$u->email} role={$u->role}\n";
    }
} catch (\Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    exit(1);
}
