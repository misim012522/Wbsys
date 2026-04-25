<?php
// Usage: php scripts/print_session_info.php
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

echo "\n== session config ==\n";
print_r(config('session'));

$conn = config('session.connection') ?: config('database.default');
echo "\n== using DB connection: $conn ==\n";
try {
    $tables = DB::connection($conn)->select('SHOW TABLES');
    print_r($tables);
} catch (Exception $e) {
    echo "Error listing tables on connection $conn: " . $e->getMessage() . "\n";
}
