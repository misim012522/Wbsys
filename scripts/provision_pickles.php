<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

$dbName = 'pickles_buksu_queueless';
DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$t = Tenant::where('slug', 'pickles')->first();
if (! $t) {
    echo "Tenant not found\n";
    exit(1);
}
$t->database_name = $dbName;
$t->save();
echo "Updated tenant database_name={$t->database_name}\n";

$app->make(App\Services\TenantDatabaseManager::class)->provision($t, [
    'name' => $t->name . ' Admin',
    'username' => 'tenant_admin',
    'email' => $t->email,
    'password' => $t->name . 'pass',
]);

echo "Provision complete\n";
