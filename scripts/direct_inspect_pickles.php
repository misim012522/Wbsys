<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) {
    echo "Tenant not found\n"; exit(1);
}

$dbName = $t->database_name;
// if database_name contains .db, strip it for MySQL
if (str_ends_with($dbName, '.db')) {
    $dbName = preg_replace('/\.db$/', '', $dbName);
}

echo "Inspecting tenant DB: $dbName\n";

// Temporarily override tenant connection database
config(['database.connections.tenant.database' => $dbName]);

try {
    $tables = DB::connection('tenant')->select('SHOW TABLES');
    echo "Tables in $dbName:\n";
    print_r($tables);

    $users = DB::connection('tenant')->select('SELECT id, username, email, role, tenant_id, created_at FROM users');
    echo "Users in $dbName:\n";
    print_r($users);
} catch (\Throwable $e) {
    echo "Error querying tenant DB: " . $e->getMessage() . "\n";
}
