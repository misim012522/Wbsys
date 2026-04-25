<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$t = Tenant::where('slug', 'pickles')->first();
if (! $t) { echo "Tenant not found\n"; exit(1); }

$old = $t->database_name;
// normalize old name: strip trailing .db if present
$oldDb = preg_replace('/\.db$/', '', $old);
// if duplication like x_y_x_y, try to detect actual DB by checking existing DBs
$existing = DB::select('SHOW DATABASES');
$existingNames = array_map(fn($r) => array_values((array)$r)[0], $existing);

// find best match in existing DBs
$foundOld = null;
foreach ($existingNames as $name) {
    if (stripos($name, 'pickles') !== false) {
        $foundOld = $name;
        break;
    }
}
if (! $foundOld) {
    echo "Could not locate existing tenant DB automatically. Existing DBs: \n"; print_r($existingNames); exit(1);
}
$newDb = 'pickles_buksu_queueless';

if ($foundOld === $newDb) {
    echo "Old DB already named $newDb\n";
} else {
    echo "Copying from $foundOld to $newDb\n";
    DB::statement("CREATE DATABASE IF NOT EXISTS `$newDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$foundOld]);
    foreach ($tables as $tblRow) {
        $table = $tblRow->table_name ?? $tblRow->TABLE_NAME ?? array_values((array)$tblRow)[0];
        echo "Copying table $table...\n";
        DB::statement("CREATE TABLE `$newDb`.`$table` LIKE `$foundOld`.`$table`");
        DB::statement("INSERT INTO `$newDb`.`$table` SELECT * FROM `$foundOld`.`$table`");
    }
    echo "Copy complete.\n";
}

// update tenant record
$t->database_name = $newDb;
$t->save();

echo "Updated tenant.database_name to $newDb\n";

// verify
config(['database.connections.tenant.database' => $newDb]);
try {
    $users = DB::connection('tenant')->select('SELECT id, username, email, role, tenant_id, created_at FROM users');
    echo "Users in $newDb:\n"; print_r($users);
} catch (\Throwable $e) {
    echo "Error querying new tenant DB: " . $e->getMessage() . "\n";
}
