<?php
// Usage: php scripts/dump_sessions.php [limit]
$limit = isset($argv[1]) ? (int)$argv[1] : 25;
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$table = config('session.table', 'sessions');
echo "Listing last $limit rows from table: $table\n";
try {
    $rows = DB::table($table)->orderBy('last_activity', 'desc')->limit($limit)->get();
    foreach ($rows as $r) {
        echo "id:" . $r->id . " len=" . strlen($r->id) . " last_activity:" . $r->last_activity . "\n";
    }
} catch (Exception $e) {
    echo "Error reading sessions table: " . $e->getMessage() . "\n";
}
