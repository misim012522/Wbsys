<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) { echo "Tenant not found\n"; exit(1); }

echo "Old database_name: {$t->database_name}\n";
$new = 'pickles_buksu_queueless';
$t->database_name = $new;
$t->save();
echo "Updated database_name to: {$t->database_name}\n";

// Verify by attempting to connect to the cleaned DB name
config(['database.connections.tenant.database' => $t->database_name]);

try {
    $tables = DB::connection('tenant')->select('SHOW TABLES');
    echo "Tables in {$t->database_name}:\n";
    print_r($tables);
    $users = DB::connection('tenant')->select('SELECT id, username, email, role, tenant_id, created_at FROM users');
    echo "Users:\n";
    print_r($users);
} catch (\Throwable $e) {
    echo "Error querying tenant DB: " . $e->getMessage() . "\n";
}
